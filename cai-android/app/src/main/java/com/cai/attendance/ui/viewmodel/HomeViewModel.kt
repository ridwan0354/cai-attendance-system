package com.cai.attendance.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.work.WorkManager
import com.cai.attendance.data.preferences.AppPreferences
import com.cai.attendance.data.repository.AttendanceRepository
import com.cai.attendance.data.repository.ParticipantRepository
import com.cai.attendance.sync.SyncWorker
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

data class HomeUiState(
    val totalParticipants: Int  = 0,
    val totalWithPhoto: Int     = 0,
    val totalWithEmbedding: Int = 0,
    val pendingUpload: Int      = 0,
    val lastSyncTime: Long      = 0L,
    val serverUrl: String       = "",
)

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val participantRepo: ParticipantRepository,
    private val attendanceRepo: AttendanceRepository,
    private val preferences: AppPreferences,
    private val workManager: WorkManager,
) : ViewModel() {

    private val _uiState = MutableStateFlow(HomeUiState())
    val uiState: StateFlow<HomeUiState> = _uiState.asStateFlow()

    val pendingCount = attendanceRepo.pendingCount
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0)

    init {
        loadStats()
        // Jadwalkan sync periodik
        SyncWorker.schedulePeriodic(workManager)
    }

    fun loadStats() {
        viewModelScope.launch {
            val (total, withPhoto, withEmb) = participantRepo.getStats()
            val url = preferences.serverUrl.first()
            _uiState.value = HomeUiState(
                totalParticipants  = total,
                totalWithPhoto     = withPhoto,
                totalWithEmbedding = withEmb,
                serverUrl          = url,
            )
        }
    }

    fun syncNow() {
        SyncWorker.runNow(workManager)
    }

    private val _isUploading = MutableStateFlow(false)
    val isUploading: StateFlow<Boolean> = _isUploading.asStateFlow()

    fun uploadPending(onComplete: (count: Int) -> Unit) {
        viewModelScope.launch {
            _isUploading.value = true
            val count = attendanceRepo.uploadPendingQueue()
            _isUploading.value = false
            onComplete(count)
            loadStats() // Reload statistik di home agar angka antrian langsung jadi 0
        }
    }

    fun logout(onDone: () -> Unit) {
        viewModelScope.launch {
            preferences.clearAll()
            onDone()
        }
    }
}
