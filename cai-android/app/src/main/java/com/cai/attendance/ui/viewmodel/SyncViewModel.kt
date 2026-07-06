package com.cai.attendance.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.cai.attendance.data.preferences.AppPreferences
import com.cai.attendance.data.repository.ParticipantRepository
import com.cai.attendance.data.repository.SyncResult
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SyncUiState(
    val isSyncing: Boolean    = false,
    val progress: Int         = 0,
    val total: Int            = 0,
    val currentName: String   = "",
    val message: String       = "Siap untuk sync",
    val isSuccess: Boolean?   = null,
    val downloaded: Int       = 0,
    val embedded: Int         = 0,
    val skipped: Int          = 0,
    val totalLocal: Int       = 0,
    val withPhoto: Int        = 0,
    val withEmbedding: Int    = 0,
)

@HiltViewModel
class SyncViewModel @Inject constructor(
    private val participantRepo: ParticipantRepository,
    private val preferences: AppPreferences,
) : ViewModel() {

    private val _uiState = MutableStateFlow(SyncUiState())
    val uiState: StateFlow<SyncUiState> = _uiState.asStateFlow()

    init {
        loadLocalStats()
    }

    private fun loadLocalStats() {
        viewModelScope.launch {
            val (total, withPhoto, withEmb) = participantRepo.getStats()
            _uiState.value = _uiState.value.copy(
                totalLocal    = total,
                withPhoto     = withPhoto,
                withEmbedding = withEmb,
                message       = if (total == 0) "Belum ada data lokal. Lakukan sync." else "Data lokal: $total peserta"
            )
        }
    }

    /** Full sync dari awal (hapus data lama jika ada) */
    fun startFullSync() = startSync(incremental = false)

    /** Incremental sync (hanya yang baru/diperbarui) */
    fun startIncrementalSync() = startSync(incremental = true)

    private fun startSync(incremental: Boolean) {
        if (_uiState.value.isSyncing) return

        viewModelScope.launch {
            val since = if (incremental) {
                val lastSync = preferences.lastSync.first()
                if (lastSync > 0) java.time.Instant.ofEpochMilli(lastSync).toString() else null
            } else null

            _uiState.value = _uiState.value.copy(
                isSyncing  = true,
                progress   = 0,
                total      = 0,
                isSuccess  = null,
                message    = "Menghubungi server…"
            )

            val result = participantRepo.syncParticipants(
                sinceTimestamp = since,
                onProgress     = { current, total, msg ->
                    _uiState.value = _uiState.value.copy(
                        progress    = current,
                        total       = total,
                        currentName = msg,
                        message     = msg
                    )
                }
            )

            when (result) {
                is SyncResult.Success -> {
                    preferences.updateLastSync(System.currentTimeMillis())
                    val (total, withPhoto, withEmb) = participantRepo.getStats()
                    _uiState.value = _uiState.value.copy(
                        isSyncing     = false,
                        isSuccess     = true,
                        downloaded    = result.totalDownloaded,
                        embedded      = result.totalEmbedded,
                        skipped       = result.totalSkipped,
                        totalLocal    = total,
                        withPhoto     = withPhoto,
                        withEmbedding = withEmb,
                        message       = "Sync selesai! ${result.totalDownloaded} foto didownload, ${result.totalEmbedded} embedding dibuat."
                    )
                }
                is SyncResult.Error -> {
                    _uiState.value = _uiState.value.copy(
                        isSyncing = false,
                        isSuccess = false,
                        message   = "Sync gagal: ${result.message}"
                    )
                }
            }
        }
    }
}
