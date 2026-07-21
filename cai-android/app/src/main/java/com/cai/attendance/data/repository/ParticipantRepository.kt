package com.cai.attendance.data.repository

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.util.Log
import com.cai.attendance.data.local.dao.ParticipantDao
import com.cai.attendance.data.local.entity.ParticipantEntity
import com.cai.attendance.data.remote.ApiService
import com.cai.attendance.data.remote.dto.CreateParticipantRequest
import com.cai.attendance.ml.FaceDetectorHelper
import com.cai.attendance.ml.FaceMatcher
import com.cai.attendance.ml.FaceNetModel
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import javax.inject.Inject
import javax.inject.Singleton

sealed class SyncResult {
    data class Success(
        val totalDownloaded: Int,
        val totalEmbedded: Int,
        val totalSkipped: Int
    ) : SyncResult()
    data class Error(val message: String) : SyncResult()
}

@Singleton
class ParticipantRepository @Inject constructor(
    @ApplicationContext private val context: Context,
    private val participantDao: ParticipantDao,
    private val apiService: ApiService,
) {
    companion object {
        private const val TAG = "ParticipantRepository"
        private const val PHOTOS_DIR = "face_photos"
    }

    val allParticipants: Flow<List<ParticipantEntity>> = participantDao.getAllFlow()

    suspend fun getParticipantsWithEmbedding(): List<ParticipantEntity> =
        participantDao.getAllWithEmbedding()

    suspend fun getStats(): Triple<Int, Int, Int> = withContext(Dispatchers.IO) {
        Triple(
            participantDao.count(),
            participantDao.countWithPhoto(),
            participantDao.countWithEmbedding()
        )
    }

    /**
     * Sync penuh: download semua peserta dari server, simpan foto lokal,
     * dan generate face embedding menggunakan FaceNet.
     *
     * @param sinceTimestamp  Jika diisi, hanya download peserta yang diperbarui setelah timestamp ini
     * @param onProgress      Callback untuk update progress (current, total, message)
     */
    suspend fun syncParticipants(
        sinceTimestamp: String? = null,
        onProgress: (current: Int, total: Int, message: String) -> Unit = { _, _, _ -> }
    ): SyncResult = withContext(Dispatchers.IO) {

        val faceNet = FaceNetModel(context)
        val faceDetector = FaceDetectorHelper()

        try {
            // 1. Fetch daftar peserta dari server (halaman per halaman)
            onProgress(0, 0, "Mengambil daftar peserta dari server…")

            val allParticipantDtos = mutableListOf<com.cai.attendance.data.remote.dto.ParticipantDto>()
            var currentPage = 1
            var lastPage = 1

            do {
                val response = apiService.getParticipants(
                    since   = sinceTimestamp,
                    page    = currentPage,
                    perPage = 100
                )

                if (!response.isSuccessful) {
                    val msg = "Server error: ${response.code()} ${response.message()}"
                    Log.e(TAG, msg)
                    return@withContext SyncResult.Error(msg)
                }

                val body = response.body() ?: return@withContext SyncResult.Error("Response kosong dari server")
                allParticipantDtos.addAll(body.data)
                lastPage = body.lastPage
                currentPage++
                onProgress(allParticipantDtos.size, body.total, "Mengambil data peserta…")

            } while (currentPage <= lastPage)

            val total = allParticipantDtos.size
            Log.d(TAG, "Total peserta dari server: $total")

            // 2. Download foto & generate embedding untuk setiap peserta
            var downloaded = 0
            var embedded = 0
            var skipped = 0

            for ((index, dto) in allParticipantDtos.withIndex()) {
                onProgress(index + 1, total, "Memproses: ${dto.name}")

                // Cek apakah foto sudah ada dan hash-nya sama (skip jika belum berubah)
                val existing = participantDao.getById(dto.id)
                val photoFile = getPhotoFile(dto.id)

                var photoPath: String? = existing?.photoPath
                var embeddingJson: String? = existing?.embeddingJson

                // Download foto jika:
                // - Belum ada foto lokal, ATAU
                // - Hash server berbeda dengan yang tersimpan (foto diperbarui)
                val needDownload = !photoFile.exists() || existing?.embeddingJson == null

                if (dto.hasPhoto && needDownload) {
                    try {
                        val photoResponse = apiService.getParticipantPhoto(dto.id)
                        if (photoResponse.isSuccessful) {
                            photoResponse.body()?.use { body ->
                                val bytes = body.bytes()
                                savePhotoFile(dto.id, bytes)
                                photoPath = photoFile.absolutePath
                                downloaded++
                                Log.d(TAG, "Downloaded photo for ${dto.name}")

                                // Generate embedding dari foto yang baru didownload
                                val bitmap = BitmapFactory.decodeByteArray(bytes, 0, bytes.size)
                                if (bitmap != null) {
                                    if (faceNet.isReady) {
                                        embeddingJson = generateEmbedding(
                                            bitmap, faceNet, dto.name
                                        )?.also { embedded++ }
                                    } else {
                                        Log.w(TAG, "FaceNet not ready saat download, skip embedding. Error: ${FaceNetModel.loadError}")
                                    }
                                }
                            }
                        } else {
                            Log.w(TAG, "Failed to download photo for ${dto.name}: ${photoResponse.code()}")
                            skipped++
                        }
                    } catch (e: Exception) {
                        Log.e(TAG, "Error downloading photo for ${dto.name}: ${e.message}")
                        skipped++
                    }
                }

                // Generate embedding dari foto lokal yang sudah ada (walau server bilang hasPhoto=false)
                // Ini menangani kasus: peserta sudah daftar wajah di HP ini tapi server belum punya foto
                if (photoFile.exists() && embeddingJson == null) {
                    if (faceNet.isReady) {
                        val bitmap = BitmapFactory.decodeFile(photoFile.absolutePath)
                        if (bitmap != null) {
                            embeddingJson = generateEmbedding(bitmap, faceNet, dto.name)
                                ?.also { embedded++ }
                            Log.d(TAG, "Generated embedding dari foto lokal: ${dto.name}")
                        }
                    } else {
                        Log.e(TAG, "FaceNet TIDAK siap! Embedding tidak bisa dibuat. Error: ${FaceNetModel.loadError}")
                    }
                }

                // Simpan ke database lokal
                val entity = ParticipantEntity(
                    id            = dto.id,
                    name          = dto.name,
                    nik           = dto.nik,
                    groupId       = dto.groupId,
                    groupName     = dto.groupName ?: "",
                    groupColor    = dto.groupColor ?: "#0052cc",
                    hasPhoto      = dto.hasPhoto,
                    faceRegistered = dto.faceRegistered,
                    photoPath     = photoPath,
                    embeddingJson = embeddingJson,
                    qrCode        = dto.qrCode,
                    updatedAt     = dto.updatedAt,
                    phone         = dto.phone ?: "",
                    gender        = dto.gender ?: "Laki-laki"
                )
                participantDao.insert(entity)
            }

            // 3. Bersihkan data peserta lokal yang sudah dihapus di server VPS (HANYA saat full sync)
            val serverIds = allParticipantDtos.map { it.id }
            if (sinceTimestamp == null && serverIds.isNotEmpty()) {
                val localParticipants = participantDao.getAll()
                for (local in localParticipants) {
                    if (local.id !in serverIds) {
                        val photoFile = getPhotoFile(local.id)
                        if (photoFile.exists()) {
                            photoFile.delete() // Hapus file foto dari internal storage
                        }
                    }
                }
                participantDao.deleteExceptIds(serverIds)
                Log.d(TAG, "Cleared deleted participants from local DB. Server IDs count: ${serverIds.size}")
            }

            Log.d(TAG, "Sync complete. Downloaded: $downloaded, Embedded: $embedded, Skipped: $skipped")
            SyncResult.Success(downloaded, embedded, skipped)

        } catch (e: Exception) {
            Log.e(TAG, "Sync error: ${e.message}", e)
            SyncResult.Error("Sync gagal: ${e.message}")
        } finally {
            faceNet.close()
            faceDetector.close()
        }
    }

    /**
     * Generate face embedding dari bitmap foto peserta.
     * Foto dari server sudah di-crop ke area wajah, tapi kita tetap
     * coba deteksi wajah ulang untuk memastikan alignment yang benar.
     */
    private fun generateEmbedding(
        bitmap: Bitmap,
        faceNet: FaceNetModel,
        name: String
    ): String? {
        return try {
            // Langsung generate embedding dari foto (foto server sudah crop wajah)
            val embedding = faceNet.getEmbedding(bitmap) ?: return null
            FaceMatcher.serializeEmbedding(embedding)
        } catch (e: Exception) {
            Log.e(TAG, "Failed to generate embedding for $name: ${e.message}")
            null
        }
    }

    private fun getPhotoFile(participantId: Int): File {
        val dir = File(context.filesDir, PHOTOS_DIR)
        if (!dir.exists()) dir.mkdirs()
        return File(dir, "$participantId.jpg")
    }

    private fun savePhotoFile(participantId: Int, bytes: ByteArray) {
        val file = getPhotoFile(participantId)
        FileOutputStream(file).use { it.write(bytes) }
    }

    fun getPhotoFilePath(participantId: Int): String =
        getPhotoFile(participantId).absolutePath

    /**
     * Mengunggah pendaftaran foto wajah baru ke Laravel server,
     * menyimpan foto secara lokal, menghasilkan embedding lokal secara real-time,
     * dan memperbarui status peserta di database Room lokal.
     */
    suspend fun uploadFaceRegistration(
        participantId: Int,
        bitmap: Bitmap,
        base64Image: String
    ): Result<String> = withContext(Dispatchers.IO) {
        val faceNet = FaceNetModel(context)
        try {
            // 1. Kirim ke Laravel API
            val request = com.cai.attendance.data.remote.dto.RegisterFaceRequest(image = base64Image)
            val response = apiService.registerFace(participantId, request)

            if (!response.isSuccessful) {
                return@withContext Result.failure(
                    Exception("Gagal mengunggah wajah ke server: ${response.code()} ${response.message()}")
                )
            }

            // 2. Deteksi wajah dan potong (crop) agar sesuai dengan format scanner absensi
            val faceDetector = FaceDetectorHelper()
            val faces = faceDetector.detectFaces(bitmap)
            val processedBitmap = if (faces.isNotEmpty()) {
                faceDetector.cropFace(bitmap, faces.first()) ?: bitmap
            } else {
                bitmap
            }
            faceDetector.close()

            // 3. Simpan foto wajah hasil crop secara lokal ke disk internal storage
            val photoFile = getPhotoFile(participantId)
            val outStream = FileOutputStream(photoFile)
            processedBitmap.compress(Bitmap.CompressFormat.JPEG, 90, outStream)
            outStream.flush()
            outStream.close()

            // 4. Hasilkan embedding secara lokal agar HP ini bisa langsung mendeteksi wajah tersebut
            var embeddingJson: String? = null
            if (faceNet.isReady) {
                // Gunakan processedBitmap (wajah hasil crop) agar embedding match dengan data scanner
                val embedding = faceNet.getEmbedding(processedBitmap)
                if (embedding != null) {
                    embeddingJson = FaceMatcher.serializeEmbedding(embedding)
                }
            }

            // 4. Perbarui status di Room database lokal
            participantDao.updateFaceRegistrationStatus(participantId, true)
            participantDao.updatePhotoPath(participantId, photoFile.absolutePath)
            if (embeddingJson != null) {
                participantDao.updateEmbedding(participantId, embeddingJson)
            }

            Result.success("Pendaftaran wajah berhasil disimpan!")
        } catch (e: Exception) {
            Log.e(TAG, "Error registering face on server: ${e.message}", e)
            Result.failure(e)
        } finally {
            faceNet.close()
        }
    }

    /** Ambil status barang registrasi peserta dari server */
    suspend fun getParticipantSupplies(participantId: Int): Result<List<com.cai.attendance.data.remote.dto.SupplyDto>> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getParticipantSupplies(participantId)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()?.data ?: emptyList())
            } else {
                Result.failure(Exception("Gagal mengambil data barang: ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    /** Update/sinkronisasi barang yang diambil peserta ke server */
    suspend fun syncParticipantSupplies(participantId: Int, supplyIds: List<Int>): Result<String> = withContext(Dispatchers.IO) {
        try {
            val request = com.cai.attendance.data.remote.dto.SyncSuppliesRequest(supplyIds)
            val response = apiService.syncParticipantSupplies(participantId, request)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()?.message ?: "Registrasi barang sukses diperbarui")
            } else {
                Result.failure(Exception("Gagal menyimpan registrasi barang: ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    /** Cari peserta lokal berdasarkan kode QR, NIK, atau ID */
    suspend fun findParticipantByCode(code: String): ParticipantEntity? = withContext(Dispatchers.IO) {
        val codeInt = code.toIntOrNull() ?: -1
        participantDao.findParticipantByCode(code, codeInt)
    }

    suspend fun getGroups(): Result<List<com.cai.attendance.data.remote.dto.GroupDto>> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getGroups()
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()?.data ?: emptyList())
            } else {
                Result.failure(Exception("Gagal mengambil data kelompok: ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun createParticipant(
        name: String,
        groupId: Int,
        gender: String,
        phone: String,
        qrCode: String?
    ): Result<ParticipantEntity> = withContext(Dispatchers.IO) {
        try {
            val request = com.cai.attendance.data.remote.dto.CreateParticipantRequest(
                name = name,
                groupId = groupId,
                gender = gender,
                phone = phone,
                qrCode = qrCode
            )
            val response = apiService.createParticipant(request)
            if (response.isSuccessful && response.body()?.success == true) {
                val dto = response.body()?.participant ?: return@withContext Result.failure(Exception("Data peserta kosong"))
                
                val entity = ParticipantEntity(
                    id = dto.id,
                    name = dto.name,
                    nik = dto.nik,
                    groupId = dto.groupId,
                    groupName = dto.groupName ?: "",
                    groupColor = dto.groupColor ?: "#0052cc",
                    hasPhoto = dto.hasPhoto,
                    faceRegistered = dto.faceRegistered,
                    photoPath = null,
                    embeddingJson = null,
                    qrCode = dto.qrCode,
                    updatedAt = dto.updatedAt,
                    phone = dto.phone ?: "",
                    gender = dto.gender ?: "Laki-laki"
                )
                participantDao.insert(entity)
                Result.success(entity)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Gagal menambah peserta di server: ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateParticipant(
        id: Int,
        name: String,
        groupId: Int,
        gender: String,
        phone: String,
        qrCode: String?
    ): Result<ParticipantEntity> = withContext(Dispatchers.IO) {
        try {
            val request = CreateParticipantRequest(
                name = name,
                groupId = groupId,
                gender = gender,
                phone = phone,
                qrCode = qrCode
            )
            val response = apiService.updateParticipant(id, request)
            if (response.isSuccessful && response.body()?.success == true) {
                val dto = response.body()?.participant ?: return@withContext Result.failure(Exception("Data peserta kosong"))
                
                val existing = participantDao.getById(id)
                val entity = ParticipantEntity(
                    id = dto.id,
                    name = dto.name,
                    nik = dto.nik,
                    groupId = dto.groupId,
                    groupName = dto.groupName ?: "",
                    groupColor = dto.groupColor ?: "#0052cc",
                    hasPhoto = dto.hasPhoto,
                    faceRegistered = dto.faceRegistered,
                    photoPath = existing?.photoPath,
                    embeddingJson = existing?.embeddingJson,
                    qrCode = dto.qrCode,
                    updatedAt = dto.updatedAt,
                    phone = dto.phone ?: "",
                    gender = dto.gender ?: "Laki-laki"
                )
                participantDao.insert(entity)
                Result.success(entity)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Gagal memperbarui peserta di server: ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
