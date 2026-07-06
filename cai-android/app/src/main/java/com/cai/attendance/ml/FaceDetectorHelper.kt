package com.cai.attendance.ml

import android.graphics.Bitmap
import android.util.Log
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.face.Face
import com.google.mlkit.vision.face.FaceDetection
import com.google.mlkit.vision.face.FaceDetectorOptions
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlin.coroutines.resume

/**
 * Wrapper untuk Google ML Kit Face Detector.
 * Mendeteksi wajah dalam frame kamera secara real-time.
 */
class FaceDetectorHelper {

    companion object {
        private const val TAG = "FaceDetectorHelper"
        private const val MIN_FACE_SIZE = 0.15f  // minimum 15% dari frame
    }

    private val detector = FaceDetection.getClient(
        FaceDetectorOptions.Builder()
            .setPerformanceMode(FaceDetectorOptions.PERFORMANCE_MODE_FAST)
            .setMinFaceSize(MIN_FACE_SIZE)
            .build()
    )

    /**
     * Deteksi wajah dari objek Bitmap yang sudah dirotasi.
     * @return List wajah yang terdeteksi
     */
    suspend fun detectFaces(bitmap: Bitmap): List<Face> =
        suspendCancellableCoroutine { continuation ->
            try {
                val image = InputImage.fromBitmap(bitmap, 0)
                detector.process(image)
                    .addOnSuccessListener { faces ->
                        continuation.resume(faces)
                    }
                    .addOnFailureListener { e ->
                        Log.e(TAG, "Face detection failed: ${e.message}")
                        continuation.resume(emptyList())
                    }
            } catch (e: Exception) {
                Log.e(TAG, "InputImage creation failed: ${e.message}")
                continuation.resume(emptyList())
            }
        }

    /**
     * Crop wajah dari bitmap berdasarkan bounding box ML Kit.
     * Menambahkan padding 15% agar wajah tidak terlalu mepet ke tepi.
     */
    fun cropFace(bitmap: Bitmap, face: Face): Bitmap? {
        val box = face.boundingBox
        val padding = (box.width() * 0.15f).toInt()

        val left   = (box.left   - padding).coerceAtLeast(0)
        val top    = (box.top    - padding).coerceAtLeast(0)
        val right  = (box.right  + padding).coerceAtMost(bitmap.width)
        val bottom = (box.bottom + padding).coerceAtMost(bitmap.height)

        val width  = right - left
        val height = bottom - top

        if (width <= 0 || height <= 0) return null

        return try {
            Bitmap.createBitmap(bitmap, left, top, width, height)
        } catch (e: Exception) {
            Log.e(TAG, "Crop failed: ${e.message}")
            null
        }
    }

    fun close() {
        detector.close()
    }
}
