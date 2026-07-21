package com.cai.attendance.data.remote

import com.cai.attendance.data.remote.dto.*
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.*

/**
 * Retrofit interface untuk semua endpoint mobile API dari Laravel.
 * Base URL dikonfigurasi secara dinamis di NetworkModule berdasarkan
 * URL server yang disimpan di AppPreferences.
 */
interface ApiService {

    /** Cek status sinkronisasi (jumlah peserta, last updated) */
    @GET("api/mobile/sync/info")
    suspend fun getSyncInfo(): Response<SyncInfoResponse>

    /**
     * Daftar peserta dengan foto terdaftar.
     * @param since  timestamp ISO-8601 untuk incremental sync (opsional)
     * @param page   halaman saat ini
     * @param perPage jumlah data per halaman
     */
    @GET("api/mobile/participants")
    suspend fun getParticipants(
        @Query("since")     since: String?     = null,
        @Query("page")      page: Int          = 1,
        @Query("per_page")  perPage: Int       = 100,
    ): Response<ParticipantsResponse>

    /**
     * Download foto peserta sebagai binary JPEG.
     * Hasil disimpan ke file lokal di internal storage.
     */
    @GET("api/mobile/participants/{id}/photo")
    @Streaming
    suspend fun getParticipantPhoto(
        @Path("id") id: Int
    ): Response<ResponseBody>

    /**
     * Daftarkan wajah peserta baru dengan mengirimkan string base64.
     */
    @POST("api/mobile/participants/{id}/register-face")
    suspend fun registerFace(
        @Path("id") id: Int,
        @Body body: RegisterFaceRequest
    ): Response<RegisterFaceResponse>

    /** Sesi absensi yang sedang aktif */
    @GET("api/mobile/sessions/active")
    suspend fun getActiveSession(): Response<SessionResponse>

    /** Semua sesi (untuk picker manual) */
    @GET("api/mobile/sessions")
    suspend fun getAllSessions(): Response<SessionsResponse>

    /** Catat absensi ke server (single record) */
    @POST("api/mobile/attendance")
    suspend fun recordAttendance(
        @Body body: AttendanceRequest
    ): Response<AttendanceResponse>

    /** Ambil status barang registrasi (supplies) milik seorang peserta */
    @GET("api/mobile/participants/{id}/supplies")
    suspend fun getParticipantSupplies(
        @Path("id") participantId: Int
    ): Response<SuppliesResponse>

    /** Sinkronisasi barang registrasi yang diambil oleh seorang peserta */
    @POST("api/mobile/participants/{id}/supplies")
    suspend fun syncParticipantSupplies(
        @Path("id") participantId: Int,
        @Body body: SyncSuppliesRequest
    ): Response<SyncSuppliesResponse>

    /** Ambil semua kelompok peserta */
    @GET("api/mobile/groups")
    suspend fun getGroups(): Response<GroupsResponse>

    /** Tambah peserta baru */
    @POST("api/mobile/participants")
    suspend fun createParticipant(
        @Body body: CreateParticipantRequest
    ): Response<CreateParticipantResponse>

    /** Update data peserta */
    @PUT("api/mobile/participants/{id}")
    suspend fun updateParticipant(
        @Path("id") id: Int,
        @Body body: CreateParticipantRequest
    ): Response<CreateParticipantResponse>
}
