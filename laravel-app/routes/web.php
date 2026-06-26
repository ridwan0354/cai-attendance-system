<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\GroupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - CAI LOMBOK 2026 Attendance System
|--------------------------------------------------------------------------
*/

// ── Public redirect ──────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('dashboard'));

// ── Dashboard (Realtime Monitor) ─────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ── Scanner Station ───────────────────────────────────────────────────────────
Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');

// ── Admin Panel ───────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    // Participants
    Route::resource('participants', ParticipantController::class);
    Route::post('participants/{participant}/register-face', [ParticipantController::class, 'registerFace'])
        ->name('participants.register-face');
    Route::get('participants/{participant}/face-image', [ParticipantController::class, 'faceImage'])
        ->name('participants.face-image');

    // Sessions
    Route::resource('sessions', SessionController::class);
    Route::post('sessions/{session}/activate', [SessionController::class, 'activate'])
        ->name('sessions.activate');
    Route::post('sessions/{session}/deactivate', [SessionController::class, 'deactivate'])
        ->name('sessions.deactivate');

    // Groups
    Route::resource('groups', GroupController::class);
});

// ── API Routes (JSON) ─────────────────────────────────────────────────────────
Route::prefix('api')->name('api.')->group(function () {
    // Attendance
    Route::post('attendance/face', [AttendanceController::class, 'processFace'])->name('attendance.face');
    Route::post('attendance/manual', [AttendanceController::class, 'processManual'])->name('attendance.manual');
    Route::get('attendance/{sessionId}', [AttendanceController::class, 'index'])->name('attendance.index');

    // Dashboard stats (polled by frontend as fallback)
    Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
});
