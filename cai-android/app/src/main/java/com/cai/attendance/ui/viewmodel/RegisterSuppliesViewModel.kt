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
    
    // Hasil scan berhasil, tampilkan checklist barang ke panitia
    data class Scanned(
        val participantId: Int,
        val participantName: String,
        val groupName: String,
        val phone: String,
        val supplies: List<com.cai.attendance.data.remote.dto.SupplyDto>
    ) : SuppliesScanResult()
    
    // Berhasil disimpan
    data class Success(val participantName: String) : SuppliesScanResult()
    
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
        private const val COOLDOWN_MS = 3000L // Cooldown scan untuk menghindari scan beruntun dari orang yang sama
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
        
        // Jika sedang menampilkan popup checklist, jangan scan wajah/QR lain
        if (_scanResult.value is SuppliesScanResult.Scanned || _scanResult.value is SuppliesScanResult.Success) {
            return
        }

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
                            onParticipantRecognized(participant)
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
                onParticipantRecognized(participant)

            } catch (e: Exception) {
                Log.e(TAG, "Processing error: ${e.message}")
                _scanResult.value = SuppliesScanResult.Error("Error: ${e.message}")
            } finally {
                _isProcessing.value = false
            }
        }
    }

    private suspend fun onParticipantRecognized(participant: ParticipantEntity) {
        withContext(Dispatchers.IO) {
            try {
                // Ambil data barang dari server VPS
                val suppliesRes = participantRepo.getParticipantSupplies(participant.id)
                suppliesRes.fold(
                    onSuccess = { supplies ->
                        // Jika peserta belum pernah mengambil barang (semua received = false),
                        // centang semuanya secara default untuk memudahkan panitia
                        val processedSupplies = if (supplies.none { it.received }) {
                            supplies.map { it.copy(received = true) }
                        } else {
                            supplies
                        }

                        _scanResult.value = SuppliesScanResult.Scanned(
                            participantId   = participant.id,
                            participantName = participant.name,
                            groupName       = participant.groupName,
                            phone           = participant.phone,
                            supplies        = processedSupplies
                        )
                        startCooldown()
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

    fun toggleSupplySelection(supplyId: Int) {
        val currentState = _scanResult.value
        if (currentState is SuppliesScanResult.Scanned) {
            val updatedSupplies = currentState.supplies.map {
                if (it.id == supplyId) it.copy(received = !it.received) else it
            }
            _scanResult.value = currentState.copy(supplies = updatedSupplies)
        }
    }

    fun saveSuppliesSelection() {
        val currentState = _scanResult.value
        if (currentState is SuppliesScanResult.Scanned) {
            _isProcessing.value = true
            viewModelScope.launch(Dispatchers.IO) {
                try {
                    val selectedIds = currentState.supplies.filter { it.received }.map { it.id }
                    val result = participantRepo.syncParticipantSupplies(currentState.participantId, selectedIds)
                    result.fold(
                        onSuccess = {
                            _scanResult.value = SuppliesScanResult.Success(currentState.participantName)
                            // Tampilkan dialog sukses selama 1.5 detik lalu tutup otomatis kembali ke mode scan
                            delay(1500)
                            forceReset()
                        },
                        onFailure = {
                            _scanResult.value = SuppliesScanResult.Error("Gagal menyimpan registrasi: ${it.message}")
                        }
                    )
                } catch (e: Exception) {
                    _scanResult.value = SuppliesScanResult.Error("Gagal menyimpan: ${e.message}")
                } finally {
                    _isProcessing.value = false
                }
            }
        }
    }

    fun resetResult() {
        if (!inCooldown && _scanResult.value !is SuppliesScanResult.Scanned && _scanResult.value !is SuppliesScanResult.Success) {
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
