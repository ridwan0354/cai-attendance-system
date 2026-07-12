package com.cai.attendance.ui.screen

import android.Manifest
import android.graphics.Bitmap
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import com.cai.attendance.ui.component.CameraPreviewView
import com.cai.attendance.ui.theme.*
import com.cai.attendance.ui.viewmodel.ScanResult
import com.cai.attendance.ui.viewmodel.ScannerViewModel

enum class ScanMode { FACE, QR }

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ScannerScreen(
    onNavigateBack: () -> Unit,
    viewModel: ScannerViewModel = hiltViewModel()
) {
    val context      = LocalContext.current
    val scanResult   by viewModel.scanResult.collectAsState()
    val activeSession by viewModel.activeSession.collectAsState()
    val isProcessing by viewModel.isProcessing.collectAsState()

    // Mode scan: FACE (kamera depan) atau QR (kamera belakang)
    var scanMode by remember { mutableStateOf(ScanMode.FACE) }

    // Permission state
    var hasCameraPermission by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA)
                == android.content.pm.PackageManager.PERMISSION_GRANTED
        )
    }

    val permissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted -> hasCameraPermission = granted }

    LaunchedEffect(Unit) {
        if (!hasCameraPermission) {
            permissionLauncher.launch(Manifest.permission.CAMERA)
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Scanner Wajah", fontWeight = FontWeight.Bold)
                        activeSession?.let {
                            Text(
                                "Sesi: ${it.name} (Hari ${it.dayNumber})",
                                style = MaterialTheme.typography.labelSmall,
                                color = CaiAccent
                            )
                        } ?: Text(
                            "Tidak ada sesi aktif",
                            style = MaterialTheme.typography.labelSmall,
                            color = CaiError
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Kembali")
                    }
                },
                actions = {
                    // Tombol toggle mode scan
                    IconButton(onClick = {
                        scanMode = if (scanMode == ScanMode.FACE) ScanMode.QR else ScanMode.FACE
                        viewModel.resetResult()
                    }) {
                        Icon(
                            if (scanMode == ScanMode.FACE) Icons.Default.QrCode else Icons.Default.Face,
                            contentDescription = "Toggle Scan Mode",
                            tint = CaiAccent
                        )
                    }
                    IconButton(onClick = viewModel::refreshSession) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh Sesi", tint = CaiAccent)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = CaiNavy,
                    titleContentColor = CaiTextPrimary
                )
            )
        },
        containerColor = Color.Black
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            if (!hasCameraPermission) {
                // ── Tampilan minta izin kamera ────────────────────────────
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(CaiNavy),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.padding(32.dp)
                    ) {
                        Icon(
                            Icons.Default.CameraAlt,
                            contentDescription = null,
                            modifier = Modifier.size(64.dp),
                            tint = CaiTextSecondary
                        )
                        Spacer(Modifier.height(16.dp))
                        Text(
                            "Izin kamera diperlukan\nuntuk scan wajah",
                            style = MaterialTheme.typography.titleMedium,
                            textAlign = TextAlign.Center,
                            color = CaiTextPrimary
                        )
                        Spacer(Modifier.height(16.dp))
                        Button(onClick = { permissionLauncher.launch(Manifest.permission.CAMERA) }) {
                            Text("Beri Izin Kamera")
                        }
                    }
                }
                return@Scaffold
            }

            // ── Preview Kamera (Full Screen) ──────────────────────────────
            CameraPreviewView(
                modifier = Modifier.fillMaxSize(),
                useFrontCamera = (scanMode == ScanMode.FACE),
                onFrameReady = { bitmap: Bitmap ->
                    viewModel.processFrame(bitmap)
                }
            )

            // ── Overlay dark gradient bawah ───────────────────────────────
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .align(Alignment.BottomCenter)
                    .height(220.dp)
                    .background(
                        Brush.verticalGradient(
                            listOf(Color.Transparent, Color.Black.copy(alpha = 0.85f))
                        )
                    )
            )

            // ── Panduan scan (target box) ─────────────────────────────────
            Box(
                modifier = Modifier
                    .size(220.dp)
                    .align(Alignment.Center)
                    .offset(y = (-40).dp)
                    .border(
                        width = 2.dp,
                        color = when (scanResult) {
                            is ScanResult.Recognized -> CaiSuccess
                            is ScanResult.Unknown    -> CaiError
                            else                     -> CaiAccent.copy(alpha = 0.7f)
                        },
                        shape = RoundedCornerShape(16.dp)
                    )
            )

            // ── Label mode aktif ──────────────────────────────────────────
            Surface(
                modifier = Modifier
                    .align(Alignment.TopCenter)
                    .padding(top = 8.dp),
                color = if (scanMode == ScanMode.QR) CaiAccent.copy(alpha = 0.85f) else CaiNavy.copy(alpha = 0.75f),
                shape = RoundedCornerShape(20.dp)
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 6.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(
                        if (scanMode == ScanMode.QR) Icons.Default.QrCode else Icons.Default.Face,
                        contentDescription = null,
                        tint = Color.White,
                        modifier = Modifier.size(16.dp)
                    )
                    Spacer(Modifier.width(6.dp))
                    Text(
                        if (scanMode == ScanMode.QR) "Mode QR — arahkan kamera ke kartu QR" else "Mode Wajah — hadapkan wajah ke kamera",
                        style = MaterialTheme.typography.labelSmall,
                        color = Color.White
                    )
                }
            }

            // ── Status Result Card ────────────────────────────────────────
            AnimatedContent(
                targetState = scanResult,
                transitionSpec = {
                    slideInVertically { it } + fadeIn() togetherWith
                    slideOutVertically { it } + fadeOut()
                },
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .padding(16.dp)
                    .fillMaxWidth(),
                label = "scanResult"
            ) { result ->
                when (result) {
                    is ScanResult.Recognized ->
                        RecognizedCard(result, if (scanMode == ScanMode.QR) "QR" else "Wajah")
                    is ScanResult.Unknown    -> UnknownCard(result)
                    is ScanResult.NoFace     ->
                        HintCard(if (scanMode == ScanMode.QR) "Arahkan kamera ke kode QR peserta" else "Arahkan wajah ke kamera")
                    is ScanResult.ModelNotReady -> ErrorCard("Model FaceNet belum siap. Pastikan file facenet.tflite ada di assets/")
                    is ScanResult.NoActiveSession -> ErrorCard("Tidak ada sesi aktif. Aktifkan sesi di server terlebih dahulu.")
                    is ScanResult.Error      -> ErrorCard(result.message)
                    is ScanResult.Scanning   -> ProcessingCard()
                    else                     ->
                        HintCard(if (scanMode == ScanMode.QR) "Tunjukkan kartu QR ke kamera belakang" else "Arahkan wajah ke area kotak di atas")
                }
            }

            // Loading indicator
            if (isProcessing) {
                LinearProgressIndicator(
                    modifier = Modifier
                        .fillMaxWidth()
                        .align(Alignment.TopCenter),
                    color = CaiAccent
                )
            }
        }
    }
}

@Composable
private fun RecognizedCard(result: ScanResult.Recognized, method: String = "Wajah") {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors   = CardDefaults.cardColors(
            containerColor = CaiSuccess.copy(alpha = 0.15f)
        ),
        shape    = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                Icons.Default.CheckCircle,
                contentDescription = null,
                tint     = CaiSuccess,
                modifier = Modifier.size(40.dp)
            )
            Spacer(Modifier.width(12.dp))
            Column(Modifier.weight(1f)) {
                Text(
                    result.participantName,
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = CaiTextPrimary
                )
                Text(
                    result.groupName,
                    style = MaterialTheme.typography.bodyMedium,
                    color = CaiTextSecondary
                )
                Text(
                    "Confidence: ${result.confidence.toInt()}% · ${method}",
                    style = MaterialTheme.typography.labelSmall,
                    color = CaiSuccess
                )
            }
            Text("✓", fontSize = 28.sp, color = CaiSuccess)
        }
    }
}

@Composable
private fun UnknownCard(result: ScanResult.Unknown) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors   = CardDefaults.cardColors(containerColor = CaiError.copy(alpha = 0.15f)),
        shape    = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(Icons.Default.PersonOff, contentDescription = null, tint = CaiError, modifier = Modifier.size(40.dp))
            Spacer(Modifier.width(12.dp))
            Column {
                Text("Wajah Tidak Dikenal", style = MaterialTheme.typography.titleMedium, color = CaiError)
                Text("Peserta belum terdaftar atau foto belum disync", style = MaterialTheme.typography.bodyMedium, color = CaiTextSecondary)
            }
        }
    }
}

@Composable
private fun HintCard(message: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors   = CardDefaults.cardColors(containerColor = CaiNavy.copy(alpha = 0.8f)),
        shape    = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(Icons.Default.Face, contentDescription = null, tint = CaiAccent, modifier = Modifier.size(32.dp))
            Spacer(Modifier.width(12.dp))
            Text(message, style = MaterialTheme.typography.bodyMedium, color = CaiTextSecondary)
        }
    }
}

@Composable
private fun ProcessingCard() {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors   = CardDefaults.cardColors(containerColor = CaiNavy.copy(alpha = 0.8f)),
        shape    = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            CircularProgressIndicator(modifier = Modifier.size(24.dp), color = CaiAccent, strokeWidth = 2.dp)
            Spacer(Modifier.width(12.dp))
            Text("Memproses wajah…", color = CaiTextSecondary)
        }
    }
}

@Composable
private fun ErrorCard(message: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors   = CardDefaults.cardColors(containerColor = CaiError.copy(alpha = 0.15f)),
        shape    = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(Icons.Default.Error, contentDescription = null, tint = CaiError)
            Spacer(Modifier.width(8.dp))
            Text(message, style = MaterialTheme.typography.bodyMedium, color = CaiError)
        }
    }
}
