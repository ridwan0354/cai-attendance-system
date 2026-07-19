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

data class CreateParticipantUiState(
    val isSaving: Boolean = false,
    val isSuccess: Boolean? = null,
    val message: String = ""
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
        _participantSupplies.value = emptyList()
    }

    private val _participantSupplies = MutableStateFlow<List<com.cai.attendance.data.remote.dto.SupplyDto>>(emptyList())
    val participantSupplies: StateFlow<List<com.cai.attendance.data.remote.dto.SupplyDto>> = _participantSupplies.asStateFlow()

    private val _isSuppliesLoading = MutableStateFlow(false)
    val isSuppliesLoading: StateFlow<Boolean> = _isSuppliesLoading.asStateFlow()

    fun loadParticipantSupplies(participantId: Int) {
        viewModelScope.launch {
            _isSuppliesLoading.value = true
            val result = participantRepo.getParticipantSupplies(participantId)
            result.fold(
                onSuccess = { list ->
                    _participantSupplies.value = list
                },
                onFailure = {
                    Log.e("ParticipantsViewModel", "Failed to load supplies: ${it.message}")
                }
            )
            _isSuppliesLoading.value = false
        }
    }

    fun toggleSupplySelection(supplyId: Int) {
        val currentList = _participantSupplies.value
        _participantSupplies.value = currentList.map {
            if (it.id == supplyId) it.copy(received = !it.received) else it
        }
    }

    fun saveParticipantSupplies(participantId: Int, onComplete: (Boolean) -> Unit = {}) {
        viewModelScope.launch {
            val selectedIds = _participantSupplies.value.filter { it.received }.map { it.id }
            val result = participantRepo.syncParticipantSupplies(participantId, selectedIds)
            result.fold(
                onSuccess = {
                    Log.d("ParticipantsViewModel", "Supplies saved successfully")
                    onComplete(true)
                },
                onFailure = {
                    Log.e("ParticipantsViewModel", "Failed to save supplies: ${it.message}")
                    onComplete(false)
                }
            )
        }
    }

    private suspend fun bitmapToBase64(bitmap: Bitmap): String = kotlinx.coroutines.withContext(Dispatchers.Default) {
        val outputStream = ByteArrayOutputStream()
        bitmap.compress(Bitmap.CompressFormat.JPEG, 85, outputStream)
        val bytes = outputStream.toByteArray()
        Base64.encodeToString(bytes, Base64.NO_WRAP)
    }

    private val _createState = MutableStateFlow(CreateParticipantUiState())
    val createState: StateFlow<CreateParticipantUiState> = _createState.asStateFlow()

    private val _groups = MutableStateFlow<List<com.cai.attendance.data.remote.dto.GroupDto>>(emptyList())
    val groups: StateFlow<List<com.cai.attendance.data.remote.dto.GroupDto>> = _groups.asStateFlow()

    private val _isLoadingGroups = MutableStateFlow(false)
    val isLoadingGroups: StateFlow<Boolean> = _isLoadingGroups.asStateFlow()

    fun loadGroups(onError: (String) -> Unit = {}) {
        viewModelScope.launch {
            _isLoadingGroups.value = true
            val result = participantRepo.getGroups()
            result.fold(
                onSuccess = { list ->
                    _groups.value = list
                },
                onFailure = {
                    Log.e("ParticipantsViewModel", "Failed to load groups: ${it.message}")
                    onError(it.message ?: "Gagal memuat kelompok dari server")
                }
            )
            _isLoadingGroups.value = false
        }
    }

    fun createParticipant(
        name: String,
        groupId: Int,
        gender: String,
        phone: String,
        qrCode: String?,
        onComplete: (Boolean, ParticipantEntity?) -> Unit
    ) {
        viewModelScope.launch {
            _createState.value = CreateParticipantUiState(isSaving = true, message = "Menyimpan peserta…")
            val result = participantRepo.createParticipant(name, groupId, gender, phone, qrCode)
            result.fold(
                onSuccess = { entity ->
                    _createState.value = CreateParticipantUiState(
                        isSaving = false,
                        isSuccess = true,
                        message = "Peserta berhasil ditambahkan"
                    )
                    onComplete(true, entity)
                },
                onFailure = { err ->
                    _createState.value = CreateParticipantUiState(
                        isSaving = false,
                        isSuccess = false,
                        message = err.message ?: "Gagal menambahkan peserta"
                    )
                    onComplete(false, null)
                }
            )
        }
    }

    fun resetCreateState() {
        _createState.value = CreateParticipantUiState()
    }
}
