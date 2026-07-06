package com.cai.attendance.ui.screen

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.rotate
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.cai.attendance.ui.theme.*
import com.cai.attendance.ui.viewmodel.SyncViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SyncScreen(
    onNavigateBack: () -> Unit,
    viewModel: SyncViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    // Animasi rotate untuk icon sync saat loading
    val rotation by rememberInfiniteTransition(label = "sync_rotate").animateFloat(
        initialValue = 0f,
        targetValue  = 360f,
        animationSpec = infiniteRepeatable(
            animation = tween(1000, easing = LinearEasing)
        ),
        label = "rotation"
    )

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Sinkronisasi Data", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack, enabled = !state.isSyncing) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Kembali")
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
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // ── Status Lokal ──────────────────────────────────────────────
            Card(
                colors = CardDefaults.cardColors(containerColor = CaiSurfaceCard),
                shape  = RoundedCornerShape(16.dp)
            ) {
                Column(
                    modifier = Modifier.padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text("Data Tersimpan Lokal", style = MaterialTheme.typography.titleMedium, color = CaiAccent)

                    SyncStatRow("Total Peserta",      state.totalLocal.toString(),   Icons.Default.People)
                    SyncStatRow("Foto Tersimpan",     state.withPhoto.toString(),    Icons.Default.Image)
                    SyncStatRow("Embedding Siap",     state.withEmbedding.toString(), Icons.Default.Face,
                        valueColor = if (state.withEmbedding > 0) CaiSuccess else CaiTextMuted)
                }
            }

            // ── Progress saat sync ────────────────────────────────────────
            AnimatedVisibility(visible = state.isSyncing) {
                Card(
                    colors = CardDefaults.cardColors(containerColor = CaiSurfaceCard),
                    shape  = RoundedCornerShape(16.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(20.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                Icons.Default.Sync,
                                contentDescription = null,
                                tint = CaiAccent,
                                modifier = Modifier.rotate(rotation)
                            )
                            Spacer(Modifier.width(8.dp))
                            Text("Menyinkronkan…", style = MaterialTheme.typography.titleMedium, color = CaiAccent)
                        }

                        Text(
                            state.currentName.ifBlank { state.message },
                            style = MaterialTheme.typography.bodyMedium,
                            color = CaiTextSecondary
                        )

                        if (state.total > 0) {
                            LinearProgressIndicator(
                                progress = state.progress.toFloat() / state.total.toFloat(),
                                modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(4.dp)),
                                color    = CaiAccent
                            )
                            Text(
                                "${state.progress} / ${state.total} peserta",
                                style = MaterialTheme.typography.labelSmall,
                                color = CaiTextMuted
                            )
                        } else {
                            LinearProgressIndicator(
                                modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(4.dp)),
                                color    = CaiAccent
                            )
                        }
                    }
                }
            }

            // ── Hasil sync ────────────────────────────────────────────────
            AnimatedVisibility(visible = state.isSuccess != null && !state.isSyncing) {
                val isSuccess = state.isSuccess ?: false
                Card(
                    colors = CardDefaults.cardColors(
                        containerColor = if (isSuccess) CaiSuccess.copy(0.12f) else CaiError.copy(0.12f)
                    ),
                    shape = RoundedCornerShape(16.dp)
                ) {
                    Column(modifier = Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                if (isSuccess) Icons.Default.CheckCircle else Icons.Default.Error,
                                contentDescription = null,
                                tint = if (isSuccess) CaiSuccess else CaiError
                            )
                            Spacer(Modifier.width(8.dp))
                            Text(
                                if (isSuccess) "Sync Berhasil!" else "Sync Gagal",
                                fontWeight = FontWeight.Bold,
                                color = if (isSuccess) CaiSuccess else CaiError
                            )
                        }
                        Text(state.message, style = MaterialTheme.typography.bodyMedium, color = CaiTextSecondary)
                        if (isSuccess) {
                            SyncStatRow("Foto didownload", state.downloaded.toString(), Icons.Default.Download, valueColor = CaiSuccess)
                            SyncStatRow("Embedding dibuat", state.embedded.toString(), Icons.Default.Face, valueColor = CaiSuccess)
                            if (state.skipped > 0)
                                SyncStatRow("Dilewati", state.skipped.toString(), Icons.Default.SkipNext, valueColor = CaiWarning)
                        }
                    }
                }
            }

            Divider(color = CaiBorder)

            // ── Tombol Sync ───────────────────────────────────────────────
            Text("Pilih Jenis Sync", style = MaterialTheme.typography.titleMedium, color = CaiTextSecondary)

            // Incremental sync (hanya yang baru)
            Button(
                onClick   = viewModel::startIncrementalSync,
                enabled   = !state.isSyncing,
                modifier  = Modifier.fillMaxWidth().height(56.dp),
                shape     = RoundedCornerShape(12.dp),
                colors    = ButtonDefaults.buttonColors(containerColor = CaiBlue)
            ) {
                Icon(Icons.Default.Update, contentDescription = null)
                Spacer(Modifier.width(8.dp))
                Column {
                    Text("Sync Incremental", fontWeight = FontWeight.SemiBold)
                    Text("Hanya peserta baru/diperbarui", style = MaterialTheme.typography.labelSmall, color = CaiAccent)
                }
            }

            // Full sync (download ulang semua)
            OutlinedButton(
                onClick   = viewModel::startFullSync,
                enabled   = !state.isSyncing,
                modifier  = Modifier.fillMaxWidth().height(56.dp),
                shape     = RoundedCornerShape(12.dp),
                colors    = ButtonDefaults.outlinedButtonColors(contentColor = CaiTextSecondary)
            ) {
                Icon(Icons.Default.Refresh, contentDescription = null)
                Spacer(Modifier.width(8.dp))
                Column {
                    Text("Sync Penuh", fontWeight = FontWeight.SemiBold)
                    Text("Download ulang semua foto & embedding", style = MaterialTheme.typography.labelSmall, color = CaiTextMuted)
                }
            }

            Spacer(Modifier.height(8.dp))

            // Tip
            Card(
                colors = CardDefaults.cardColors(containerColor = CaiSurfaceCard),
                shape  = RoundedCornerShape(12.dp)
            ) {
                Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.Top) {
                    Icon(Icons.Default.Lightbulb, contentDescription = null, tint = CaiWarning, modifier = Modifier.size(20.dp))
                    Spacer(Modifier.width(8.dp))
                    Text(
                        "Lakukan sync saat terhubung ke WiFi untuk menghemat kuota. " +
                        "Setelah sync, scan wajah bisa dilakukan tanpa internet.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = CaiTextMuted
                    )
                }
            }
        }
    }
}

@Composable
private fun SyncStatRow(
    label: String,
    value: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    valueColor: Color = CaiTextPrimary
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(icon, contentDescription = null, tint = CaiTextMuted, modifier = Modifier.size(18.dp))
        Spacer(Modifier.width(8.dp))
        Text(label, style = MaterialTheme.typography.bodyMedium, color = CaiTextSecondary, modifier = Modifier.weight(1f))
        Text(value, style = MaterialTheme.typography.bodyMedium, color = valueColor, fontWeight = FontWeight.Bold)
    }
}

// Extension untuk clip di Column
private fun Modifier.clip(shape: RoundedCornerShape): Modifier =
    this.then(Modifier.background(Color.Transparent, shape))
