package com.cai.attendance.ui.viewmodel

import android.content.Context
import android.graphics.Bitmap
import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.cai.attendance.data.local.entity.ParticipantEntity
import com.cai.attendance.data.remote.dto.SessionDto
import com.cai.attendance.data.repository.AttendanceRepository
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

sealed class ScanResult {
    object Idle : ScanResult()
    object Scanning : ScanResult()
    object NoFace : ScanResult()
    object ModelNotReady : ScanResult()
    object NoActiveSession : ScanResult()
    data class PendingConfirmation(
        val participantId: Int,
        val participantName: String,
        val groupName: String,
        val groupColor: String,
        val confidence: Float,
        val method: String
    ) : ScanResult()
    data class Recognized(
        val participantName: String,
        val groupName: String,
        val groupColor: String,
        val confidence: Float,
        val alreadyPresent: Boolean = false
    ) : ScanResult()
    data class Unknown(val confidence: Float) : ScanResult()
    data class Error(val message: String) : ScanResult()
}

@HiltViewModel
class ScannerViewModel @Inject constructor(
    @ApplicationContext private val context: Context,
    private val participantRepo: ParticipantRepository,
    private val attendanceRepo: AttendanceRepository,
) : ViewModel() {

    companion object {
        private const val TAG = "ScannerViewModel"
        private const val COOLDOWN_MS = 2500L  // Cooldown antar scan berhasil
    }

    private val faceNet     = FaceNetModel(context)
    private val faceDetector = FaceDetectorHelper()
    private val barcodeScanner = BarcodeScannerHelper()

    private val _scanResult = MutableStateFlow<ScanResult>(ScanResult.Idle)
    val scanResult: StateFlow<ScanResult> = _scanResult.asStateFlow()

    private val _activeSession = MutableStateFlow<SessionDto?>(null)
    val activeSession: StateFlow<SessionDto?> = _activeSession.asStateFlow()

    private val _isProcessing = MutableStateFlow(false)
    val isProcessing: StateFlow<Boolean> = _isProcessing.asStateFlow()

    private var candidates: List<ParticipantEntity> = emptyList()
    private var cooldownJob: Job? = null
    private var inCooldown = false

    init {
        if (!faceNet.isReady) {
            _scanResult.value = ScanResult.ModelNotReady
        }
        loadCandidates()
        loadActiveSession()
    }

    private fun loadCandidates() {
        viewModelScope.launch(Dispatchers.IO) {
            candidates = participantRepo.getParticipantsWithEmbedding()
            Log.d(TAG, "Loaded ${candidates.size} candidates with embeddings")
        }
    }

    private fun loadActiveSession() {
        viewModelScope.launch {
            _activeSession.value = attendanceRepo.getActiveSession()
        }
    }

    /**
     * Proses frame dari kamera.
     * Dipanggil ~1-2x per detik dari CameraX analyzer.
     */
    fun processFrame(bitmap: Bitmap) {
        if (inCooldown || _isProcessing.value || _scanResult.value is ScanResult.PendingConfirmation) return
        if (!faceNet.isReady) {
            _scanResult.value = ScanResult.ModelNotReady
            return
        }

        val session = _activeSession.value
        if (session == null) {
            _scanResult.value = ScanResult.NoActiveSession
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
                            _scanResult.value = ScanResult.PendingConfirmation(
                                participantId   = participant.id,
                                participantName = participant.name,
                                groupName       = participant.groupName,
                                groupColor      = participant.groupColor,
                                confidence      = 100f,
                                method          = "qr"
                            )

                            return@launch
                        } else {
                            Log.w(TAG, "Participant not found locally for code: $qrCodeValue")
                        }
                    }
                }

                // 2. Deteksi wajah menggunakan ML Kit
                val faces = faceDetector.detectFaces(bitmap)
                if (faces.isEmpty()) {
                    _scanResult.value = ScanResult.NoFace
                    return@launch
                }

                // Ambil wajah pertama yang terdeteksi
                val face = faces.first()

                // 3. Crop area wajah saja
                val croppedFace = faceDetector.cropFace(bitmap, face)
                if (croppedFace == null) {
                    _scanResult.value = ScanResult.NoFace
                    return@launch
                }

                if (candidates.isEmpty()) {
                    _scanResult.value = ScanResult.Error("Belum ada data peserta. Lakukan sync terlebih dahulu.")
                    return@launch
                }

                // 4. Generate embedding dari wajah hasil crop
                val embedding = faceNet.getEmbedding(croppedFace)

                if (embedding == null) {
                    _scanResult.value = ScanResult.NoFace
                    return@launch
                }

                // 4. Cari kemiripan dengan candidates di DB
                val matchResult = FaceMatcher.findBestMatch(embedding, candidates)

                if (matchResult == null || !matchResult.isMatch) {
                    val conf = matchResult?.similarity ?: 0f
                    _scanResult.value = ScanResult.Unknown(conf)
                    return@launch
                }

                // Match ditemukan! Tampilkan dialog konfirmasi ke pengguna lebih dahulu
                val participant = matchResult.participant
                val confidence  = matchResult.similarity

                _scanResult.value = ScanResult.PendingConfirmation(
                    participantId   = participant.id,
                    participantName = participant.name,
                    groupName       = participant.groupName,
                    groupColor      = participant.groupColor,
                    confidence      = FaceMatcher.similarityToConfidence(confidence),
                    method          = "face"
                )

            } catch (e: Exception) {
                Log.e(TAG, "Processing error: ${e.message}")
                _scanResult.value = ScanResult.Error("Error: ${e.message}")
            } finally {
                _isProcessing.value = false
            }
        }
    }

    fun confirmAttendance() {
        val current = _scanResult.value
        if (current is ScanResult.PendingConfirmation) {
            val session = _activeSession.value ?: return
            viewModelScope.launch(Dispatchers.IO) {
                attendanceRepo.recordAttendance(
                    participantId   = current.participantId,
                    sessionId       = session.id,
                    participantName = current.participantName,
                    groupName       = current.groupName,
                    groupColor      = current.groupColor,
                    method          = current.method,
                    confidenceScore = current.confidence,
                )

                withContext(Dispatchers.Main) {
                    _scanResult.value = ScanResult.Recognized(
                        participantName = current.participantName,
                        groupName       = current.groupName,
                        groupColor      = current.groupColor,
                        confidence      = current.confidence,
                        alreadyPresent  = false
                    )
                    startCooldown()
                }
            }
        }
    }

    fun rejectConfirmation() {
        _scanResult.value = ScanResult.Idle
        startCooldown()
    }

            } catch (e: Exception) {
                Log.e(TAG, "Processing error: ${e.message}")
                _scanResult.value = ScanResult.Error("Error: ${e.message}")
            } finally {
                _isProcessing.value = false
            }
        }
    }

    fun refreshSession() {
        loadActiveSession()
    }

    fun resetResult() {
        if (!inCooldown) {
            _scanResult.value = ScanResult.Idle
        }
    }

    private fun startCooldown() {
        inCooldown = true
        cooldownJob?.cancel()
        cooldownJob = viewModelScope.launch {
            delay(COOLDOWN_MS)
            inCooldown = false
            _scanResult.value = ScanResult.Idle
        }
    }

    override fun onCleared() {
        super.onCleared()
        faceNet.close()
        faceDetector.close()
        barcodeScanner.close()
    }
}
