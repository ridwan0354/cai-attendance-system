package com.cai.attendance.di

import com.cai.attendance.data.preferences.AppPreferences
import com.cai.attendance.data.remote.ApiService
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    /**
     * OkHttpClient dengan:
     * - X-Api-Key header otomatis dari preferences
     * - Timeout 30 detik (untuk download foto yang mungkin lambat di LAN)
     * - Logging untuk debug
     */
    @Provides
    @Singleton
    fun provideOkHttpClient(preferences: AppPreferences): OkHttpClient {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        return OkHttpClient.Builder()
            .addInterceptor { chain ->
                // Baca API key dari preferences (blocking karena interceptor sync)
                val apiKey = runBlocking { preferences.apiKey.first() }
                val request = chain.request().newBuilder()
                    .addHeader("X-Api-Key", apiKey)
                    .addHeader("Accept", "application/json")
                    .build()
                chain.proceed(request)
            }
            .addInterceptor(logging)
            .connectTimeout(15, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()
    }

    /**
     * Retrofit dengan base URL dinamis.
     * Base URL diambil dari preferences saat pertama kali digunakan.
     * Catatan: jika user mengubah server URL, perlu restart app atau
     * re-inject dengan URL baru.
     */
    @Provides
    @Singleton
    fun provideRetrofit(
        okHttpClient: OkHttpClient,
        preferences: AppPreferences
    ): Retrofit {
        // Ambil server URL dari preferences (fallback ke placeholder)
        val baseUrl = runBlocking {
            preferences.serverUrl.first().let {
                if (it.isBlank()) "http://localhost:8000/" else "$it/"
            }
        }

        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
    }

    @Provides
    @Singleton
    fun provideApiService(retrofit: Retrofit): ApiService =
        retrofit.create(ApiService::class.java)
}
