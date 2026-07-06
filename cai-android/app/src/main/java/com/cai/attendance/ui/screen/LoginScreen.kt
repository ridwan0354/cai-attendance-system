package com.cai.attendance.ui.screen

import androidx.compose.animation.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Key
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material.icons.outlined.Language
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.cai.attendance.ui.theme.*
import com.cai.attendance.ui.viewmodel.LoginViewModel

@Composable
fun LoginScreen(
    onLoginSuccess: () -> Unit,
    viewModel: LoginViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var showApiKey by remember { mutableStateOf(false) }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(
                Brush.verticalGradient(
                    colors = listOf(CaiNavyDark, CaiNavy, CaiSurface)
                )
            ),
        contentAlignment = Alignment.Center
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 28.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            // ── Logo / Header ──────────────────────────────────────────────
            Spacer(Modifier.height(24.dp))

            // Icon lingkaran
            Box(
                modifier = Modifier
                    .size(80.dp)
                    .clip(RoundedCornerShape(50))
                    .background(
                        Brush.radialGradient(listOf(CaiBlueLight, CaiBlueDark))
                    ),
                contentAlignment = Alignment.Center
            ) {
                Text("CAI", fontSize = 22.sp, fontWeight = FontWeight.Bold, color = CaiTextPrimary)
            }

            Spacer(Modifier.height(20.dp))

            Text(
                text = "CAI Attendance",
                style = MaterialTheme.typography.displayMedium,
                color = CaiTextPrimary,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = "Sistem Absensi CAI Lombok 2026",
                style = MaterialTheme.typography.bodyMedium,
                color = CaiTextSecondary,
                textAlign = TextAlign.Center
            )

            Spacer(Modifier.height(40.dp))

            // ── Card Form ─────────────────────────────────────────────────
            Card(
                modifier  = Modifier.fillMaxWidth(),
                colors    = CardDefaults.cardColors(containerColor = CaiSurfaceCard),
                elevation = CardDefaults.cardElevation(defaultElevation = 8.dp),
                shape     = RoundedCornerShape(20.dp)
            ) {
                Column(
                    modifier = Modifier.padding(24.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    Text(
                        "Konfigurasi Server",
                        style = MaterialTheme.typography.titleMedium,
                        color = CaiAccent
                    )

                    // Server URL
                    OutlinedTextField(
                        value         = state.serverUrl,
                        onValueChange = viewModel::onServerUrlChanged,
                        label         = { Text("URL Server") },
                        placeholder   = { Text("http://192.168.1.100:8000", color = CaiTextMuted) },
                        leadingIcon   = { Icon(Icons.Outlined.Language, contentDescription = null) },
                        singleLine    = true,
                        modifier      = Modifier.fillMaxWidth(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor   = CaiAccent,
                            unfocusedBorderColor = CaiBorder,
                            focusedLabelColor    = CaiAccent,
                            unfocusedLabelColor  = CaiTextSecondary,
                        )
                    )

                    // API Key
                    OutlinedTextField(
                        value         = state.apiKey,
                        onValueChange = viewModel::onApiKeyChanged,
                        label         = { Text("API Key") },
                        placeholder   = { Text("cai-mobile-2026-xxxxx", color = CaiTextMuted) },
                        leadingIcon   = { Icon(Icons.Default.Key, contentDescription = null) },
                        trailingIcon  = {
                            IconButton(onClick = { showApiKey = !showApiKey }) {
                                Icon(
                                    if (showApiKey) Icons.Default.VisibilityOff else Icons.Default.Visibility,
                                    contentDescription = null
                                )
                            }
                        },
                        visualTransformation = if (showApiKey) VisualTransformation.None
                                               else PasswordVisualTransformation(),
                        singleLine    = true,
                        modifier      = Modifier.fillMaxWidth(),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor   = CaiAccent,
                            unfocusedBorderColor = CaiBorder,
                            focusedLabelColor    = CaiAccent,
                            unfocusedLabelColor  = CaiTextSecondary,
                        )
                    )

                    // Error message
                    AnimatedVisibility(visible = state.error != null) {
                        state.error?.let {
                            Card(
                                colors = CardDefaults.cardColors(containerColor = CaiError.copy(alpha = 0.15f)),
                                shape  = RoundedCornerShape(8.dp)
                            ) {
                                Text(
                                    text     = it,
                                    color    = CaiError,
                                    style    = MaterialTheme.typography.bodyMedium,
                                    modifier = Modifier.padding(12.dp)
                                )
                            }
                        }
                    }

                    // Tombol Login
                    Button(
                        onClick   = { viewModel.login(onLoginSuccess) },
                        enabled   = !state.isLoading,
                        modifier  = Modifier
                            .fillMaxWidth()
                            .height(52.dp),
                        shape     = RoundedCornerShape(12.dp),
                        colors    = ButtonDefaults.buttonColors(
                            containerColor = CaiBlue,
                            contentColor   = CaiTextPrimary
                        )
                    ) {
                        if (state.isLoading) {
                            CircularProgressIndicator(
                                modifier  = Modifier.size(20.dp),
                                color     = CaiTextPrimary,
                                strokeWidth = 2.dp
                            )
                        } else {
                            Text(
                                "Masuk & Mulai Sync",
                                fontWeight = FontWeight.SemiBold,
                                fontSize   = 16.sp
                            )
                        }
                    }
                }
            }

            Spacer(Modifier.height(24.dp))

            Text(
                text  = "Masukkan URL server Laravel Anda\ndan API Key dari file .env (MOBILE_API_KEY)",
                style = MaterialTheme.typography.labelSmall,
                textAlign = TextAlign.Center,
                color = CaiTextMuted
            )
        }
    }
}
