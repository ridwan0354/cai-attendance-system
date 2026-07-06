package com.cai.attendance.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.cai.attendance.data.preferences.AppPreferences
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class LoginUiState(
    val serverUrl: String  = "",
    val apiKey: String     = "",
    val isLoading: Boolean = false,
    val error: String?     = null,
)

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val preferences: AppPreferences,
) : ViewModel() {

    val isLoggedIn = preferences.isLoggedIn

    private val _uiState = MutableStateFlow(LoginUiState())
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()

    fun onServerUrlChanged(value: String) {
        _uiState.value = _uiState.value.copy(serverUrl = value, error = null)
    }

    fun onApiKeyChanged(value: String) {
        _uiState.value = _uiState.value.copy(apiKey = value, error = null)
    }

    fun login(onSuccess: () -> Unit) {
        val state = _uiState.value

        if (state.serverUrl.isBlank()) {
            _uiState.value = state.copy(error = "URL server tidak boleh kosong")
            return
        }
        if (state.apiKey.isBlank()) {
            _uiState.value = state.copy(error = "API Key tidak boleh kosong")
            return
        }

        // Validasi format URL dasar
        if (!state.serverUrl.startsWith("http://") && !state.serverUrl.startsWith("https://")) {
            _uiState.value = state.copy(error = "URL harus diawali dengan http:// atau https://")
            return
        }

        viewModelScope.launch {
            _uiState.value = state.copy(isLoading = true, error = null)
            try {
                preferences.saveServerConfig(state.serverUrl.trim(), state.apiKey.trim())
                onSuccess()
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = "Gagal menyimpan konfigurasi: ${e.message}"
                )
            }
        }
    }
}
