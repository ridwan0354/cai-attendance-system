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

                $responseData = $response->json();

                if ($response->successful() && ($responseData['status'] ?? false)) {
                    return [
                        'success' => true,
                        'message_id' => $responseData['id'] ?? null,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $responseData['reason'] ?? json_encode($responseData),
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

        $methodLabel = match ($attendance->method) {
            'face' => 'Pindai Wajah (Face Recognition) 📷',
            'qr' => 'Pindai Kode QR 📱',
            'rfid' => 'Pindai Kartu RFID 💳',
            default => 'Manual ✏️',
        };

        $groupName = $participant->group?->name ?? 'Tidak Ada';

        $message = "✅ *Konfirmasi Kehadiran CAI LOMBOK 2026*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "Halo *{$participant->name}*,\n";
        $message .= "Kehadiran Anda berhasil tercatat di sistem kami:\n\n";
        $message .= "📅 Sesi: *{$session->name}*\n";
        $message .= "👥 Kelompok: *{$groupName}*\n";
        $message .= "⏰ Waktu Absen: *{$attendance->check_in_time->format('H:i:s')}*\n";
        $message .= "👤 Metode: *{$methodLabel}*\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Terima kasih atas partisipasinya!\n\n";
        $message .= "_Pesan otomatis - CAI Lombok 2026_";

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
        $minutesLeft = max(0, now()->diffInMinutes($endTime, false));

        // Gender stats
        $totalMale = $participants->filter(fn($p) => $p->gender === 'Laki-laki')->count();
        $totalFemale = $participants->filter(fn($p) => $p->gender === 'Perempuan')->count();
        $presentMale = $present->filter(fn($p) => $p->gender === 'Laki-laki')->count();
        $presentFemale = $present->filter(fn($p) => $p->gender === 'Perempuan')->count();
        $absentMale = $absent->filter(fn($p) => $p->gender === 'Laki-laki')->count();
        $absentFemale = $absent->filter(fn($p) => $p->gender === 'Perempuan')->count();

        $message = "📋 *Laporan Kehadiran CAI LOMBOK 2026*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📅 Sesi: *{$session->name}*\n";
        $message .= "👥 Grup: *{$group->name}*\n";
        $message .= "🗓️ Hari ke-{$session->day_number} | " . $session->date->format('d M Y') . "\n\n";

        $message .= "📊 *Rincian Utusan:*\n";
        $message .= "- Total Laki-laki: *{$totalMale}*\n";
        $message .= "- Total Perempuan: *{$totalFemale}*\n";
        $message .= "- Total Peserta: *{$stats['total']}*\n\n";

        $message .= "✅ *Sudah Hadir ({$stats['present']}):*\n";
        $message .= "- Laki-laki: *{$presentMale}*\n";
        $message .= "- Perempuan: *{$presentFemale}*\n\n";

        $message .= "❌ *Belum Hadir ({$stats['absent']}):*\n";
        $message .= "- Laki-laki: *{$absentMale}*\n";
        $message .= "- Perempuan: *{$absentFemale}*\n\n";

        // Absent list detailed
        if ($absent->isNotEmpty()) {
            $message .= "📝 *Daftar Peserta Belum Hadir:*\n";
            $no = 1;
            foreach ($absent as $p) {
                $gLabel = $p->gender === 'Laki-laki' ? 'L' : 'P';
                $message .= "{$no}. {$p->name} ({$gLabel})\n";
                $no++;
            }
            $message .= "\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 Persentase Kehadiran: *{$stats['percentage']}%*\n";
        $message .= "⏳ Sisa waktu sesi: *{$minutesLeft} menit*\n";
        $message .= "\n_Pesan otomatis - CAI Lombok 2026_";

        return $message;
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
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            return '62' . $phone;
        }

        return $phone;
    }
}

