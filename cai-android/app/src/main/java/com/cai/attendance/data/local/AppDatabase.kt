package com.cai.attendance.data.local

import androidx.room.Database
import androidx.room.RoomDatabase
import com.cai.attendance.data.local.dao.AttendanceQueueDao
import com.cai.attendance.data.local.dao.ParticipantDao
import com.cai.attendance.data.local.entity.AttendanceQueueEntity
import com.cai.attendance.data.local.entity.ParticipantEntity

@Database(
    entities = [
        ParticipantEntity::class,
        AttendanceQueueEntity::class,
    ],
    version = 3,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun participantDao(): ParticipantDao
    abstract fun attendanceQueueDao(): AttendanceQueueDao

    companion object {
        const val DATABASE_NAME = "cai_attendance.db"
    }
}
