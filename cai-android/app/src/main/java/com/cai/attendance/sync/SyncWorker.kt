package com.cai.attendance.sync

import android.content.Context
import android.util.Log
import androidx.hilt.work.HiltWorker
import androidx.work.*
import com.cai.attendance.data.preferences.AppPreferences
import com.cai.attendance.data.repository.AttendanceRepository
import com.cai.attendance.data.repository.ParticipantRepository
import com.cai.attendance.data.repository.SyncResult
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject
import kotlinx.coroutines.flow.first
import java.util.concurrent.TimeUnit

/**
 * WorkManager worker untuk sinkronisasi background.
 * - Upload antrian absensi yang offline
 * - Incremental sync peserta (peserta baru/diperbarui)
 *
 * Bisa dipanggil secara periodik atau one-time.
 */
@HiltWorker
class SyncWorker @AssistedInject constructor(
    @Assisted context: Context,
    @Assisted params: WorkerParameters,
    private val participantRepo: ParticipantRepository,
    private val attendanceRepo: AttendanceRepository,
    private val preferences: AppPreferences,
) : CoroutineWorker(context, params) {

    companion object {
        private const val TAG = "SyncWorker"
        const val WORK_NAME_PERIODIC  = "cai_periodic_sync"
        const val WORK_NAME_IMMEDIATE = "cai_immediate_sync"

        /**
         * Jadwalkan sync periodik setiap 30 menit (saat app berjalan di background).
         * Hanya berjalan saat ada koneksi jaringan.
         */
        fun schedulePeriodic(workManager: WorkManager) {
            val request = PeriodicWorkRequestBuilder<SyncWorker>(
                30, TimeUnit.MINUTES
            )
                .setConstraints(
                    Constraints.Builder()
                        .setRequiredNetworkType(NetworkType.CONNECTED)
                        .build()
                )
                .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 5, TimeUnit.MINUTES)
                .build()

            workManager.enqueueUniquePeriodicWork(
                WORK_NAME_PERIODIC,
                ExistingPeriodicWorkPolicy.KEEP,
                request
            )
        }

        /**
         * Jalankan sync segera (untuk tombol "Sync Sekarang").
         */
        fun runNow(workManager: WorkManager) {
            val request = OneTimeWorkRequestBuilder<SyncWorker>()
                .setConstraints(
                    Constraints.Builder()
                        .setRequiredNetworkType(NetworkType.CONNECTED)
                        .build()
                )
                .build()

            workManager.enqueueUniqueWork(
                WORK_NAME_IMMEDIATE,
                ExistingWorkPolicy.REPLACE,
                request
            )
        }
    }

    override suspend fun doWork(): Result {
        Log.d(TAG, "Starting sync work")

        // Upload antrian offline dulu
        val uploaded = attendanceRepo.uploadPendingQueue()
        if (uploaded > 0) {
            Log.d(TAG, "Uploaded $uploaded pending attendance records")
        }

        // Incremental sync peserta (hanya yang baru/diperbarui)
        val lastSync = preferences.lastSync.first()
        val sinceTimestamp: String? = if (lastSync > 0) {
            java.time.Instant.ofEpochMilli(lastSync).toString()
        } else null

        return when (val result = participantRepo.syncParticipants(sinceTimestamp)) {
            is SyncResult.Success -> {
                preferences.updateLastSync(System.currentTimeMillis())
                Log.d(TAG, "Sync success: ${result.totalDownloaded} downloaded, ${result.totalEmbedded} embedded")
                Result.success()
            }
            is SyncResult.Error -> {
                Log.e(TAG, "Sync error: ${result.message}")
                Result.retry()
            }
        }
    }
}
