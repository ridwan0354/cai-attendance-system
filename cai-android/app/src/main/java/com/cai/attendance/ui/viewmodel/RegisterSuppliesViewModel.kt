package com.cai.attendance.ui.viewmodel

import android.content.Context
import android.graphics.Bitmap
import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.cai.attendance.data.local.entity.ParticipantEntity
import com.cai.attendance.data.repository.ParticipantRepository
import com.cai.attendance.ml.BarcodeScannerHelper
import com.cai.attendance.ml.FaceDetectorHelper
import com.cai.attendance.ml.FaceMatcher
import com.cai.attendance.ml.FaceNetModel
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import javax.inject.Inject

sealed class SuppliesScanResult {
    object Idle : SuppliesScanResult()
    object Scanning : SuppliesScanResult()
    object NoFace : SuppliesScanResult()
    object ModelNotReady : SuppliesScanResult()
    data class Success(
        val participantName: String,
        val groupName: String,
        val items: List<String>
    ) : SuppliesScanResult()
    data class Unknown(val confidence: Float) : SuppliesScanResult()
    data class Error(val message: String) : SuppliesScanResult()
}

@HiltViewModel
class RegisterSuppliesViewModel @Inject constructor(
    @ApplicationContext private val context: Context,
    private val participantRepo: ParticipantRepository,
) : ViewModel() {

    companion object {
        private const val TAG = "RegSuppliesViewModel"
        private const val COOLDOWN_MS = 2500L
    }

    private val faceNet     = FaceNetModel(context)
    private val faceDetector = FaceDetectorHelper()
    private val barcodeScanner = BarcodeScannerHelper()

    private val _scanResult = MutableStateFlow<SuppliesScanResult>(SuppliesScanResult.Idle)
    val scanResult: StateFlow<SuppliesScanResult> = _scanResult.asStateFlow()

    private val _isProcessing = MutableStateFlow(false)
    val isProcessing: StateFlow<Boolean> = _isProcessing.asStateFlow()

    private var candidates: List<ParticipantEntity> = emptyList()
    private var cooldownJob: Job? = null
    private var inCooldown = false

    init {
        if (!faceNet.isReady) {
            _scanResult.value = SuppliesScanResult.ModelNotReady
        }
        loadCandidates()
    }

    private fun loadCandidates() {
        viewModelScope.launch(Dispatchers.IO) {
            candidates = participantRepo.getParticipantsWithEmbedding()
            Log.d(TAG, "Loaded ${candidates.size} candidates with embeddings")
        }
    }

    fun processFrame(bitmap: Bitmap) {
        if (inCooldown || _isProcessing.value) return
        if (!faceNet.isReady) {
            _scanResult.value = SuppliesScanResult.ModelNotReady
            return
        }

        viewModelScope.launch(Dispatchers.Default) {
            _isProcessing.value = true

            try {
                // 1. Scan for QR/Barcode first
                val barcodes = barcodeScanner.scanBarcodes(bitmap)
                if (barcodes.isNotEmpty()) {
                    val qrCodeValue = barcodes.first().rawValue
                    if (!qrCodeValue.isNullOrBlank()) {
                        Log.d(TAG, "Detected QR/Barcode: $qrCodeValue")
                        val participant = participantRepo.findParticipantByCode(qrCodeValue)
                        if (participant != null) {
                            registerParticipantSupplies(participant)
                            return@launch
                        } else {
                            Log.w(TAG, "Participant not found locally for code: $qrCodeValue")
                        }
                    }
                }

                // 2. Fallback to Face Detection
                val faces = faceDetector.detectFaces(bitmap)
                if (faces.isEmpty()) {
                    _scanResult.value = SuppliesScanResult.NoFace
                    return@launch
                }

                // Ambil wajah pertama
                val face = faces.first()
                val croppedFace = faceDetector.cropFace(bitmap, face)
                if (croppedFace == null) {
                    _scanResult.value = SuppliesScanResult.NoFace
                    return@launch
                }

                if (candidates.isEmpty()) {
                    _scanResult.value = SuppliesScanResult.Error("Belum ada data peserta. Lakukan sync terlebih dahulu.")
                    return@launch
                }

                val embedding = faceNet.getEmbedding(croppedFace)
                if (embedding == null) {
                    _scanResult.value = SuppliesScanResult.NoFace
                    return@launch
                }

                val matchResult = FaceMatcher.findBestMatch(embedding, candidates)
                if (matchResult == null || !matchResult.isMatch) {
                    val conf = matchResult?.similarity ?: 0f
                    _scanResult.value = SuppliesScanResult.Unknown(conf)
                    return@launch
                }

                val participant = matchResult.participant
                registerParticipantSupplies(participant)

            } catch (e: Exception) {
                Log.e(TAG, "Processing error: ${e.message}")
                _scanResult.value = SuppliesScanResult.Error("Error: ${e.message}")
            } finally {
                _isProcessing.value = false
            }
        }
    }

    private suspend fun registerParticipantSupplies(participant: ParticipantEntity) {
        withContext(Dispatchers.IO) {
            try {
                // 1. Get participant's supplies
                val suppliesRes = participantRepo.getParticipantSupplies(participant.id)
                suppliesRes.fold(
                    onSuccess = { supplies ->
                        // 2. Mark all as received (sync)
                        val allIds = supplies.map { it.id }
                        val syncRes = participantRepo.syncParticipantSupplies(participant.id, allIds)
                        syncRes.fold(
                            onSuccess = {
                                val itemNames = supplies.map { it.name }
                                _scanResult.value = SuppliesScanResult.Success(
                                    participantName = participant.name,
                                    groupName = participant.groupName,
                                    items = itemNames
                                )
                                startCooldown()
                            },
                            onFailure = {
                                _scanResult.value = SuppliesScanResult.Error("Gagal sinkronisasi barang: ${it.message}")
                            }
                        )
                    },
                    onFailure = {
                        _scanResult.value = SuppliesScanResult.Error("Gagal mengambil daftar barang: ${it.message}")
                    }
                )
            } catch (e: Exception) {
                _scanResult.value = SuppliesScanResult.Error("Kesalahan jaringan/sistem: ${e.message}")
            }
        }
    }

    fun resetResult() {
        if (!inCooldown) {
            _scanResult.value = SuppliesScanResult.Idle
        }
    }

    private fun startCooldown() {
        inCooldown = true
        cooldownJob?.cancel()
        cooldownJob = viewModelScope.launch {
            delay(COOLDOWN_MS)
            inCooldown = false
        }
    }

    fun forceReset() {
        inCooldown = false
        _scanResult.value = SuppliesScanResult.Idle
    }

    override fun onCleared() {
        super.onCleared()
        faceNet.close()
        faceDetector.close()
        barcodeScanner.close()
    }
}
