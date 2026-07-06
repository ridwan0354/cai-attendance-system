package com.cai.attendance.data.preferences

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.core.longPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

private val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "cai_prefs")

/**
 * Menyimpan konfigurasi aplikasi (URL server, API key, last sync time)
 * menggunakan Jetpack DataStore.
 */
@Singleton
class AppPreferences @Inject constructor(
    @ApplicationContext private val context: Context
) {
    companion object {
        private val KEY_SERVER_URL  = stringPreferencesKey("server_url")
        private val KEY_API_KEY     = stringPreferencesKey("api_key")
        private val KEY_LAST_SYNC   = longPreferencesKey("last_sync_timestamp")
        private val KEY_DEVICE_NAME = stringPreferencesKey("device_name")
    }

    val serverUrl: Flow<String> = context.dataStore.data.map { it[KEY_SERVER_URL] ?: "" }
    val apiKey: Flow<String>    = context.dataStore.data.map { it[KEY_API_KEY] ?: "" }
    val lastSync: Flow<Long>    = context.dataStore.data.map { it[KEY_LAST_SYNC] ?: 0L }

    val isLoggedIn: Flow<Boolean> = context.dataStore.data.map { prefs ->
        !prefs[KEY_SERVER_URL].isNullOrBlank() && !prefs[KEY_API_KEY].isNullOrBlank()
    }

    suspend fun saveServerConfig(serverUrl: String, apiKey: String) {
        context.dataStore.edit { prefs ->
            prefs[KEY_SERVER_URL]  = serverUrl.trimEnd('/')
            prefs[KEY_API_KEY]     = apiKey
        }
    }

    suspend fun updateLastSync(timestamp: Long) {
        context.dataStore.edit { it[KEY_LAST_SYNC] = timestamp }
    }

    suspend fun clearAll() {
        context.dataStore.edit { it.clear() }
    }
}
