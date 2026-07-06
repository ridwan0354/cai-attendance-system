package com.cai.attendance.ml

import com.cai.attendance.data.local.entity.ParticipantEntity
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import kotlin.math.max

/**
 * Membandingkan embedding wajah yang baru di-scan dengan semua embedding
 * yang tersimpan di database lokal menggunakan cosine similarity.
 */
object FaceMatcher {

    private val gson = Gson()

    /**
     * Threshold minimum untuk dianggap cocok.
     * Nilai 0.65 artinya: cosine similarity >= 0.65 dianggap wajah yang sama.
     * Bisa dinaikkan (lebih ketat) atau diturunkan (lebih longgar) sesuai kebutuhan.
     */
    const val DEFAULT_THRESHOLD = 0.65f

    data class MatchResult(
        val participant: ParticipantEntity,
        val similarity: Float,
        val isMatch: Boolean
    )

    /**
     * Cari peserta yang paling mirip dengan embedding yang diberikan.
     * @param queryEmbedding  Embedding wajah hasil scan (512-d float array)
     * @param candidates      Semua peserta yang punya embedding di DB lokal
     * @param threshold       Minimum similarity untuk dianggap cocok
     * @return MatchResult terbaik, atau null jika tidak ada yang melewati threshold
     */
    fun findBestMatch(
        queryEmbedding: FloatArray,
        candidates: List<ParticipantEntity>,
        threshold: Float = DEFAULT_THRESHOLD
    ): MatchResult? {
        if (candidates.isEmpty()) return null

        var bestMatch: ParticipantEntity? = null
        var bestSimilarity = -1f

        for (candidate in candidates) {
            val storedEmbedding = deserializeEmbedding(candidate.embeddingJson ?: continue)
                ?: continue

            val similarity = cosineSimilarity(queryEmbedding, storedEmbedding)

            if (similarity > bestSimilarity) {
                bestSimilarity = similarity
                bestMatch = candidate
            }
        }

        val participant = bestMatch ?: return null

        return MatchResult(
            participant  = participant,
            similarity   = bestSimilarity,
            isMatch      = bestSimilarity >= threshold
        )
    }

    /**
     * Cosine similarity antara dua vektor.
     * Hasil range: [-1, 1]. Semakin mendekati 1, semakin mirip.
     * Karena embeddings sudah L2-normalized di FaceNetModel, ini setara
     * dengan dot product.
     */
    fun cosineSimilarity(a: FloatArray, b: FloatArray): Float {
        if (a.size != b.size) return 0f
        var dot = 0f
        for (i in a.indices) {
            dot += a[i] * b[i]
        }
        // Karena sudah L2-normalized, norm = 1, jadi similarity = dot
        return dot.coerceIn(-1f, 1f)
    }

    /** Serialisasi FloatArray ke JSON string untuk disimpan di Room */
    fun serializeEmbedding(embedding: FloatArray): String =
        gson.toJson(embedding)

    /** Deserialisasi JSON string kembali ke FloatArray */
    fun deserializeEmbedding(json: String): FloatArray? {
        return try {
            val type = object : TypeToken<FloatArray>() {}.type
            gson.fromJson(json, type)
        } catch (e: Exception) {
            null
        }
    }

    /** Konversi similarity ke persentase confidence yang lebih mudah dibaca */
    fun similarityToConfidence(similarity: Float): Float =
        max(0f, (similarity - 0.5f) / 0.5f * 100f)
}
