package com.cai.attendance.data.local.dao

import androidx.room.*
import com.cai.attendance.data.local.entity.ParticipantEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface ParticipantDao {

    @Query("SELECT * FROM participants ORDER BY name ASC")
    fun getAllFlow(): Flow<List<ParticipantEntity>>

    @Query("SELECT * FROM participants ORDER BY name ASC")
    suspend fun getAll(): List<ParticipantEntity>

    @Query("SELECT * FROM participants WHERE id = :id")
    suspend fun getById(id: Int): ParticipantEntity?

    /** Ambil semua peserta yang sudah punya embedding (siap untuk face matching) */
    @Query("SELECT * FROM participants WHERE embeddingJson IS NOT NULL")
    suspend fun getAllWithEmbedding(): List<ParticipantEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(participant: ParticipantEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(participants: List<ParticipantEntity>)

    @Update
    suspend fun update(participant: ParticipantEntity)

    /** Update hanya embedding, tanpa mengubah data lain */
    @Query("UPDATE participants SET embeddingJson = :embeddingJson WHERE id = :id")
    suspend fun updateEmbedding(id: Int, embeddingJson: String)

    /** Update path foto lokal */
    @Query("UPDATE participants SET photoPath = :photoPath WHERE id = :id")
    suspend fun updatePhotoPath(id: Int, photoPath: String)

    /** Update status pendaftaran wajah */
    @Query("UPDATE participants SET faceRegistered = :faceRegistered WHERE id = :id")
    suspend fun updateFaceRegistrationStatus(id: Int, faceRegistered: Boolean)

    @Query("DELETE FROM participants WHERE id = :id")
    suspend fun deleteById(id: Int)

    @Query("DELETE FROM participants")
    suspend fun deleteAll()

    @Query("SELECT COUNT(*) FROM participants")
    suspend fun count(): Int

    @Query("SELECT COUNT(*) FROM participants WHERE embeddingJson IS NOT NULL")
    suspend fun countWithEmbedding(): Int

    @Query("SELECT COUNT(*) FROM participants WHERE photoPath IS NOT NULL")
    suspend fun countWithPhoto(): Int
}
