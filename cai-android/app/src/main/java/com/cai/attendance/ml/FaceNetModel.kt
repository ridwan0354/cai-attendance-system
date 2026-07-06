package com.cai.attendance.ml

import android.content.Context
import android.graphics.Bitmap
import android.util.Log
import org.tensorflow.lite.Interpreter
import java.io.InputStream
import java.nio.ByteBuffer
import java.nio.ByteOrder
import kotlin.math.sqrt

/**
 * Wrapper untuk model FaceNet TFLite.
 *
 * Model mengkonversi gambar wajah 160×160 pixel menjadi vektor embedding
 * 512 dimensi. Embedding ini kemudian dibandingkan dengan embedding yang
 * tersimpan di database lokal menggunakan cosine similarity.
 *
 * Model harus diletakkan di: app/src/main/assets/facenet.tflite
 */
class FaceNetModel(context: Context) {

    companion object {
        private const val TAG = "FaceNetModel"
        private const val MODEL_FILE  = "facenet.tflite"
        private const val INPUT_SIZE  = 160   // 160×160 pixel
        private const val EMBEDDING_SIZE = 512
        private const val CHANNELS    = 3    // RGB
        private const val PIXEL_BYTES = 4    // float32

        // Diagnostik error jika gagal load atau run model
        var loadError: String? = null
        var inferenceError: String? = null
    }

    private var interpreter: Interpreter? = null
    val isReady: Boolean get() = interpreter != null

    init {
        try {
            val options = Interpreter.Options().apply {
                setNumThreads(4)
            }
            val modelBuffer = loadModelFile(context)
            interpreter = Interpreter(modelBuffer, options)
            loadError = null
            Log.d(TAG, "FaceNet model loaded successfully. Input tensors: ${interpreter!!.inputTensorCount}")
        } catch (e: Exception) {
            loadError = e.message ?: e.toString()
            Log.e(TAG, "Failed to load FaceNet model: ${e.message}", e)
        }
    }

    /**
     * Menghasilkan embedding 512-d dari bitmap wajah.
     */
    fun getEmbedding(faceBitmap: Bitmap): FloatArray? {
        val interpreter = this.interpreter ?: run {
            Log.e(TAG, "Interpreter null saat getEmbedding")
            return null
        }

        val resized = Bitmap.createScaledBitmap(faceBitmap, INPUT_SIZE, INPUT_SIZE, true)
        val inputBuffer = bitmapToByteBuffer(resized)
        val outputArray = Array(1) { FloatArray(EMBEDDING_SIZE) }

        return try {
            interpreter.run(inputBuffer, outputArray)
            inferenceError = null
            l2Normalize(outputArray[0])
        } catch (e: Exception) {
            inferenceError = e.message ?: e.toString()
            Log.e(TAG, "Inference error: ${e.message}", e)
            null
        }
    }


    /**
     * Konversi bitmap ke ByteBuffer yang dinormalisasi ke range [-1, 1].
     */
    private fun bitmapToByteBuffer(bitmap: Bitmap): ByteBuffer {
        val buffer = ByteBuffer.allocateDirect(
            1 * INPUT_SIZE * INPUT_SIZE * CHANNELS * PIXEL_BYTES
        ).apply { order(ByteOrder.nativeOrder()) }

        val pixels = IntArray(INPUT_SIZE * INPUT_SIZE)
        bitmap.getPixels(pixels, 0, INPUT_SIZE, 0, 0, INPUT_SIZE, INPUT_SIZE)

        for (pixel in pixels) {
            val r = ((pixel shr 16) and 0xFF).toFloat()
            val g = ((pixel shr 8)  and 0xFF).toFloat()
            val b = (pixel          and 0xFF).toFloat()

            // Normalize ke [-1, 1]
            buffer.putFloat((r - 127.5f) / 128.0f)
            buffer.putFloat((g - 127.5f) / 128.0f)
            buffer.putFloat((b - 127.5f) / 128.0f)
        }

        buffer.rewind()
        return buffer
    }

    /** L2 normalisasi agar cosine similarity setara dengan dot product */
    private fun l2Normalize(embedding: FloatArray): FloatArray {
        var norm = 0f
        for (v in embedding) norm += v * v
        norm = sqrt(norm)
        if (norm == 0f) return embedding
        return FloatArray(embedding.size) { embedding[it] / norm }
    }

    /**
     * Membaca file asset secara langsung sebagai byte array dan membungkusnya
     * dalam direct ByteBuffer. Ini menghindari error MappedByteBuffer jika file terkompresi.
     */
    private fun loadModelFile(context: Context): ByteBuffer {
        val inputStream: InputStream = context.assets.open(MODEL_FILE)
        val bytes = inputStream.readBytes()
        inputStream.close()

        val buffer = ByteBuffer.allocateDirect(bytes.size).apply {
            order(ByteOrder.nativeOrder())
            put(bytes)
            rewind()
        }
        return buffer
    }

    fun close() {
        interpreter?.close()
        interpreter = null
    }
}
