package com.cai.attendance.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

/**
 * Data peserta yang disimpan lokal di device Android.
 * embedding: JSON string dari FloatArray 512 dimensi (FaceNet output)
 */
@Entity(tableName = "participants")
data class ParticipantEntity(
    @PrimaryKey val id: Int,
    val name: String,
    val nik: String?,
    val groupId: Int,
    val groupName: String,
    val groupColor: String,
    val hasPhoto: Boolean,
    val faceRegistered: Boolean,
    val photoPath: String?,        // path file foto lokal di internal storage
    val embeddingJson: String?,    // JSON FloatArray 512-d dari FaceNet
    val updatedAt: String,
    val syncedAt: Long = System.currentTimeMillis()
)
