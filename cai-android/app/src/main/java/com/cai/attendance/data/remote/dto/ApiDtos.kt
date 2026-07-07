package com.cai.attendance.data.remote.dto

import com.google.gson.annotations.SerializedName

/** Response dari GET /api/mobile/participants */
data class ParticipantsResponse(
    @SerializedName("success")     val success: Boolean,
    @SerializedName("data")        val data: List<ParticipantDto>,
    @SerializedName("total")       val total: Int,
    @SerializedName("page")        val page: Int,
    @SerializedName("last_page")   val lastPage: Int,
    @SerializedName("server_time") val serverTime: String
)

data class ParticipantDto(
    @SerializedName("id")              val id: Int,
    @SerializedName("name")            val name: String,
    @SerializedName("nik")             val nik: String?,
    @SerializedName("group_id")        val groupId: Int,
    @SerializedName("group_name")      val groupName: String?,
    @SerializedName("group_color")     val groupColor: String?,
    @SerializedName("face_registered") val faceRegistered: Boolean,
    @SerializedName("has_photo")       val hasPhoto: Boolean,
    @SerializedName("photo_hash")      val photoHash: String?,
    @SerializedName("updated_at")      val updatedAt: String
)

/** Response dari GET /api/mobile/sessions/active */
data class SessionResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("session") val session: SessionDto?,
    @SerializedName("message") val message: String?
)

data class SessionDto(
    @SerializedName("id")          val id: Int,
    @SerializedName("name")        val name: String,
    @SerializedName("day_number")  val dayNumber: Int,
    @SerializedName("date")        val date: String,
    @SerializedName("start_time")  val startTime: String,
    @SerializedName("end_time")    val endTime: String,
    @SerializedName("is_active")   val isActive: Boolean
)

data class SessionsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data")    val data: List<SessionDto>
)

/** Request body untuk POST /api/mobile/attendance */
data class AttendanceRequest(
    @SerializedName("participant_id")   val participantId: Int,
    @SerializedName("session_id")       val sessionId: Int,
    @SerializedName("method")           val method: String,
    @SerializedName("confidence_score") val confidenceScore: Float?,
    @SerializedName("check_in_time")    val checkInTime: String
)

/** Response dari POST /api/mobile/attendance */
data class AttendanceResponse(
    @SerializedName("success")         val success: Boolean,
    @SerializedName("already_present") val alreadyPresent: Boolean?,
    @SerializedName("message")         val message: String?,
    @SerializedName("attendance")      val attendance: AttendanceDto?
)

data class AttendanceDto(
    @SerializedName("id")                val id: Int?,
    @SerializedName("participant_name")  val participantName: String?,
    @SerializedName("group_name")        val groupName: String?,
    @SerializedName("check_in_time")     val checkInTime: String?
)

/** Response dari GET /api/mobile/sync/info */
data class SyncInfoResponse(
    @SerializedName("success")              val success: Boolean,
    @SerializedName("total_participants")   val totalParticipants: Int,
    @SerializedName("total_with_photo")     val totalWithPhoto: Int,
    @SerializedName("last_updated")         val lastUpdated: String?,
    @SerializedName("server_time")          val serverTime: String,
    @SerializedName("server_version")       val serverVersion: String?
)

/** Request body untuk POST /api/mobile/participants/{id}/register-face */
data class RegisterFaceRequest(
    @SerializedName("image") val image: String // base64 string
)

/** Response dari POST /api/mobile/participants/{id}/register-face */
data class RegisterFaceResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String?
)

// ── Model Aset Registrasi Barang (Supplies) ──────────────────────────────────
data class SupplyDto(
    @SerializedName("id")       val id: Int,
    @SerializedName("name")     val name: String,
    @SerializedName("received") val received: Boolean = false
)

data class SuppliesResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data")    val data: List<SupplyDto>
)

data class SyncSuppliesRequest(
    @SerializedName("supplies") val supplies: List<Int>
)

data class SyncSuppliesResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String?
)


