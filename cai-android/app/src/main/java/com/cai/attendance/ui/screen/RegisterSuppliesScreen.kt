package com.cai.attendance.ui.screen

import android.Manifest
import android.graphics.Bitmap
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
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
import com.cai.attendance.ui.viewmodel.SuppliesScanResult
import com.cai.attendance.ui.viewmodel.RegisterSuppliesViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RegisterSuppliesScreen(
    onNavigateBack: () -> Unit,
    viewModel: RegisterSuppliesViewModel = hiltViewModel()
) {
    val context      = LocalContext.current
    val scanResult   by viewModel.scanResult.collectAsState()
    val isProcessing by viewModel.isProcessing.collectAsState()

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

    // Success dialog popup
    if (scanResult is SuppliesScanResult.Success) {
        val successData = scanResult as SuppliesScanResult.Success
        AlertDialog(
            onDismissRequest = { viewModel.forceReset() },
            title = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        Icons.Default.Giftcard,
                        contentDescription = null,
                        tint = CaiSuccess,
                        modifier = Modifier.size(32.dp)
                    )
                    Spacer(Modifier.width(8.dp))
                    Text(
                        "Registrasi Barang Berhasil!",
                        fontWeight = FontWeight.Bold,
                        style = MaterialTheme.typography.titleMedium,
                        color = CaiTextPrimary
                    )
                }
            },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Column {
                        Text(
                            text = successData.participantName,
                            style = MaterialTheme.typography.titleLarge,
                            fontWeight = FontWeight.Bold,
                            color = CaiTextPrimary
                        )
                        Text(
                            text = "Kelompok: ${successData.groupName}",
                            style = MaterialTheme.typography.bodyMedium,
                            color = CaiTextSecondary
                        )
                    }

                    HorizontalDivider(color = CaiBorder)

                    Text(
                        text = "Barang yang diperoleh:",
                        fontWeight = FontWeight.SemiBold,
                        style = MaterialTheme.typography.labelMedium,
                        color = CaiAccent
                    )

                    LazyColumn(
                        verticalArrangement = Arrangement.spacedBy(6.dp),
                        modifier = Modifier.fillMaxWidth().heightIn(max = 180.dp)
                    ) {
                        items(successData.items) { item ->
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .background(CaiNavyLight, RoundedCornerShape(6.dp))
                                    .padding(horizontal = 10.dp, vertical = 6.dp)
                            ) {
                                Icon(
                                    Icons.Default.CheckCircle,
                                    contentDescription = null,
                                    tint = CaiSuccess,
                                    modifier = Modifier.size(18.dp)
                                )
                                Spacer(Modifier.width(8.dp))
                                Text(
                                    text = item,
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = CaiTextPrimary
                                )
                            }
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = { viewModel.forceReset() },
                    colors = ButtonDefaults.buttonColors(containerColor = CaiSuccess)
                ) {
                    Text("Selesai")
                }
            },
            containerColor = CaiNavy,
            shape = RoundedCornerShape(16.dp)
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Registrasi Barang Peserta", fontWeight = FontWeight.Bold)
                        Text(
                            "Scan wajah/QR untuk mengambil barang",
                            style = MaterialTheme.typography.labelSmall,
                            color = CaiAccent
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
                            "Izin kamera diperlukan\nuntuk scan registrasi",
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

            // Camera preview
            CameraPreviewView(
                modifier = Modifier.fillMaxSize(),
                useFrontCamera = (scanMode == ScanMode.FACE),
                onFrameReady = { bitmap: Bitmap ->
                    viewModel.processFrame(bitmap)
                }
            )

            // Overlay gradient
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

            // Center target box
            Box(
                modifier = Modifier
                    .size(220.dp)
                    .align(Alignment.Center)
                    .offset(y = (-40).dp)
                    .border(
                        width = 2.dp,
                        color = when (scanResult) {
                            is SuppliesScanResult.Success -> CaiSuccess
                            is SuppliesScanResult.Unknown -> CaiError
                            else                          -> CaiAccent.copy(alpha = 0.7f)
                        },
                        shape = RoundedCornerShape(16.dp)
                    )
            )

            // Label mode aktif
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
                        if (scanMode == ScanMode.QR) "Mode QR — scan kartu QR barang" else "Mode Wajah — hadapkan wajah peserta",
                        style = MaterialTheme.typography.labelSmall,
                        color = Color.White
                    )
                }
            }

            // Bottom status hint
            Box(
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .padding(16.dp)
                    .fillMaxWidth()
            ) {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors   = CardDefaults.cardColors(containerColor = CaiNavy.copy(alpha = 0.8f)),
                    shape    = RoundedCornerShape(16.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(16.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        val statusText = when (scanResult) {
                            is SuppliesScanResult.NoFace ->
                                if (scanMode == ScanMode.QR) "Tunjukkan kode QR peserta" else "Arahkan wajah peserta ke kamera"
                            is SuppliesScanResult.ModelNotReady -> "Model FaceNet sedang bersiap..."
                            is SuppliesScanResult.Unknown -> "Wajah tidak terdaftar atau buram"
                            is SuppliesScanResult.Error -> (scanResult as SuppliesScanResult.Error).message
                            is SuppliesScanResult.Scanning -> "Memproses data..."
                            else -> if (scanMode == ScanMode.QR) "Arahkan kamera belakang ke kode QR" else "Arahkan wajah ke kotak"
                        }
                        Icon(
                            if (scanResult is SuppliesScanResult.Unknown || scanResult is SuppliesScanResult.Error) Icons.Default.Error else Icons.Default.Face,
                            contentDescription = null,
                            tint = if (scanResult is SuppliesScanResult.Unknown || scanResult is SuppliesScanResult.Error) CaiError else CaiAccent,
                            modifier = Modifier.size(32.dp)
                        )
                        Spacer(Modifier.width(12.dp))
                        Text(statusText, style = MaterialTheme.typography.bodyMedium, color = CaiTextSecondary)
                    }
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
