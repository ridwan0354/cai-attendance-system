package com.cai.attendance.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable

private val CaiDarkColorScheme = darkColorScheme(
    primary          = CaiBlue,
    onPrimary        = CaiTextPrimary,
    primaryContainer = CaiNavyLight,
    secondary        = CaiAccent,
    onSecondary      = CaiNavy,
    background       = CaiNavy,
    onBackground     = CaiTextPrimary,
    surface          = CaiSurface,
    onSurface        = CaiTextPrimary,
    surfaceVariant   = CaiSurfaceCard,
    onSurfaceVariant = CaiTextSecondary,
    error            = CaiError,
    onError          = CaiTextPrimary,
    outline          = CaiBorder,
)

@Composable
fun CaiAttendanceTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = CaiDarkColorScheme,
        typography  = CaiTypography,
        content     = content
    )
}
