<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Participant;
use App\Models\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * MobileApiController
 *
 * Menyediakan API endpoints untuk aplikasi Android CAI Attendance.
 * Mendukung sinkronisasi data peserta (foto, embedding) ke lokal device
 * dan pencatatan absensi dari device (online maupun queued offline).
 *
 * Auth: menggunakan header X-Api-Key yang dikonfigurasi di .env MOBILE_API_KEY
 */
class MobileApiController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Auth middleware helper — validasi API Key dari header
    // ─────────────────────────────────────────────────────────────────────────

    private function checkApiKey(Request $request): bool
    {
        $key = config('mobile.api_key', env('MOBILE_API_KEY', ''));
        if (empty($key)) {
            // Jika belum dikonfigurasi, izinkan sementara (dev mode)
            return true;
        }
        return $request->header('X-Api-Key') === $key;
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Pastikan X-Api-Key header sudah benar.',
        ], 401);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/mobile/participants
    // Sync daftar peserta (incremental — gunakan ?since= untuk update saja)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mengembalikan daftar peserta yang terdaftar wajahnya.
     * Mendukung incremental sync via query parameter `since` (ISO 8601).
     *
     * @queryParam since  string  Timestamp ISO-8601. Jika diisi, hanya peserta yang
     *                            diperbarui setelah waktu ini yang dikembalikan.
     * @queryParam page   int     Nomor halaman (default: 1)
     * @queryParam per_page int   Jumlah per halaman (default: 50, max: 200)
     */
    public function participants(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        $query = Participant::with('group')
            ->select([
                'id', 'name', 'nik', 'group_id',
                'photo_path', 'face_registered',
                'updated_at', 'created_at',
            ]);

        // Incremental sync: hanya peserta yang diperbarui sejak timestamp tertentu
        if ($request->has('since')) {
            try {
                $since = \Carbon\Carbon::parse($request->input('since'));
                $query->where('updated_at', '>', $since);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format parameter `since` tidak valid. Gunakan ISO-8601 (contoh: 2026-01-01T00:00:00Z)',
                ], 422);
            }
        }

        $perPage = min((int) $request->input('per_page', 50), 200);
        $paginated = $query->orderBy('updated_at', 'desc')->paginate($perPage);

        $data = $paginated->map(function (Participant $p) {
            $photoPath = base_path('../python-face-service/face_db/' . $p->id . '/photo.jpg');
            $hasPhoto  = file_exists($photoPath);

            return [
                'id'              => $p->id,
                'name'            => $p->name,
                'nik'             => $p->nik,
                'group_id'        => $p->group_id,
                'group_name'      => $p->group?->name,
                'group_color'     => $p->group?->color,
                'face_registered' => $p->face_registered,
                'has_photo'       => $hasPhoto,
                'photo_hash'      => $hasPhoto ? md5_file($photoPath) : null,
                'updated_at'      => $p->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success'     => true,
            'data'        => $data,
            'total'       => $paginated->total(),
            'page'        => $paginated->currentPage(),
            'last_page'   => $paginated->lastPage(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/mobile/participants/{id}/photo
    // Unduh foto peserta dalam resolusi yang dioptimalkan untuk Android
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mengembalikan foto wajah peserta sebagai JPEG.
     * Foto diambil dari direktori face_db milik Python face service.
     *
     * @queryParam size int  Ukuran target dalam pixel (default: 320, max: 640)
     */
    public function participantPhoto(Request $request, int $id): Response|JsonResponse
    {
        try {
            if (!$this->checkApiKey($request)) {
                return $this->unauthorizedResponse();
            }

            $participant = Participant::find($id);
            if (!$participant) {
                return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
            }

            $photoPath = base_path('../python-face-service/face_db/' . $id . '/photo.jpg');

            if (!file_exists($photoPath)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Foto belum terdaftar di path: ' . $photoPath
                ], 404);
            }

            // Baca konten secara langsung untuk meminimalkan kendala file-lock/permission dari Symfony BinaryFileResponse
            $content = @file_get_contents($photoPath);
            if ($content === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca file foto wajah. Pastikan webserver memiliki izin membaca file: ' . $photoPath
                ], 500);
            }

            // Serve langsung sebagai binary JPEG
            return response($content, 200, [
                'Content-Type'  => 'image/jpeg',
                'Cache-Control' => 'public, max-age=86400',
                'ETag'          => md5($content),
                'X-Participant-Id' => $id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/mobile/sessions/active
    // Dapatkan sesi absensi yang sedang aktif
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mengembalikan sesi yang sedang aktif untuk digunakan Android scanner.
     * Jika tidak ada sesi aktif, mengembalikan 404.
     */
    public function activeSession(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        $session = Session::getActive();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada sesi aktif saat ini.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'session' => [
                'id'         => $session->id,
                'name'       => $session->name,
                'day_number' => $session->day_number,
                'date'       => $session->date?->toDateString(),
                'start_time' => $session->start_time,
                'end_time'   => $session->end_time,
                'is_active'  => $session->is_active,
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/mobile/sessions
    // Daftar semua sesi (untuk keperluan pemilihan manual di Android)
    // ─────────────────────────────────────────────────────────────────────────

    public function sessions(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        $sessions = Session::orderBy('day_number')->orderBy('start_time')->get()->map(fn($s) => [
            'id'         => $s->id,
            'name'       => $s->name,
            'day_number' => $s->day_number,
            'date'       => $s->date?->toDateString(),
            'start_time' => $s->start_time,
            'end_time'   => $s->end_time,
            'is_active'  => $s->is_active,
        ]);

        return response()->json(['success' => true, 'data' => $sessions]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/mobile/attendance
    // Catat absensi dari Android (satu atau batch)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mencatat absensi yang dikirim oleh Android app.
     * Mendukung single record maupun batch (array of records) untuk upload
     * saat device kembali online setelah mode offline.
     *
     * Body (single):
     * {
     *   "participant_id": 1,
     *   "session_id": 2,
     *   "method": "face",
     *   "confidence_score": 0.92,
     *   "check_in_time": "2026-07-06T08:00:00+08:00"
     * }
     *
     * Body (batch):
     * {
     *   "records": [ {...}, {...} ]
     * }
     */
    public function recordAttendance(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        // Support batch upload (offline queue dari Android)
        if ($request->has('records')) {
            return $this->recordBatch($request);
        }

        // Single record
        $request->validate([
            'participant_id'   => 'required|integer|exists:participants,id',
            'session_id'       => 'required|integer|exists:event_sessions,id',
            'method'           => 'required|in:face,rfid,qr,manual',
            'confidence_score' => 'nullable|numeric|min:0|max:1',
            'check_in_time'    => 'nullable|date',
        ]);

        $result = $this->doRecord(
            participantId:   $request->integer('participant_id'),
            sessionId:       $request->integer('session_id'),
            method:          $request->string('method'),
            confidenceScore: $request->input('confidence_score'),
            checkInTime:     $request->input('check_in_time'),
        );

        if ($result['already_present']) {
            return response()->json([
                'success'        => true,
                'already_present'=> true,
                'message'        => 'Peserta sudah tercatat hadir di sesi ini.',
                'attendance'     => $result,
            ]);
        }

        return response()->json([
            'success'    => true,
            'attendance' => $result,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/mobile/sync/info
    // Info statistik sync (berapa peserta yang punya foto, timestamp terbaru)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mengembalikan statistik sinkronisasi yang digunakan Android untuk
     * menentukan apakah perlu sync atau tidak.
     */
    public function syncInfo(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        $faceDbPath = base_path('../python-face-service/face_db/');

        $totalWithPhoto = 0;
        if (is_dir($faceDbPath)) {
            $dirs = glob($faceDbPath . '*/photo.jpg');
            $totalWithPhoto = count($dirs ?? []);
        }

        $totalParticipants = Participant::where('face_registered', true)->count();
        $lastUpdated       = Participant::max('updated_at');

        return response()->json([
            'success'            => true,
            'total_participants' => $totalParticipants,
            'total_with_photo'   => $totalWithPhoto,
            'last_updated'       => $lastUpdated ? \Carbon\Carbon::parse($lastUpdated)->toIso8601String() : null,
            'server_time'        => now()->toIso8601String(),
            'server_version'     => '2026.1',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/mobile/participants/{id}/register-face
    // Daftarkan wajah peserta dari Android ke Laravel + Python Face Service
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Menerima base64 foto wajah peserta dari Android app,
     * menyimpannya di server, mendaftarkan ke Python face service,
     * dan mengubah status face_registered menjadi true.
     */
    public function registerFace(Request $request, int $id): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        $request->validate([
            'image' => 'required|string', // base64 string
        ]);

        $participant = Participant::find($id);
        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan.',
            ], 404);
        }

        // Panggil FaceRecognitionService bawaan Laravel untuk daftarkan ke Python service
        $faceService = app(\App\Services\FaceRecognitionService::class);
        $result = $faceService->registerFace($participant->id, $participant->name, $request->input('image'));

        if ($result['success'] ?? false) {
            $participant->update([
                'face_registered' => true,
                'updated_at' => now(), // Memaksa update timestamp agar kedetect saat sync di HP lain
            ]);

            return response()->json([
                'success' => true,
                'message' => "Wajah {$participant->name} berhasil didaftarkan!",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Gagal mendaftarkan wajah di server.',
        ], 422);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Proses batch upload absensi offline dari Android.
     */
    private function recordBatch(Request $request): JsonResponse
    {
        $request->validate([
            'records'                          => 'required|array|min:1|max:500',
            'records.*.participant_id'         => 'required|integer',
            'records.*.session_id'             => 'required|integer',
            'records.*.method'                 => 'required|in:face,rfid,qr,manual',
            'records.*.confidence_score'       => 'nullable|numeric|min:0|max:1',
            'records.*.check_in_time'          => 'nullable|date',
        ]);

        $results = [];
        $successCount = 0;
        $skipCount    = 0;
        $errorCount   = 0;

        foreach ($request->input('records') as $idx => $record) {
            // Validasi participant & session ada di DB
            $participantExists = Participant::where('id', $record['participant_id'])->exists();
            $sessionExists     = \App\Models\Session::where('id', $record['session_id'])->exists();

            if (!$participantExists || !$sessionExists) {
                $results[] = ['index' => $idx, 'status' => 'error', 'message' => 'Participant atau session tidak ditemukan.'];
                $errorCount++;
                continue;
            }

            try {
                $result = $this->doRecord(
                    participantId:   $record['participant_id'],
                    sessionId:       $record['session_id'],
                    method:          $record['method'],
                    confidenceScore: $record['confidence_score'] ?? null,
                    checkInTime:     $record['check_in_time'] ?? null,
                );

                if ($result['already_present']) {
                    $results[] = ['index' => $idx, 'status' => 'skipped', 'participant_id' => $record['participant_id']];
                    $skipCount++;
                } else {
                    $results[] = ['index' => $idx, 'status' => 'recorded', 'attendance_id' => $result['id'] ?? null];
                    $successCount++;
                }
            } catch (\Exception $e) {
                Log::error('Mobile batch attendance error', ['index' => $idx, 'error' => $e->getMessage()]);
                $results[] = ['index' => $idx, 'status' => 'error', 'message' => 'Internal error.'];
                $errorCount++;
            }
        }

        return response()->json([
            'success'       => true,
            'total'         => count($request->input('records')),
            'recorded'      => $successCount,
            'skipped'       => $skipCount,
            'errors'        => $errorCount,
            'results'       => $results,
        ]);
    }

    /**
     * Core logic pencatatan absensi (digunakan oleh single & batch).
     */
    private function doRecord(
        int     $participantId,
        int     $sessionId,
        string  $method,
        ?float  $confidenceScore,
        ?string $checkInTime,
    ): array {
        $participant = Participant::with('group')->findOrFail($participantId);

        // Cek duplikat
        $existing = Attendance::where('participant_id', $participantId)
            ->where('session_id', $sessionId)
            ->first();

        if ($existing) {
            return [
                'id'               => $existing->id,
                'participant_id'   => $participantId,
                'participant_name' => $participant->name,
                'group_name'       => $participant->group?->name,
                'group_color'      => $participant->group?->color,
                'check_in_time'    => $existing->check_in_time?->toIso8601String(),
                'method'           => $existing->method,
                'confidence_score' => $existing->confidence_score,
                'already_present'  => true,
            ];
        }

        // Gunakan waktu dari Android (saat scan offline) atau fallback ke now()
        // Konversi eksplisit ke timezone aplikasi agar tersimpan dengan jam lokal yang benar
        $checkedAt = $checkInTime 
            ? \Carbon\Carbon::parse($checkInTime)->setTimezone(config('app.timezone', 'Asia/Makassar')) 
            : now();

        $attendance = Attendance::create([
            'participant_id'   => $participantId,
            'session_id'       => $sessionId,
            'check_in_time'    => $checkedAt,
            'method'           => $method,
            'confidence_score' => $confidenceScore,
        ]);

        // Broadcast WebSocket ke dashboard (jika ada)
        try {
            broadcast(new \App\Events\AttendanceRecorded($attendance));
        } catch (\Exception $e) {
            Log::warning('Mobile: Failed to broadcast attendance', ['error' => $e->getMessage()]);
        }

        Log::info("Mobile attendance recorded: {$participant->name} [{$method}] session {$sessionId}");

        return [
            'id'               => $attendance->id,
            'participant_id'   => $participantId,
            'participant_name' => $participant->name,
            'group_name'       => $participant->group?->name,
            'group_color'      => $participant->group?->color,
            'check_in_time'    => $attendance->check_in_time?->toIso8601String(),
            'method'           => $method,
            'confidence_score' => $confidenceScore,
            'already_present'  => false,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/mobile/supplies
    // Daftar semua jenis barang registrasi global
    // ─────────────────────────────────────────────────────────────────────────
    public function supplies(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        $supplies = \App\Models\Supply::orderBy('name')->get()->map(fn($s) => [
            'id'   => $s->id,
            'name' => $s->name,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $supplies,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/mobile/participants/{id}/supplies
    // Daftar status pengambilan barang untuk peserta tertentu
    // ─────────────────────────────────────────────────────────────────────────
    public function participantSupplies(Request $request, int $id): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        $participant = Participant::find($id);
        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
        }

        $supplies = \App\Models\Supply::orderBy('name')->get()->map(fn($s) => [
            'id'       => $s->id,
            'name'     => $s->name,
            'received' => $participant->supplies()->where('supply_id', $s->id)->exists(),
        ]);

        return response()->json([
            'success'  => true,
            'data'     => $supplies,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/mobile/participants/{id}/supplies
    // Menyinkronkan/menyimpan barang registrasi yang diambil peserta
    // ─────────────────────────────────────────────────────────────────────────
    public function syncParticipantSupplies(Request $request, int $id): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return $this->unauthorizedResponse();
        }

        $participant = Participant::find($id);
        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
        }

        $request->validate([
            'supplies'   => 'nullable|array',
            'supplies.*' => 'integer|exists:supplies,id',
        ]);

        // Sinkronisasi barang ke pivot table
        $participant->supplies()->sync($request->input('supplies', []));

        return response()->json([
            'success' => true,
            'message' => 'Registrasi barang berhasil diperbarui.',
        ]);
    }
}

