package com.cai.attendance.ui.screen

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.cai.attendance.ui.theme.*
import com.cai.attendance.ui.viewmodel.HomeViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    onNavigateToScanner: () -> Unit,
    onNavigateToSync: () -> Unit,
    onNavigateToParticipants: () -> Unit,
    onNavigateToRegisterSupplies: () -> Unit,
    onLogout: () -> Unit,
    viewModel: HomeViewModel = hiltViewModel()
) {
    val context = androidx.compose.ui.platform.LocalContext.current
    val state by viewModel.uiState.collectAsState()
    val pendingCount by viewModel.pendingCount.collectAsState()
    var showLogoutDialog by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        viewModel.loadStats()
    }

    // Logout confirmation dialog
    if (showLogoutDialog) {
        AlertDialog(
            onDismissRequest = { showLogoutDialog = false },
            title  = { Text("Keluar?") },
            text   = { Text("Konfigurasi server akan dihapus.") },
            confirmButton = {
                TextButton(onClick = { viewModel.logout(onLogout) }) {
                    Text("Ya, Keluar", color = CaiError)
                }
            },
            dismissButton = {
                TextButton(onClick = { showLogoutDialog = false }) {
                    Text("Batal")
                }
            },
            containerColor = CaiSurfaceCard
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("CAI Attendance", fontWeight = FontWeight.Bold)
                        Text(
                            state.serverUrl.ifBlank { "–" },
                            style = MaterialTheme.typography.labelSmall,
                            color = CaiTextMuted
                        )
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.syncNow() }) {
                        Icon(Icons.Default.Sync, contentDescription = "Sync", tint = CaiAccent)
                    }
                    IconButton(onClick = { showLogoutDialog = true }) {
                        Icon(Icons.Default.Logout, contentDescription = "Logout", tint = CaiTextSecondary)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = CaiNavy,
                    titleContentColor = CaiTextPrimary
                )
            )
        },
        containerColor = CaiNavy
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // ── Stats Grid ────────────────────────────────────────────────
            Text(
                "Status Lokal",
                style = MaterialTheme.typography.titleMedium,
                color = CaiTextSecondary
            )

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                StatCard(
                    modifier = Modifier.weight(1f),
                    label    = "Total Peserta",
                    value    = state.totalParticipants.toString(),
                    icon     = Icons.Default.People,
                    color    = CaiBlue
                )
                StatCard(
                    modifier = Modifier.weight(1f),
                    label    = "Foto Tersimpan",
                    value    = state.totalWithPhoto.toString(),
                    icon     = Icons.Default.Image,
                    color    = CaiAccent
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                StatCard(
                    modifier = Modifier.weight(1f),
                    label    = "Embedding Siap",
                    value    = state.totalWithEmbedding.toString(),
                    icon     = Icons.Default.Face,
                    color    = CaiSuccess
                )
                StatCard(
                    modifier = Modifier.weight(1f),
                    label    = "Antrian Upload",
                    value    = pendingCount.toString(),
                    icon     = Icons.Default.Upload,
                    color    = if (pendingCount > 0) CaiWarning else CaiTextMuted
                )
            }

            val isUploading by viewModel.isUploading.collectAsState()

            // Tombol upload pending jika ada
            if (pendingCount > 0) {
                OutlinedButton(
                    onClick  = {
                        viewModel.uploadPending { count ->
                            android.widget.Toast.makeText(context, "$count data absensi offline berhasil diunggah!", android.widget.Toast.LENGTH_LONG).show()
                        }
                    },
                    modifier = Modifier.fillMaxWidth(),
                    enabled  = !isUploading,
                    shape    = RoundedCornerShape(12.dp),
                    colors   = ButtonDefaults.outlinedButtonColors(contentColor = CaiWarning),
                    border   = ButtonDefaults.outlinedButtonBorder
                ) {
                    if (isUploading) {
                        CircularProgressIndicator(modifier = Modifier.size(20.dp), color = CaiWarning, strokeWidth = 2.dp)
                    } else {
                        Icon(Icons.Default.CloudUpload, contentDescription = null)
                        Spacer(Modifier.width(8.dp))
                        Text("Upload $pendingCount Absensi Offline")
                    }
                }
            }


            Divider(color = CaiBorder, thickness = 1.dp)

            // ── Action Buttons ────────────────────────────────────────────
            Text(
                "Aksi",
                style = MaterialTheme.typography.titleMedium,
                color = CaiTextSecondary
            )

            // Tombol Scan Wajah (utama)
            Button(
                onClick   = onNavigateToScanner,
                modifier  = Modifier
                    .fillMaxWidth()
                    .height(64.dp),
                shape     = RoundedCornerShape(16.dp),
                colors    = ButtonDefaults.buttonColors(
                    containerColor = CaiBlue
                )
            ) {
                Icon(Icons.Default.CameraAlt, contentDescription = null, modifier = Modifier.size(28.dp))
                Spacer(Modifier.width(12.dp))
                Column {
                    Text("Mulai Scan Wajah", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    Text("Face recognition lokal", style = MaterialTheme.typography.labelSmall, color = CaiAccent)
                }
            }

            // Tombol Registrasi Barang Peserta (Gift)
            Button(
                onClick   = onNavigateToRegisterSupplies,
                modifier  = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                shape     = RoundedCornerShape(12.dp),
                colors    = ButtonDefaults.buttonColors(
                    containerColor = CaiSuccess.copy(alpha = 0.15f)
                ),
                border    = BorderStroke(1.dp, CaiSuccess)
            ) {
                Icon(Icons.Default.CardGiftcard, contentDescription = null, tint = CaiSuccess)
                Spacer(Modifier.width(8.dp))
                Text("Registrasi Barang Peserta 🎁", fontWeight = FontWeight.SemiBold, color = CaiSuccess)
            }

            // Tombol Registrasi Wajah
            Button(
                onClick   = onNavigateToParticipants,
                modifier  = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                shape     = RoundedCornerShape(12.dp),
                colors    = ButtonDefaults.buttonColors(
                    containerColor = CaiNavyLight
                ),
                border    = BorderStroke(1.dp, CaiBorder)
            ) {
                Icon(Icons.Default.Face, contentDescription = null, tint = CaiAccent)
                Spacer(Modifier.width(8.dp))
                Text("Registrasi Wajah Peserta", fontWeight = FontWeight.SemiBold, color = CaiTextPrimary)
            }

            // Tombol Sync Data
            OutlinedButton(
                onClick   = onNavigateToSync,
                modifier  = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                shape     = RoundedCornerShape(12.dp),
                colors    = ButtonDefaults.outlinedButtonColors(contentColor = CaiAccent)
            ) {
                Icon(Icons.Default.Sync, contentDescription = null)
                Spacer(Modifier.width(8.dp))
                Text("Sinkronisasi Data Peserta", fontWeight = FontWeight.Medium)
            }

            // Info embedding
            if (state.totalWithEmbedding == 0 && state.totalWithPhoto > 0) {
                val loadErr = com.cai.attendance.ml.FaceNetModel.loadError
                val inferErr = com.cai.attendance.ml.FaceNetModel.inferenceError
                val errorMsg = loadErr ?: inferErr
                Card(
                    colors = CardDefaults.cardColors(
                        containerColor = CaiWarning.copy(alpha = 0.12f)
                    ),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.Warning, contentDescription = null, tint = CaiWarning)
                        Spacer(Modifier.width(8.dp))
                        Text(
                            text = when {
                                loadErr != null  -> "Error load model: $loadErr"
                                inferErr != null -> "Error inference: $inferErr"
                                else -> "Embedding belum dibuat. Lakukan sync ulang untuk generate embedding wajah."
                            },
                            style = MaterialTheme.typography.bodyMedium,
                            color = CaiWarning
                        )
                    }
                }
            }

            if (state.totalParticipants == 0) {
                Card(
                    colors = CardDefaults.cardColors(
                        containerColor = CaiBlue.copy(alpha = 0.12f)
                    ),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.Info, contentDescription = null, tint = CaiAccent)
                        Spacer(Modifier.width(8.dp))
                        Text(
                            "Belum ada data peserta. Tekan 'Sinkronisasi Data' untuk memulai.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = CaiTextSecondary
                        )
                    }
                }
            }

            Spacer(Modifier.height(16.dp))
        }
    }
}

@Composable
private fun StatCard(
    label: String,
    value: String,
    icon: ImageVector,
    color: Color,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier,
        colors   = CardDefaults.cardColors(containerColor = CaiSurfaceCard),
        shape    = RoundedCornerShape(16.dp),
        elevation = CardDefaults.cardElevation(4.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Icon(icon, contentDescription = null, tint = color, modifier = Modifier.size(24.dp))
            Text(value, style = MaterialTheme.typography.headlineMedium, color = color, fontWeight = FontWeight.Bold)
            Text(label, style = MaterialTheme.typography.labelSmall, color = CaiTextSecondary)
        }
    }
}
