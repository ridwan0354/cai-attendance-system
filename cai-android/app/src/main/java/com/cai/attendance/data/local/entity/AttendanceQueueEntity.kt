package com.cai.attendance.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

/**
 * Antrian absensi yang belum berhasil diupload ke server (mode offline).
 * Setelah berhasil diupload, record ini akan dihapus.
 */
@Entity(tableName = "attendance_queue")
data class AttendanceQueueEntity(
    @PrimaryKey(autoGenerate = true) val localId: Int = 0,
    val participantId: Int,
    val sessionId: Int,
    val participantName: String,
    val groupName: String,
    val groupColor: String,
    val method: String,                // "face", "manual"
    val confidenceScore: Float?,
    val checkInTime: String,           // ISO-8601
    val isUploaded: Boolean = false,
    val uploadAttempts: Int = 0,
    val createdAt: Long = System.currentTimeMillis()
)
