<?php

namespace App\Services;

use App\Models\Group;
use App\Models\NotificationLog;
use App\Models\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteWhatsAppService
{
    private string $gateway;
    private string $fonnteApiKey;
    private string $apiUrl = 'https://api.fonnte.com/send';
    private string $grooviteApiUrl;
    private string $grooviteWaKey;

    public function __construct()
    {
        $this->gateway = (string) \App\Models\Setting::getVal('wa_gateway', 'fonnte');
        $this->fonnteApiKey = (string) \App\Models\Setting::getVal('fonnte_api_key', '');
        $this->grooviteApiUrl = (string) \App\Models\Setting::getVal('groovite_api_url', 'https://waa.galipatsistem.com/api');
        $this->grooviteWaKey = (string) \App\Models\Setting::getVal('groovite_wa_key', '');
    }

    /**
     * Send message using the active gateway.
     * Returns ['success' => bool, 'message_id' => ?string, 'error' => ?string]
     */
    public function sendMessage(string $phone, string $message): array
    {
        $normalizedPhone = $this->normalizePhone($phone);
        if (empty($normalizedPhone) || strlen($normalizedPhone) < 10) {
            return ['success' => false, 'error' => 'Nomor HP tidak valid / kosong (minimal 10 digit).'];
        }

        if ($this->gateway === 'groovite') {
            if (empty($this->grooviteWaKey)) {
                return ['success' => false, 'error' => 'API Key/waKey Groovite belum dikonfigurasi.'];
            }

            $url = rtrim($this->grooviteApiUrl, '/') . '/send-message';
            $jid = $normalizedPhone . '@s.whatsapp.net';

            try {
                $response = Http::post($url, [
                    'waKey' => $this->grooviteWaKey,
                    'id' => $jid,
                    'text' => $message,
                    'userId' => null,
                ]);

                $responseData = $response->json();

                if ($response->successful()) {
                    $msgId = $responseData['id'] ?? $responseData['data']['id'] ?? $responseData['message_id'] ?? null;
                    return [
                        'success' => true,
                        'message_id' => $msgId,
                    ];
                }

                return [
                    'success' => false,
                    'error' => json_encode($responseData) ?: 'HTTP status ' . $response->status(),
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        } else {
            // Default to Fonnte
            if (empty($this->fonnteApiKey)) {
                return ['success' => false, 'error' => 'API Key Fonnte belum dikonfigurasi.'];
            }

            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->fonnteApiKey,
                ])->post($this->apiUrl, [
                    'target'  => $normalizedPhone,
                    'message' => $message,
                ]);

                $responseData = $response->json() ?? [];

                $isSuccess = $response->successful() && (
                    ($responseData['status'] ?? false) === true ||
                    ($responseData['status'] ?? '') === 'true' ||
                    ($responseData['status'] ?? 0) === 1 ||
                    ($responseData['status'] ?? '') === '1' ||
                    !empty($responseData['id'])
                );

                if ($isSuccess) {
                    $msgId = is_array($responseData['id'] ?? null) 
                        ? implode(',', $responseData['id']) 
                        : ($responseData['id'] ?? null);

                    return [
                        'success' => true,
                        'message_id' => $msgId,
                    ];
                }

                $errorMsg = $responseData['reason'] ?? $responseData['message'] ?? json_encode($responseData);
                return [
                    'success' => false,
                    'error' => is_string($errorMsg) ? $errorMsg : json_encode($errorMsg),
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Send attendance report to a group's Pembina via WhatsApp.
     */
    public function sendAttendanceReport(Group $group, Session $session): bool
    {
        // Check toggle ON/OFF setting
        $notifyEnabled = (string) \App\Models\Setting::getVal('notify_pembina_enabled', '1');
        if ($notifyEnabled !== '1') {
            Log::info("WA Attendance report to pembina disabled in settings. Skipping.");
            return true;
        }

        $message = $this->buildReportMessage($group, $session);
        $phone = $this->normalizePhone($group->pembina_phone);

        // Log attempt
        $log = NotificationLog::create([
            'group_id'     => $group->id,
            'session_id'   => $session->id,
            'phone_number' => $phone,
            'message'      => $message,
            'status'       => 'pending',
        ]);

        $res = $this->sendMessage($phone, $message);

        if ($res['success']) {
            $log->update([
                'status'             => 'sent',
                'fonnte_message_id'  => $res['message_id'] ?? null,
                'sent_at'            => now(),
            ]);
            Log::info("WA sent to {$group->pembina_name} ({$phone})");
            return true;
        }

        $log->update([
            'status'        => 'failed',
            'error_message' => $res['error'] ?? 'Unknown error',
        ]);
        Log::error('WA send attendance report failed', ['error' => $res['error'] ?? 'Unknown error']);
        return false;
    }

    /**
     * Send check-in confirmation to a participant via WhatsApp.
     */
    public function sendCheckInConfirmation(\App\Models\Attendance $attendance): bool
    {
        // Check toggle ON/OFF setting
        $notifyEnabled = (string) \App\Models\Setting::getVal('notify_peserta_enabled', '1');
        if ($notifyEnabled !== '1') {
            Log::info("WA Check-in confirmation to participant disabled in settings. Skipping.");
            return true;
        }

        $participant = $attendance->participant;
        if (!$participant || empty($participant->phone)) {
            Log::warning("No phone number found for participant {$participant?->id}. Skipping WA confirmation.");
            return false;
        }

        $session = $attendance->session;
        $phone = $this->normalizePhone($participant->phone);

        // Check if already sent check-in confirmation to this phone for this session
        $alreadySent = NotificationLog::where('session_id', $session->id)
            ->where('phone_number', $phone)
            ->where('status', 'sent')
            ->exists();

        if ($alreadySent) {
            Log::info("WA Check-in confirmation already sent to {$phone} for session {$session->id}. Skipping.");
            return true;
        }

        // Apply throttling delay (default 13 seconds between participant WA messages to prevent anti-spam block)
        $delayInterval = (int) \App\Models\Setting::getVal('wa_send_delay_seconds', 13);
        if ($delayInterval > 0) {
            $lock = \Illuminate\Support\Facades\Cache::lock('wa_participant_send_lock', 10);
            $targetTimestamp = 0;
            try {
                $lock->block(5);
                $currentTime = time();
                $lastScheduled = (int) \Illuminate\Support\Facades\Cache::get('wa_last_participant_send_time', 0);

                if ($lastScheduled <= $currentTime) {
                    $targetTimestamp = $currentTime;
                } else {
                    $targetTimestamp = $lastScheduled + $delayInterval;
                }

                \Illuminate\Support\Facades\Cache::put('wa_last_participant_send_time', $targetTimestamp, 3600);
            } catch (\Exception $e) {
                Log::warning("WA Send throttling lock error: " . $e->getMessage());
                $targetTimestamp = time();
            } finally {
                optional($lock)->release();
            }

            $sleepSeconds = $targetTimestamp - time();
            if ($sleepSeconds > 0) {
                Log::info("Throttling WA send to {$participant->name} ({$phone}): sleeping {$sleepSeconds}s (interval: {$delayInterval}s)");
                sleep($sleepSeconds);
            }
        }

        $methodLabel = match ($attendance->method) {
            'face' => 'Pindai Wajah (Face Recognition) 📷',
            'qr' => 'Pindai Kode QR 📱',
            'rfid' => 'Pindai Kartu RFID 💳',
            default => 'Manual ✏️',
        };

        $groupName = $participant->group?->name ?? 'Tidak Ada';

        // Load custom template or default template
        $template = (string) \App\Models\Setting::getVal(
            'peserta_message_template',
            \App\Models\Setting::defaultPesertaTemplate()
        );

        $message = str_replace([
            '{nama_peserta}', '{participant_name}',
            '{nama_sesi}', '{session_name}',
            '{kelompok}', '{group_name}',
            '{jam_absen}', '{check_in_time}',
            '{metode}', '{method}'
        ], [
            $participant->name, $participant->name,
            $session->name, $session->name,
            $groupName, $groupName,
            $attendance->check_in_time->format('H:i:s'), $attendance->check_in_time->format('H:i:s'),
            $methodLabel, $methodLabel
        ], $template);

        // Log attempt (group_id is required by DB schema)
        $log = NotificationLog::create([
            'group_id'     => $participant->group_id,
            'session_id'   => $session->id,
            'phone_number' => $phone,
            'message'      => $message,
            'status'       => 'pending',
        ]);

        $res = $this->sendMessage($phone, $message);

        if ($res['success']) {
            $log->update([
                'status'             => 'sent',
                'fonnte_message_id'  => $res['message_id'] ?? null,
                'sent_at'            => now(),
            ]);
            Log::info("WA Check-in confirmation sent to participant {$participant->name} ({$phone})");
            return true;
        }

        $log->update([
            'status'        => 'failed',
            'error_message' => $res['error'] ?? 'Unknown error',
        ]);
        Log::error("Failed to send WA Check-in confirmation to {$phone}", ['error' => $res['error'] ?? 'Unknown error']);
        return false;
    }

    /**
     * Send all sessions attendance summary report to a group's Pembina via WhatsApp.
     */
    public function sendAllSessionsRecapReport(Group $group): bool
    {
        // Check toggle ON/OFF setting
        $notifyEnabled = (string) \App\Models\Setting::getVal('notify_pembina_enabled', '1');
        if ($notifyEnabled !== '1') {
            Log::info("WA Attendance report to pembina disabled in settings. Skipping.");
            return true;
        }

        $message = $this->buildAllSessionsRecapMessage($group);
        $phone = $this->normalizePhone($group->pembina_phone);

        if (empty($phone)) {
            Log::warning("No phone number found for pembina of group {$group->name}. Skipping.");
            return false;
        }

        $firstSessionId = Session::orderBy('day_number')->orderBy('start_time')->first()?->id ?? 1;

        // Log attempt
        $log = NotificationLog::create([
            'group_id'     => $group->id,
            'session_id'   => $firstSessionId,
            'phone_number' => $phone,
            'message'      => $message,
            'status'       => 'pending',
        ]);

        $res = $this->sendMessage($phone, $message);

        if ($res['success']) {
            $log->update([
                'status'            => 'sent',
                'fonnte_message_id' => $res['message_id'] ?? null,
                'sent_at'           => now(),
            ]);
            Log::info("WA All Sessions Recap sent to {$group->pembina_name} ({$phone})");
            return true;
        }

        $log->update([
            'status'        => 'failed',
            'error_message' => $res['error'] ?? 'Unknown error',
        ]);
        Log::error('WA send all sessions recap report failed', ['error' => $res['error'] ?? 'Unknown error']);
        return false;
    }

    /**
     * Build the formatted WhatsApp all-sessions attendance recap message.
     */
    private function buildAllSessionsRecapMessage(Group $group): string
    {
        $allSessions = Session::orderBy('day_number')->orderBy('start_time')->get();
        $totalSessionsCount = $allSessions->count();

        $participants = $group->participants()->with('attendances')->orderBy('name')->get();
        $totalParticipantsCount = $participants->count();

        $lines = [];
        $lines[] = "📊 *REKAP KEHADIRAN SEMUA SESI KEGIATAN*";
        $lines[] = "----------------------------------------";
        $lines[] = "📌 *Kelompok:* " . $group->name;
        $lines[] = "👤 *Pembina:* " . ($group->pembina_name ?? '-');
        $lines[] = "📅 *Total Sesi Kegiatan:* " . $totalSessionsCount . " Sesi";
        $lines[] = "";
        $lines[] = "👥 *DAFTAR KEHADIRAN PESERTA:*";

        if ($participants->isEmpty()) {
            $lines[] = "_(Belum ada data peserta dalam kelompok ini)_";
        } else {
            $no = 1;
            $totalPresentSum = 0;

            foreach ($participants as $p) {
                $attendedSessionIds = $p->attendances->pluck('session_id')->unique();
                $attendedCount = $attendedSessionIds->count();
                $totalPresentSum += $attendedCount;

                $pct = $totalSessionsCount > 0 ? round(($attendedCount / $totalSessionsCount) * 100) : 0;
                $genderLabel = $p->gender === 'Perempuan' ? 'P' : 'L';

                $starBadge = ($attendedCount === $totalSessionsCount && $totalSessionsCount > 0) ? " ⭐ (Full)" : "";

                $lines[] = "{$no}. *{$p->name}* ({$genderLabel})";
                $lines[] = "   └ Hadir: *{$attendedCount}/{$totalSessionsCount} Sesi* ({$pct}%){$starBadge}";

                // Mention missed sessions if any
                if ($attendedCount < $totalSessionsCount && $totalSessionsCount > 0) {
                    $missedSessions = $allSessions->reject(fn($s) => $attendedSessionIds->contains($s->id))->pluck('name')->toArray();
                    if (!empty($missedSessions)) {
                        $lines[] = "   └ Absen: " . implode(', ', $missedSessions);
                    }
                }

                $no++;
            }

            $overallPct = ($totalParticipantsCount > 0 && $totalSessionsCount > 0) 
                ? round(($totalPresentSum / ($totalParticipantsCount * $totalSessionsCount)) * 100) 
                : 0;

            $lines[] = "";
            $lines[] = "📈 *REKAPITULASI KELOMPOK:*";
            $lines[] = "• Total Peserta: {$totalParticipantsCount} Orang";
            $lines[] = "• Rata-rata Kehadiran: {$overallPct}%";
            $lines[] = "• Waktu Rekap: " . now()->format('d/m/Y H:i') . " WITA";
        }

        $lines[] = "";
        $lines[] = "_CAI LOMBOK 2026 Attendance System_";

        return implode("\n", $lines);
    }

    /**
     * Build the formatted WhatsApp attendance report message.
     */
    private function buildReportMessage(Group $group, Session $session): string
    {
        $participants = $group->participants()->with(['attendances' => function ($q) use ($session) {
            $q->where('session_id', $session->id);
        }])->get();

        $present = $participants->filter(fn($p) => $p->attendances->isNotEmpty());
        $absent  = $participants->filter(fn($p) => $p->attendances->isEmpty());

        $stats = $group->getAttendanceStats($session->id);
        $endTime = \Carbon\Carbon::parse($session->date->format('Y-m-d') . ' ' . $session->end_time);
        $minutesLeft = (int) max(0, round(now()->diffInMinutes($endTime, false)));

        // Gender stats
        $totalMale = $participants->filter(fn($p) => $p->gender === 'Laki-laki')->count();
        $totalFemale = $participants->filter(fn($p) => $p->gender === 'Perempuan')->count();
        $presentMale = $present->filter(fn($p) => $p->gender === 'Laki-laki')->count();
        $presentFemale = $present->filter(fn($p) => $p->gender === 'Perempuan')->count();
        $absentMale = max(0, $totalMale - $presentMale);
        $absentFemale = max(0, $totalFemale - $presentFemale);

        if ($absent->isEmpty()) {
            $absentListText = "🎉 Semua peserta telah hadir!";
        } else {
            $no = 1;
            $lines = [];
            foreach ($absent as $p) {
                $gLabel = $p->gender === 'Laki-laki' ? 'L' : 'P';
                $lines[] = "{$no}. {$p->name} ({$gLabel})";
                $no++;
            }
            $absentListText = implode("\n", $lines);
        }

        $template = (string) \App\Models\Setting::getVal(
            'pembina_message_template',
            \App\Models\Setting::defaultPembinaTemplate()
        );

        return str_replace([
            '{nama_sesi}', '{session_name}',
            '{kelompok}', '{group_name}',
            '{nama_pembina}', '{pembina_name}',
            '{jam_absen}', '{check_in_time}',
            '{total_peserta}', '{total_participants}',
            '{jumlah_hadir}', '{present_count}',
            '{jumlah_tidak_hadir}', '{absent_count}',
            '{persentase}', '{percentage}',
            '{hadir_laki_laki}', '{total_laki_laki}', '{absent_laki_laki}',
            '{hadir_perempuan}', '{total_perempuan}', '{absent_perempuan}',
            '{daftar_belum_hadir}', '{absent_list}',
            '{sisa_menit}', '{minutes_left}'
        ], [
            $session->name, $session->name,
            $group->name, $group->name,
            $group->pembina_name ?? 'Pembina', $group->pembina_name ?? 'Pembina',
            now()->format('H:i:s'), now()->format('H:i:s'),
            $stats['total'], $stats['total'],
            $stats['present'], $stats['present'],
            $stats['absent'], $stats['absent'],
            $stats['percentage'], $stats['percentage'],
            $presentMale, $totalMale, $absentMale,
            $presentFemale, $totalFemale, $absentFemale,
            $absentListText, $absentListText,
            $minutesLeft, $minutesLeft
        ], $template);
    }

    /**
     * Send a generic message to any number.
     * Returns ['success' => bool, 'message' => string]
     */
    public function sendGenericMessage(string $phone, string $message, ?string $overrideKey = null): array
    {
        if ($overrideKey) {
            if ($this->gateway === 'groovite') {
                $tempApiKey = $this->grooviteWaKey;
                $this->grooviteWaKey = $overrideKey;
                $res = $this->sendMessage($phone, $message);
                $this->grooviteWaKey = $tempApiKey;
            } else {
                $tempApiKey = $this->fonnteApiKey;
                $this->fonnteApiKey = $overrideKey;
                $res = $this->sendMessage($phone, $message);
                $this->fonnteApiKey = $tempApiKey;
            }
        } else {
            $res = $this->sendMessage($phone, $message);
        }

        if ($res['success']) {
            return ['success' => true, 'message' => 'Pesan berhasil terkirim.'];
        }

        return ['success' => false, 'message' => $res['error'] ?? 'Unknown error'];
    }

    /**
     * Normalize phone number to Indonesian WhatsApp format.
     * Example: 081234567890 → 6281234567890
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (empty($digits)) {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (!str_starts_with($digits, '62')) {
            return '62' . $digits;
        }

        return $digits;
    }
}

