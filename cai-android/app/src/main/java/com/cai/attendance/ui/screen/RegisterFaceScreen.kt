package com.cai.attendance.ui.screen

import android.Manifest
import android.graphics.Bitmap
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.*
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import com.cai.attendance.ui.component.CameraPreviewView
import com.cai.attendance.ui.theme.*
import com.cai.attendance.ui.viewmodel.ParticipantsViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RegisterFaceScreen(
    participantId: Int,
    participantName: String,
    onNavigateBack: () -> Unit,
    viewModel: ParticipantsViewModel = hiltViewModel()
) {
    val context      = LocalContext.current
    val registerState by viewModel.registerState.collectAsState()

    var capturedBitmap by remember { mutableStateOf<Bitmap?>(null) }
    var latestFrame    by remember { mutableStateOf<Bitmap?>(null) }

    // Camera permission
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
        viewModel.resetRegisterState()
        if (!hasCameraPermission) {
            permissionLauncher.launch(Manifest.permission.CAMERA)
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Registrasi Wajah", fontWeight = FontWeight.Bold)
                        Text(participantName, style = MaterialTheme.typography.labelSmall, color = CaiAccent)
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack, enabled = !registerState.isUploading) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Kembali")
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
                // Izin Kamera
                Box(modifier = Modifier.fillMaxSize().background(CaiNavy), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(32.dp)) {
                        Icon(Icons.Default.CameraAlt, contentDescription = null, modifier = Modifier.size(64.dp), tint = CaiTextSecondary)
                        Spacer(Modifier.height(16.dp))
                        Text("Izin kamera diperlukan untuk mendaftarkan wajah", style = MaterialTheme.typography.titleMedium, textAlign = TextAlign.Center, color = CaiTextPrimary)
                        Spacer(Modifier.height(16.dp))
                        Button(onClick = { permissionLauncher.launch(Manifest.permission.CAMERA) }) {
                            Text("Beri Izin")
                        }
                    }
                }
                return@Scaffold
            }

            if (capturedBitmap == null) {
                // ── MODE AMBIL FOTO (Kamera Live) ─────────────────────────
                CameraPreviewView(
                    modifier     = Modifier.fillMaxSize(),
                    onFrameReady = { frame ->
                        latestFrame = frame
                    }
                )

                // Overlay Lingkaran Panduan
                Box(
                    modifier = Modifier
                        .size(240.dp)
                        .align(Alignment.Center)
                        .offset(y = (-40).dp)
                        .border(width = 3.dp, color = CaiAccent, shape = CircleShape)
                )

                Text(
                    text      = "Posisikan wajah di dalam lingkaran\ndan tekan tombol jepret di bawah",
                    color     = CaiTextSecondary,
                    textAlign = TextAlign.Center,
                    style     = MaterialTheme.typography.bodyMedium,
                    modifier  = Modifier
                        .align(Alignment.BottomCenter)
                        .padding(bottom = 120.dp)
                        .fillMaxWidth()
                        .padding(horizontal = 32.dp)
                )

                // Tombol Jepret (Shutter)
                FloatingActionButton(
                    onClick = {
                        latestFrame?.let {
                            capturedBitmap = it
                        } ?: run {
                            Toast.makeText(context, "Kamera belum siap, tunggu sebentar", Toast.LENGTH_SHORT).show()
                        }
                    },
                    containerColor = CaiBlue,
                    contentColor   = CaiTextPrimary,
                    modifier       = Modifier
                        .align(Alignment.BottomCenter)
                        .padding(bottom = 32.dp)
                        .size(72.dp),
                    shape          = CircleShape
                ) {
                    Icon(Icons.Default.CameraAlt, contentDescription = "Capture", modifier = Modifier.size(32.dp))
                }
            } else {
                // ── MODE PRATINJAU FOTO (Preview Captured Image) ──────────
                Image(
                    bitmap             = capturedBitmap!!.asImageBitmap(),
                    contentDescription = "Captured Face",
                    modifier           = Modifier.fillMaxSize()
                )

                // Card Preview Feedback
                Card(
                    modifier = Modifier
                        .align(Alignment.BottomCenter)
                        .padding(16.dp)
                        .fillMaxWidth(),
                    colors   = CardDefaults.cardColors(containerColor = CaiSurfaceCard.copy(alpha = 0.95f)),
                    shape    = RoundedCornerShape(16.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(
                            text = "Apakah foto wajah ini sudah jelas?",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = CaiTextPrimary
                        )

                        Row(
                            horizontalArrangement = Arrangement.spacedBy(16.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            // Ulangi
                            OutlinedButton(
                                onClick  = { capturedBitmap = null },
                                modifier = Modifier.weight(1f),
                                colors   = ButtonDefaults.outlinedButtonColors(contentColor = CaiTextSecondary),
                                enabled  = !registerState.isUploading
                            ) {
                                Icon(Icons.Default.Refresh, contentDescription = null)
                                Spacer(Modifier.width(8.dp))
                                Text("Ulangi")
                            }

                            // Upload / Daftar
                            Button(
                                onClick  = {
                                    viewModel.registerFace(
                                        participantId = participantId,
                                        bitmap        = capturedBitmap!!,
                                        onComplete    = { success ->
                                            if (success) {
                                                // show dialog instead of navigating back immediately
                                            }
                                        }
                                    )
                                },
                                modifier = Modifier.weight(1f),
                                colors   = ButtonDefaults.buttonColors(containerColor = CaiSuccess),
                                enabled  = !registerState.isUploading
                            ) {
                                if (registerState.isUploading) {
                                    CircularProgressIndicator(modifier = Modifier.size(20.dp), color = CaiTextPrimary, strokeWidth = 2.dp)
                                } else {
                                    Icon(Icons.Default.Check, contentDescription = null)
                                    Spacer(Modifier.width(8.dp))
                                    Text("Simpan & Upload")
                                }
                            }
                        }

                        // Feedback message saat upload
                        AnimatedVisibility(visible = registerState.message.isNotBlank()) {
                            Text(
                                text  = registerState.message,
                                color = if (registerState.isSuccess == false) CaiError else CaiAccent,
                                style = MaterialTheme.typography.labelSmall
                            )
                        }
                    }
                }
            }
        }
    }

    // Popup Konfirmasi Sukses Registrasi Wajah
    if (registerState.isSuccess == true && capturedBitmap != null) {
        AlertDialog(
            onDismissRequest = { /* prevent dismiss on outside click */ },
            title = {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Icon(
                        imageVector = Icons.Default.CheckCircle,
                        contentDescription = null,
                        tint = CaiSuccess,
                        modifier = Modifier.size(28.dp)
                    )
                    Text(
                        text = "Registrasi Wajah Berhasil",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = CaiTextPrimary
                    )
                }
            },
            text = {
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // Tampilkan foto wajah yang diambil di dalam bingkai bulat
                    Box(
                        modifier = Modifier
                            .size(100.dp)
                            .clip(CircleShape)
                            .border(width = 3.dp, color = CaiSuccess, shape = CircleShape)
                            .background(CaiNavyLight)
                    ) {
                        Image(
                            bitmap = capturedBitmap!!.asImageBitmap(),
                            contentDescription = "Foto terdaftar",
                            modifier = Modifier.fillMaxSize()
                        )
                    }

                    Text(
                        text = "Wajah untuk peserta \"$participantName\" telah sukses dikonfigurasi.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = CaiTextSecondary,
                        textAlign = TextAlign.Center
                    )

                    // Checklist konfirmasi apa saja yang sudah berhasil diambil/dibuat
                    Card(
                        colors = CardDefaults.cardColors(containerColor = CaiNavyLight),
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(
                            modifier = Modifier.padding(12.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Text(
                                text = "Detail Aset yang Terbentuk:",
                                style = MaterialTheme.typography.labelMedium,
                                fontWeight = FontWeight.Bold,
                                color = CaiAccent
                            )
                            
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.Check, contentDescription = null, tint = CaiSuccess, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(8.dp))
                                Text("Foto Lokal (.jpg) -> Tersimpan", style = MaterialTheme.typography.bodySmall, color = CaiTextPrimary)
                            }
                            
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.Check, contentDescription = null, tint = CaiSuccess, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(8.dp))
                                Text("Upload Server Pusat -> Berhasil", style = MaterialTheme.typography.bodySmall, color = CaiTextPrimary)
                            }

                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.Check, contentDescription = null, tint = CaiSuccess, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(8.dp))
                                Text("Embedding Wajah (${viewModel.participantsList.value.firstOrNull { it.id == participantId }?.embeddingJson?.let { "Siap" } ?: "Siap"}) -> Terhitung", style = MaterialTheme.typography.bodySmall, color = CaiTextPrimary)
                            }
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.resetRegisterState()
                        onNavigateBack()
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = CaiBlue)
                ) {
                    Text("Selesai", color = CaiTextPrimary)
                }
            },
            containerColor = CaiSurfaceCard,
            shape = RoundedCornerShape(20.dp)
        )
    }
}
