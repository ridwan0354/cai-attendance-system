package com.cai.attendance.ui.screen

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowDropDown
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
import androidx.compose.ui.platform.LocalContext
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
    val context = LocalContext.current
    val participants by viewModel.participantsList.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()
    var showAddDialog by remember { mutableStateOf(false) }

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
        floatingActionButton = {
            FloatingActionButton(
                onClick = { showAddDialog = true },
                containerColor = CaiBlue,
                contentColor = CaiTextPrimary
            ) {
                Icon(Icons.Default.Add, contentDescription = "Tambah Peserta")
            }
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

    // ── Dialog Tambah Peserta Baru ─────────────────────────────────────────
    if (showAddDialog) {
        var name by remember { mutableStateOf("") }
        var phone by remember { mutableStateOf("") }
        var qrCode by remember { mutableStateOf("") }
        var selectedGender by remember { mutableStateOf("Laki-laki") }
        val groups by viewModel.groups.collectAsState()
        val isLoadingGroups by viewModel.isLoadingGroups.collectAsState()
        val createState by viewModel.createState.collectAsState()
        
        var selectedGroup by remember { mutableStateOf<com.cai.attendance.data.remote.dto.GroupDto?>(null) }
        var isGroupDropdownExpanded by remember { mutableStateOf(false) }

        LaunchedEffect(Unit) {
            viewModel.loadGroups { errorMessage ->
                Toast.makeText(context, errorMessage, Toast.LENGTH_LONG).show()
            }
        }

        LaunchedEffect(groups) {
            if (selectedGroup == null && groups.isNotEmpty()) {
                selectedGroup = groups.first()
            }
        }

        AlertDialog(
            onDismissRequest = {
                if (!createState.isSaving) {
                    showAddDialog = false
                    viewModel.resetCreateState()
                }
            },
            title = {
                Text(
                    text = "Tambah Peserta Baru",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = CaiTextPrimary
                )
            },
            text = {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .verticalScroll(rememberScrollState()),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    // Nama
                    OutlinedTextField(
                        value = name,
                        onValueChange = { name = it },
                        label = { Text("Nama Lengkap", color = CaiTextSecondary) },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth(),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = CaiAccent,
                            unfocusedBorderColor = CaiBorder,
                            focusedTextColor = CaiTextPrimary,
                            unfocusedTextColor = CaiTextPrimary
                        )
                    )

                    // No HP / WhatsApp
                    OutlinedTextField(
                        value = phone,
                        onValueChange = { phone = it },
                        label = { Text("Nomor HP (WhatsApp)", color = CaiTextSecondary) },
                        singleLine = true,
                        keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(
                            keyboardType = androidx.compose.ui.text.input.KeyboardType.Phone
                        ),
                        modifier = Modifier.fillMaxWidth(),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = CaiAccent,
                            unfocusedBorderColor = CaiBorder,
                            focusedTextColor = CaiTextPrimary,
                            unfocusedTextColor = CaiTextPrimary
                        )
                    )

                    // Gender (Radio Button)
                    Column(modifier = Modifier.fillMaxWidth()) {
                        Text("Jenis Kelamin", style = MaterialTheme.typography.labelMedium, color = CaiTextSecondary)
                        Row(
                            horizontalArrangement = Arrangement.spacedBy(16.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                RadioButton(
                                    selected = selectedGender == "Laki-laki",
                                    onClick = { selectedGender = "Laki-laki" },
                                    colors = RadioButtonDefaults.colors(selectedColor = CaiAccent)
                                )
                                Text("Laki-laki", color = CaiTextPrimary)
                            }
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                RadioButton(
                                    selected = selectedGender == "Perempuan",
                                    onClick = { selectedGender = "Perempuan" },
                                    colors = RadioButtonDefaults.colors(selectedColor = CaiAccent)
                                )
                                Text("Perempuan", color = CaiTextPrimary)
                            }
                        }
                    }

                    // Kelompok (Dropdown)
                    Column(modifier = Modifier.fillMaxWidth()) {
                        Text("Kelompok", style = MaterialTheme.typography.labelMedium, color = CaiTextSecondary)
                        Spacer(modifier = Modifier.height(4.dp))
                        Box(modifier = Modifier.fillMaxWidth()) {
                            OutlinedTextField(
                                value = selectedGroup?.name ?: "Pilih Kelompok",
                                onValueChange = {},
                                enabled = false,
                                trailingIcon = {
                                    Icon(
                                        imageVector = Icons.Default.ArrowDropDown,
                                        contentDescription = null,
                                        tint = CaiTextSecondary
                                    )
                                },
                                modifier = Modifier.fillMaxWidth(),
                                colors = OutlinedTextFieldDefaults.colors(
                                    disabledBorderColor = CaiBorder,
                                    disabledTextColor = CaiTextPrimary,
                                    disabledTrailingIconColor = CaiTextSecondary,
                                    disabledLabelColor = CaiTextSecondary
                                )
                            )
                            Box(
                                modifier = Modifier
                                    .matchParentSize()
                                    .clickable { isGroupDropdownExpanded = true }
                            )
                            DropdownMenu(
                                expanded = isGroupDropdownExpanded,
                                onDismissRequest = { isGroupDropdownExpanded = false },
                                modifier = Modifier
                                    .fillMaxWidth(0.9f)
                                    .background(CaiSurfaceCard)
                            ) {
                                if (isLoadingGroups) {
                                    DropdownMenuItem(
                                        text = { CircularProgressIndicator(modifier = Modifier.size(24.dp)) },
                                        onClick = {}
                                    )
                                } else if (groups.isEmpty()) {
                                    DropdownMenuItem(
                                        text = { Text("Kelompok kosong (Gagal memuat)", color = CaiTextMuted) },
                                        onClick = { isGroupDropdownExpanded = false }
                                    )
                                } else {
                                    groups.forEach { group ->
                                        DropdownMenuItem(
                                            text = {
                                                Row(
                                                    verticalAlignment = Alignment.CenterVertically,
                                                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                                                ) {
                                                    Box(
                                                        modifier = Modifier
                                                            .size(12.dp)
                                                            .background(
                                                                try {
                                                                    Color(android.graphics.Color.parseColor(group.color))
                                                                } catch (e: Exception) {
                                                                    CaiBlue
                                                                },
                                                                shape = CircleShape
                                                            )
                                                    )
                                                    Text(group.name, color = CaiTextPrimary)
                                                }
                                            },
                                            onClick = {
                                                selectedGroup = group
                                                isGroupDropdownExpanded = false
                                            }
                                        )
                                    }
                                }
                            }
                        }
                    }

                    // QR Code (Optional)
                    OutlinedTextField(
                        value = qrCode,
                        onValueChange = { qrCode = it },
                        label = { Text("Kode QR (Opsional)", color = CaiTextSecondary) },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth(),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = CaiAccent,
                            unfocusedBorderColor = CaiBorder,
                            focusedTextColor = CaiTextPrimary,
                            unfocusedTextColor = CaiTextPrimary
                        )
                    )

                    if (createState.message.isNotBlank()) {
                        Text(
                            text = createState.message,
                            color = if (createState.isSuccess == true) CaiSuccess else CaiError,
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        if (name.isBlank()) {
                            Toast.makeText(context, "Nama tidak boleh kosong", Toast.LENGTH_SHORT).show()
                            return@Button
                        }
                        if (selectedGroup == null) {
                            Toast.makeText(context, "Kelompok belum dipilih", Toast.LENGTH_SHORT).show()
                            return@Button
                        }
                        viewModel.createParticipant(
                            name = name,
                            groupId = selectedGroup!!.id,
                            gender = selectedGender,
                            phone = phone,
                            qrCode = qrCode.ifBlank { null }
                        ) { success, entity ->
                            if (success && entity != null) {
                                Toast.makeText(context, "Peserta berhasil ditambahkan!", Toast.LENGTH_LONG).show()
                                showAddDialog = false
                                viewModel.resetCreateState()
                                onNavigateToRegister(entity.id, entity.name)
                            }
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = CaiSuccess),
                    enabled = !createState.isSaving
                ) {
                    if (createState.isSaving) {
                        CircularProgressIndicator(modifier = Modifier.size(20.dp), color = CaiTextPrimary, strokeWidth = 2.dp)
                    } else {
                        Text("Simpan & Lanjut Wajah", color = CaiTextPrimary)
                    }
                }
            },
            dismissButton = {
                TextButton(
                    onClick = {
                        showAddDialog = false
                        viewModel.resetCreateState()
                    },
                    enabled = !createState.isSaving
                ) {
                    Text("Batal", color = CaiTextSecondary)
                }
            },
            containerColor = CaiSurfaceCard,
            shape = RoundedCornerShape(20.dp)
        )
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
