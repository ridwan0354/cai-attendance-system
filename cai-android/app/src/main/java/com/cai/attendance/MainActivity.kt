package com.cai.attendance

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import com.cai.attendance.navigation.CaiNavGraph
import com.cai.attendance.ui.theme.CaiAttendanceTheme
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            CaiAttendanceTheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    CaiNavGraph()
                }
            }
        }
    }
}
