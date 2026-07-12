package com.cai.attendance.navigation

/** Definisi layar-layar dalam app */
sealed class Screen(val route: String) {
    object Login   : Screen("login")
    object Home    : Screen("home")
    object Scanner : Screen("scanner")
    object Sync    : Screen("sync")
    object Participants : Screen("participants")
    object RegisterSupplies : Screen("register_supplies")
    object RegisterFace : Screen("register_face/{id}/{name}") {
        fun createRoute(id: Int, name: String) = "register_face/$id/$name"
    }
}
