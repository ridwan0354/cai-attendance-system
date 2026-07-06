package com.cai.attendance.ui.screen

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Error
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.cai.attendance.data.local.entity.ParticipantEntity
import com.cai.attendance.ui.theme.*
import com.cai.attendance.ui.viewmodel.ParticipantsViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ParticipantsScreen(
    onNavigateBack: () -> Unit,
    onNavigateToRegister: (id: Int, name: String) -> Unit,
    viewModel: ParticipantsViewModel = hiltViewModel()
) {
    val participants by viewModel.participantsList.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Daftar Peserta & Wajah", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
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
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // ── Search Bar ────────────────────────────────────────────────
            OutlinedTextField(
                value         = searchQuery,
                onValueChange = viewModel::onSearchQueryChanged,
                placeholder   = { Text("Cari nama peserta atau NIK…", color = CaiTextMuted) },
                leadingIcon   = { Icon(Icons.Default.Search, contentDescription = null, tint = CaiTextSecondary) },
                singleLine    = true,
                modifier      = Modifier.fillMaxWidth(),
                shape         = RoundedCornerShape(12.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor   = CaiAccent,
                    unfocusedBorderColor = CaiBorder,
                    focusedLabelColor    = CaiAccent,
                    unfocusedLabelColor  = CaiTextSecondary,
                )
            )

            // ── List Peserta ──────────────────────────────────────────────
            if (participants.isEmpty()) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = if (searchQuery.isBlank()) "Belum ada data peserta lokal. Lakukan sync dulu." else "Nama peserta tidak ditemukan",
                        color = CaiTextSecondary,
                        fontSize = 14.sp
                    )
                }
            } else {
                LazyColumn(
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                    modifier = Modifier.fillMaxSize()
                ) {
                    items(participants, key = { it.id }) { participant ->
                        ParticipantRow(
                            participant = participant,
                            onRegisterClick = { onNavigateToRegister(participant.id, participant.name) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun ParticipantRow(
    participant: ParticipantEntity,
    onRegisterClick: () -> Unit
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = CaiSurfaceCard),
        shape  = RoundedCornerShape(12.dp),
        modifier = Modifier.fillMaxWidth()
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Avatar inisial
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(CaiNavyLight),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = participant.name.take(1).uppercase(),
                    color = CaiAccent,
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp
                )
            }

            Spacer(Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = participant.name,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                    color = CaiTextPrimary
                )
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    // Badge Kelompok
                    Card(
                        colors = CardDefaults.cardColors(
                            containerColor = parseColor(participant.groupColor).copy(alpha = 0.15f)
                        ),
                        shape = RoundedCornerShape(4.dp)
                    ) {
                        Text(
                            text = participant.groupName,
                            style = MaterialTheme.typography.labelSmall,
                            color = parseColor(participant.groupColor),
                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                            fontWeight = FontWeight.Bold
                        )
                    }

                    // Status Wajah
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Icon(
                            imageVector = if (participant.faceRegistered) Icons.Default.CheckCircle else Icons.Default.Error,
                            contentDescription = null,
                            tint = if (participant.faceRegistered) CaiSuccess else CaiError,
                            modifier = Modifier.size(14.dp)
                        )
                        Text(
                            text = if (participant.faceRegistered) "Wajah Terdaftar" else "Belum Ada Wajah",
                            style = MaterialTheme.typography.labelSmall,
                            color = if (participant.faceRegistered) CaiSuccess else CaiError
                        )
                    }
                }
            }

            Spacer(Modifier.width(8.dp))

            // Tombol Daftar Wajah / Ganti Wajah
            IconButton(
                onClick = onRegisterClick,
                colors = IconButtonDefaults.iconButtonColors(
                    containerColor = if (participant.faceRegistered) CaiNavyLight else CaiBlue
                )
            ) {
                Icon(
                    Icons.Default.CameraAlt,
                    contentDescription = "Daftar Wajah",
                    tint = if (participant.faceRegistered) CaiTextSecondary else CaiTextPrimary,
                    modifier = Modifier.size(20.dp)
                )
            }
        }
    }
}

private fun parseColor(hex: String): Color {
    return try {
        Color(android.graphics.Color.parseColor(hex))
    } catch (e: Exception) {
        CaiBlue
    }
}
