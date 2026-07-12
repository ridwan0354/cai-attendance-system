package com.cai.attendance.ml

import android.graphics.Bitmap
import android.util.Log
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlin.coroutines.resume

/**
 * Wrapper untuk Google ML Kit Barcode/QR Code Scanner.
 * Mendeteksi kode QR dari frame kamera secara real-time.
 */
class BarcodeScannerHelper {

    companion object {
        private const val TAG = "BarcodeScannerHelper"
    }

    private val scanner = BarcodeScanning.getClient()

    /**
     * Scan barcode/QR dari bitmap.
     * @return List barcode yang terdeteksi
     */
    suspend fun scanBarcodes(bitmap: Bitmap): List<Barcode> =
        suspendCancellableCoroutine { continuation ->
            try {
                val image = InputImage.fromBitmap(bitmap, 0)
                scanner.process(image)
                    .addOnSuccessListener { barcodes ->
                        continuation.resume(barcodes)
                    }
                    .addOnFailureListener { e ->
                        Log.e(TAG, "Barcode scanning failed: ${e.message}")
                        continuation.resume(emptyList())
                    }
            } catch (e: Exception) {
                Log.e(TAG, "InputImage creation failed: ${e.message}")
                continuation.resume(emptyList())
            }
        }

    fun close() {
        scanner.close()
    }
}
