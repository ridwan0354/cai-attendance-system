package com.cai.attendance.data.repository

import android.util.Log
import com.cai.attendance.data.local.dao.AttendanceQueueDao
import com.cai.attendance.data.local.entity.AttendanceQueueEntity
import com.cai.attendance.data.remote.ApiService
import com.cai.attendance.data.remote.dto.AttendanceRequest
import com.cai.attendance.data.remote.dto.SessionDto
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.withContext
import java.time.Instant
import java.time.format.DateTimeFormatter
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AttendanceRepository @Inject constructor(
    private val queueDao: AttendanceQueueDao,
    private val apiService: ApiService,
) {
    companion object {
        private const val TAG = "AttendanceRepository"
    }

    val pendingCount: Flow<Int> = queueDao.getPendingCountFlow()
    val allQueue: Flow<List<AttendanceQueueEntity>> = queueDao.getAllFlow()

    /**
     * Catat absensi:
     * 1. Simpan ke antrian lokal (Room DB)
     * 2. Coba upload ke server
     * 3. Jika berhasil, tandai sebagai uploaded
     * 4. Jika gagal (offline), tetap tersimpan di antrian untuk upload nanti
     */
    suspend fun recordAttendance(
        participantId: Int,
        sessionId: Int,
        participantName: String,
        groupName: String,
        groupColor: String,
        method: String,
        confidenceScore: Float?,
    ): Boolean = withContext(Dispatchers.IO) {

        val now = DateTimeFormatter.ISO_INSTANT.format(Instant.now())

        // Simpan ke antrian lokal dulu
        val queueId = queueDao.insert(
            AttendanceQueueEntity(
                participantId   = participantId,
                sessionId       = sessionId,
                participantName = participantName,
                groupName       = groupName,
                groupColor      = groupColor,
                method          = method,
                confidenceScore = confidenceScore,
                checkInTime     = now,
            )
        ).toInt()

        // Coba upload ke server
        return@withContext tryUpload(queueId, participantId, sessionId, method, confidenceScore, now)
    }

    /** Upload semua antrian yang belum terkirim ke server */
    suspend fun uploadPendingQueue(): Int = withContext(Dispatchers.IO) {
        val pending = queueDao.getPending()
        var uploadedCount = 0

        for (record in pending) {
            val success = tryUpload(
                localId        = record.localId,
                participantId  = record.participantId,
                sessionId      = record.sessionId,
                method         = record.method,
                confidence     = record.confidenceScore,
                checkInTime    = record.checkInTime
            )
            if (success) uploadedCount++
        }

        // Bersihkan record yang sudah diupload
        if (uploadedCount > 0) {
            queueDao.deleteUploaded()
        }

        uploadedCount
    }

    private suspend fun tryUpload(
        localId: Int,
        participantId: Int,
        sessionId: Int,
        method: String,
        confidence: Float?,
        checkInTime: String
    ): Boolean {
        return try {
            val response = apiService.recordAttendance(
                AttendanceRequest(
                    participantId   = participantId,
                    sessionId       = sessionId,
                    method          = method,
                    confidenceScore = confidence,
                    checkInTime     = checkInTime
                )
            )

            if (response.isSuccessful) {
                queueDao.markUploaded(localId)
                Log.d(TAG, "Uploaded attendance for participant $participantId")
                true
            } else {
                queueDao.incrementAttempts(localId)
                Log.w(TAG, "Upload failed: ${response.code()}")
                false
            }
        } catch (e: Exception) {
            queueDao.incrementAttempts(localId)
            Log.w(TAG, "Upload error (offline?): ${e.message}")
            false
        }
    }

    /** Ambil sesi yang sedang aktif dari server */
    suspend fun getActiveSession(): SessionDto? = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getActiveSession()
            if (response.isSuccessful) response.body()?.session else null
        } catch (e: Exception) {
            Log.w(TAG, "Cannot get active session: ${e.message}")
            null
        }
    }

    /** Ambil semua sesi dari server */
    suspend fun getAllSessions(): List<SessionDto> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAllSessions()
            if (response.isSuccessful) response.body()?.data ?: emptyList() else emptyList()
        } catch (e: Exception) {
            Log.w(TAG, "Cannot get sessions: ${e.message}")
            emptyList()
        }
    }
}
