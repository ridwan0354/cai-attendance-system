package com.cai.attendance.ui.component

import android.content.Context
import android.graphics.Bitmap
import android.graphics.Matrix
import androidx.camera.core.*
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import androidx.lifecycle.LifecycleOwner
import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors

/**
 * Composable yang menampilkan preview kamera real-time menggunakan CameraX.
 * Mengirimkan frame bitmap ke [onFrameReady] setiap ~500ms untuk diproses.
 */
@Composable
fun CameraPreviewView(
    modifier: Modifier = Modifier,
    onFrameReady: (Bitmap) -> Unit,
) {
    val executor: ExecutorService = remember { Executors.newSingleThreadExecutor() }

    DisposableEffect(Unit) {
        onDispose { executor.shutdown() }
    }

    AndroidView(
        modifier = modifier,
        factory  = { context ->
            val previewView = PreviewView(context).apply {
                implementationMode = PreviewView.ImplementationMode.COMPATIBLE
                scaleType = PreviewView.ScaleType.FILL_CENTER
            }
            startCamera(context, previewView, executor, onFrameReady)
            previewView
        }
    )
}

private fun startCamera(
    context: Context,
    previewView: PreviewView,
    executor: ExecutorService,
    onFrameReady: (Bitmap) -> Unit
) {
    val cameraProviderFuture = ProcessCameraProvider.getInstance(context)

    cameraProviderFuture.addListener({
        val cameraProvider = cameraProviderFuture.get()

        val preview = Preview.Builder()
            .setTargetAspectRatio(AspectRatio.RATIO_4_3)
            .build()
            .also { it.setSurfaceProvider(previewView.surfaceProvider) }

        // Frame analyzer: kirim setiap 2 frame (throttle)
        var frameCount = 0
        val imageAnalysis = ImageAnalysis.Builder()
            .setTargetAspectRatio(AspectRatio.RATIO_4_3)
            .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
            .setOutputImageFormat(ImageAnalysis.OUTPUT_IMAGE_FORMAT_RGBA_8888)
            .build()
            .also { analysis ->
                analysis.setAnalyzer(executor) { imageProxy ->
                    frameCount++
                    // Proses setiap 3 frame (~500ms pada 6fps analyzer)
                    if (frameCount % 3 == 0) {
                        val bitmap = imageProxy.toBitmap()
                        val rotated = rotateBitmap(bitmap, imageProxy.imageInfo.rotationDegrees)
                        onFrameReady(rotated)
                    }
                    imageProxy.close()
                }
            }

        try {
            cameraProvider.unbindAll()
            cameraProvider.bindToLifecycle(
                previewView.context as LifecycleOwner,
                CameraSelector.DEFAULT_FRONT_CAMERA, // Kamera depan untuk absensi
                preview,
                imageAnalysis
            )
        } catch (e: Exception) {
            // Fallback ke kamera belakang jika kamera depan tidak ada
            try {
                cameraProvider.bindToLifecycle(
                    previewView.context as LifecycleOwner,
                    CameraSelector.DEFAULT_BACK_CAMERA,
                    preview,
                    imageAnalysis
                )
            } catch (ex: Exception) {
                // Kamera tidak tersedia
            }
        }
    }, ContextCompat.getMainExecutor(context))
}

private fun rotateBitmap(bitmap: Bitmap, degrees: Int): Bitmap {
    if (degrees == 0) return bitmap
    val matrix = Matrix().apply { postRotate(degrees.toFloat()) }
    return Bitmap.createBitmap(bitmap, 0, 0, bitmap.width, bitmap.height, matrix, true)
}
