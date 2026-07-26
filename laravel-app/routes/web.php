<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\SupplyController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Api\MobileApiController;
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
    Route::get('/', fn() => redirect()->route('admin.participants.index'))->name('index');

    // Attendance Log
    Route::resource('attendances', AdminAttendanceController::class)->only(['index', 'store', 'update', 'destroy']);

    // Participants
    Route::resource('participants', ParticipantController::class);
    Route::post('participants/{participant}/register-face', [ParticipantController::class, 'registerFace'])
        ->name('participants.register-face');
    Route::delete('participants/{participant}/delete-face', [ParticipantController::class, 'deleteFace'])
        ->name('participants.delete-face');
    Route::get('participants/{participant}/face-image', [ParticipantController::class, 'faceImage'])
        ->name('participants.face-image');
    Route::post('participants/{participant}/verify-checkin', [ParticipantController::class, 'verifyCheckIn'])
        ->name('participants.verify-checkin');
    Route::get('participants/{participant}/checkin-data', [ParticipantController::class, 'checkInData'])
        ->name('participants.checkin-data');
    Route::post('participants/{participant}/save-checkin', [ParticipantController::class, 'saveCheckIn'])
        ->name('participants.save-checkin');

    // Sessions
    Route::resource('sessions', SessionController::class);
    Route::post('sessions/{session}/activate', [SessionController::class, 'activate'])
        ->name('sessions.activate');
    Route::post('sessions/{session}/deactivate', [SessionController::class, 'deactivate'])
        ->name('sessions.deactivate');
    Route::post('sessions/{session}/send-report', [SessionController::class, 'sendReport'])
        ->name('sessions.send-report');

    // Groups
    Route::post('groups/send-all-sessions-report', [GroupController::class, 'sendAllSessionsReport'])
        ->name('groups.send-all-sessions-report');
    Route::post('groups/{group}/send-recap', [GroupController::class, 'sendRecap'])
        ->name('groups.send-recap');
    Route::resource('groups', GroupController::class);

    // Supplies
    Route::resource('supplies', SupplyController::class)->only(['index', 'store', 'destroy']);

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/unlock', [SettingController::class, 'unlock'])->name('settings.unlock');
    Route::post('settings', [SettingController::class, 'store'])->name('settings.store');
    Route::post('settings/lock', [SettingController::class, 'lock'])->name('settings.lock');
    Route::post('settings/test-wa', [SettingController::class, 'testWA'])->name('settings.test-wa');
});

// ── API Routes (JSON) ─────────────────────────────────────────────────────────
Route::prefix('api')->name('api.')->group(function () {
    // Attendance
    Route::post('attendance/face', [AttendanceController::class, 'processFace'])->name('attendance.face');
    Route::post('attendance/qr', [AttendanceController::class, 'processQR'])->name('attendance.qr');
    Route::post('attendance/manual', [AttendanceController::class, 'processManual'])->name('attendance.manual');
    Route::get('attendance/{sessionId}', [AttendanceController::class, 'index'])->name('attendance.index');

    // Dashboard stats (polled by frontend as fallback)
    Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('dashboard/sessions/{session}/detail', [DashboardController::class, 'sessionDetail'])->name('dashboard.sessions.detail');
});

// ── Mobile API Routes (Android App) ──────────────────────────────────────────
// Auth: X-Api-Key header (set MOBILE_API_KEY di .env)
Route::prefix('api/mobile')->name('api.mobile.')->group(function () {
    // Sync info (statistik, last updated)
    Route::get('sync/info', [MobileApiController::class, 'syncInfo'])->name('sync.info');

    // Daftar peserta (incremental sync via ?since=)
    Route::get('participants', [MobileApiController::class, 'participants'])->name('participants');

    // Tambah peserta baru
    Route::post('participants', [MobileApiController::class, 'storeParticipant'])->name('participants.store');

    // Update data peserta
    Route::put('participants/{id}', [MobileApiController::class, 'updateParticipant'])->name('participants.update');

    // Daftar kelompok peserta
    Route::get('groups', [MobileApiController::class, 'groups'])->name('groups');

    // Register wajah peserta baru/update
    Route::post('participants/{id}/register-face', [MobileApiController::class, 'registerFace'])->name('participants.register-face');

    // Download foto peserta (binary JPEG)
    Route::get('participants/{id}/photo', [MobileApiController::class, 'participantPhoto'])->name('participants.photo');

    // Sesi aktif saat ini
    Route::get('sessions/active', [MobileApiController::class, 'activeSession'])->name('sessions.active');

    // Semua sesi
    Route::get('sessions', [MobileApiController::class, 'sessions'])->name('sessions');

    // Catat absensi (single atau batch untuk upload offline queue)
    Route::post('attendance', [MobileApiController::class, 'recordAttendance'])->name('attendance');

    // Registrasi Barang (Supplies) Check-in
    Route::get('supplies', [MobileApiController::class, 'supplies'])->name('supplies');
    Route::get('participants/{id}/supplies', [MobileApiController::class, 'participantSupplies'])->name('participants.supplies');
    Route::post('participants/{id}/supplies', [MobileApiController::class, 'syncParticipantSupplies'])->name('participants.sync-supplies');
});


