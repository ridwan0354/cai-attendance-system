package com.cai.attendance.ml

import android.content.Context
import android.graphics.Bitmap
import android.util.Log
import org.tensorflow.lite.Interpreter
import org.tensorflow.lite.flex.FlexDelegate
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

        // Diagnostik error jika gagal load model
        var loadError: String? = null
    }

    private var interpreter: Interpreter? = null
    private var flexDelegate: FlexDelegate? = null
    val isReady: Boolean get() = interpreter != null

    init {
        try {
            // FlexDelegate dibutuhkan untuk model yang menggunakan ops non-standar TFLite
            // seperti FaceNet (BatchMatMulV2, dll)
            val delegate = FlexDelegate()
            flexDelegate = delegate

            val options = Interpreter.Options().apply {
                addDelegate(delegate)
                setNumThreads(4)
            }
            // Membaca model menggunakan direct ByteBuffer (aman dari kompresi gradle/AAPT)
            val modelBuffer = loadModelFile(context)
            interpreter = Interpreter(modelBuffer, options)
            loadError = null
            Log.d(TAG, "FaceNet model loaded successfully with FlexDelegate")
        } catch (e: Exception) {
            loadError = e.message ?: e.toString()
            Log.e(TAG, "Failed to load FaceNet model: ${e.message}", e)
            Log.e(TAG, "Pastikan file facenet.tflite ada di assets/")
        }
    }


    /**
     * Menghasilkan embedding 512-d dari bitmap wajah.
     * @param faceBitmap Bitmap wajah yang sudah di-crop (akan di-resize ke 160×160)
     * @return FloatArray 512 dimensi, atau null jika model belum siap
     */
    fun getEmbedding(faceBitmap: Bitmap): FloatArray? {
        val interpreter = this.interpreter ?: return null

        // Resize ke 160×160
        val resized = Bitmap.createScaledBitmap(faceBitmap, INPUT_SIZE, INPUT_SIZE, true)

        // Konversi ke ByteBuffer
        val inputBuffer = bitmapToByteBuffer(resized)

        // Output: [1, 512] float
        val outputArray = Array(1) { FloatArray(EMBEDDING_SIZE) }

        try {
            interpreter.run(inputBuffer, outputArray)
        } catch (e: Exception) {
            Log.e(TAG, "Inference error: ${e.message}")
            return null
        }

        // L2 normalize embedding
        val embedding = outputArray[0]
        return l2Normalize(embedding)
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
        flexDelegate?.close()
        flexDelegate = null
    }
}
