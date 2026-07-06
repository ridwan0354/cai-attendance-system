package com.cai.attendance.navigation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.cai.attendance.ui.screen.HomeScreen
import com.cai.attendance.ui.screen.LoginScreen
import com.cai.attendance.ui.screen.ScannerScreen
import com.cai.attendance.ui.screen.SyncScreen
import com.cai.attendance.ui.screen.ParticipantsScreen
import com.cai.attendance.ui.screen.RegisterFaceScreen
import com.cai.attendance.ui.viewmodel.LoginViewModel
import androidx.navigation.NavType
import androidx.navigation.navArgument

@Composable
fun CaiNavGraph() {
    val navController = rememberNavController()
    val loginViewModel: LoginViewModel = hiltViewModel()
    val isLoggedIn by loginViewModel.isLoggedIn.collectAsState(initial = false)

    NavHost(
        navController  = navController,
        startDestination = if (isLoggedIn) Screen.Home.route else Screen.Login.route
    ) {
        composable(Screen.Login.route) {
            LoginScreen(
                onLoginSuccess = {
                    navController.navigate(Screen.Home.route) {
                        popUpTo(Screen.Login.route) { inclusive = true }
                    }
                }
            )
        }

        composable(Screen.Home.route) {
            HomeScreen(
                onNavigateToScanner      = { navController.navigate(Screen.Scanner.route) },
                onNavigateToSync         = { navController.navigate(Screen.Sync.route) },
                onNavigateToParticipants = { navController.navigate(Screen.Participants.route) },
                onLogout                 = {
                    navController.navigate(Screen.Login.route) {
                        popUpTo(Screen.Home.route) { inclusive = true }
                    }
                }
            )
        }

        composable(Screen.Scanner.route) {
            ScannerScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(Screen.Sync.route) {
            SyncScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(Screen.Participants.route) {
            ParticipantsScreen(
                onNavigateBack = { navController.popBackStack() },
                onNavigateToRegister = { id, name ->
                    navController.navigate(Screen.RegisterFace.createRoute(id, name))
                }
            )
        }

        composable(
            route = Screen.RegisterFace.route,
            arguments = listOf(
                navArgument("id") { type = NavType.IntType },
                navArgument("name") { type = NavType.StringType }
            )
        ) { backStackEntry ->
            val participantId   = backStackEntry.arguments?.getInt("id") ?: 0
            val participantName = backStackEntry.arguments?.getString("name") ?: ""
            RegisterFaceScreen(
                participantId   = participantId,
                participantName = participantName,
                onNavigateBack  = { navController.popBackStack() }
            )
        }
    }
}
