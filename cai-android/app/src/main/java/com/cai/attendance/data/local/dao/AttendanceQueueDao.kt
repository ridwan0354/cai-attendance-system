package com.cai.attendance.data.local.dao

import androidx.room.*
import com.cai.attendance.data.local.entity.AttendanceQueueEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface AttendanceQueueDao {

    @Query("SELECT * FROM attendance_queue ORDER BY createdAt DESC")
    fun getAllFlow(): Flow<List<AttendanceQueueEntity>>

    @Query("SELECT * FROM attendance_queue WHERE isUploaded = 0 ORDER BY createdAt ASC")
    suspend fun getPending(): List<AttendanceQueueEntity>

    @Query("SELECT COUNT(*) FROM attendance_queue WHERE isUploaded = 0")
    fun getPendingCountFlow(): Flow<Int>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(record: AttendanceQueueEntity): Long

    @Query("UPDATE attendance_queue SET isUploaded = 1 WHERE localId = :localId")
    suspend fun markUploaded(localId: Int)

    @Query("""
        UPDATE attendance_queue 
        SET uploadAttempts = uploadAttempts + 1 
        WHERE localId = :localId
    """)
    suspend fun incrementAttempts(localId: Int)

    @Query("DELETE FROM attendance_queue WHERE isUploaded = 1")
    suspend fun deleteUploaded()

    @Query("SELECT COUNT(*) FROM attendance_queue")
    suspend fun count(): Int
}
