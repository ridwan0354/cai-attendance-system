package com.cai.attendance.di

import android.content.Context
import androidx.room.Room
import com.cai.attendance.data.local.AppDatabase
import com.cai.attendance.data.local.dao.AttendanceQueueDao
import com.cai.attendance.data.local.dao.ParticipantDao
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DatabaseModule {

    @Provides
    @Singleton
    fun provideDatabase(@ApplicationContext context: Context): AppDatabase =
        Room.databaseBuilder(
            context,
            AppDatabase::class.java,
            AppDatabase.DATABASE_NAME
        )
            .fallbackToDestructiveMigration()
            .build()

    @Provides
    fun provideParticipantDao(db: AppDatabase): ParticipantDao = db.participantDao()

    @Provides
    fun provideAttendanceQueueDao(db: AppDatabase): AttendanceQueueDao = db.attendanceQueueDao()
}
