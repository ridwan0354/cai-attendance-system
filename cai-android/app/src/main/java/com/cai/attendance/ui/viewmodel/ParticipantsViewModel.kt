package com.cai.attendance.ui.viewmodel

import android.graphics.Bitmap
import android.util.Base64
import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.cai.attendance.data.local.entity.ParticipantEntity
import com.cai.attendance.data.repository.ParticipantRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import java.io.ByteArrayOutputStream
import javax.inject.Inject

data class RegisterFaceUiState(
    val isUploading: Boolean = false,
    val isSuccess: Boolean?  = null,
    val message: String      = ""
)

@HiltViewModel
class ParticipantsViewModel @Inject constructor(
    private val participantRepo: ParticipantRepository
) : ViewModel() {

    private val _searchQuery = MutableStateFlow("")
    val searchQuery: StateFlow<String> = _searchQuery.asStateFlow()

    // Ambil list peserta real-time dari database lokal Room
    val participantsList: StateFlow<List<ParticipantEntity>> = participantRepo.allParticipants
        .combine(_searchQuery) { list, query ->
            if (query.isBlank()) {
                list
            } else {
                list.filter { it.name.contains(query, ignoreCase = true) || (it.nik?.contains(query) == true) }
            }
        }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    private val _registerState = MutableStateFlow(RegisterFaceUiState())
    val registerState: StateFlow<RegisterFaceUiState> = _registerState.asStateFlow()

    fun onSearchQueryChanged(query: String) {
        _searchQuery.value = query
    }

    /**
     * Daftarkan wajah peserta:
     * 1. Kompres bitmap ke JPEG
     * 2. Konversi ke Base64
     * 3. Panggil repo untuk upload & sync local
     */
    fun registerFace(participantId: Int, bitmap: Bitmap, onComplete: (Boolean) -> Unit) {
        viewModelScope.launch {
            _registerState.value = RegisterFaceUiState(isUploading = true, message = "Memproses wajah…")

            try {
                // Konversi bitmap ke Base64 string (JPEG) secara asynchronous
                val base64 = bitmapToBase64(bitmap)
                
                val result = participantRepo.uploadFaceRegistration(participantId, bitmap, base64)
                
                result.fold(
                    onSuccess = { msg ->
                        _registerState.value = RegisterFaceUiState(
                            isUploading = false,
                            isSuccess   = true,
                            message     = msg
                        )
                        onComplete(true)
                    },
                    onFailure = { err ->
                        _registerState.value = RegisterFaceUiState(
                            isUploading = false,
                            isSuccess   = false,
                            message     = err.message ?: "Gagal mendaftarkan wajah ke server"
                        )
                        onComplete(false)
                    }
                )
            } catch (e: Exception) {
                _registerState.value = RegisterFaceUiState(
                    isUploading = false,
                    isSuccess   = false,
                    message     = "Gagal memproses gambar: ${e.message}"
                )
                onComplete(false)
            }
        }
    }

    fun resetRegisterState() {
        _registerState.value = RegisterFaceUiState()
    }

    private suspend fun bitmapToBase64(bitmap: Bitmap): String = kotlinx.coroutines.withContext(Dispatchers.Default) {
        val outputStream = ByteArrayOutputStream()
        bitmap.compress(Bitmap.CompressFormat.JPEG, 85, outputStream)
        val bytes = outputStream.toByteArray()
        Base64.encodeToString(bytes, Base64.NO_WRAP)
    }
}
