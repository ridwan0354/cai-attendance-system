<?php

namespace App\Services;

use App\Models\Group;
use App\Models\NotificationLog;
use App\Models\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteWhatsAppService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.fonnte.com/send';

    public function __construct()
    {
        $this->apiKey = config('services.fonnte.api_key', '');
    }

    /**
     * Send attendance report to a group's Pembina via WhatsApp.
     */
    public function sendAttendanceReport(Group $group, Session $session): bool
    {
        if (empty($this->apiKey)) {
            Log::error('Fonnte API key is not configured');
            return false;
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

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl, [
                'target'  => $phone,
                'message' => $message,
            ]);

            $responseData = $response->json();

            if ($response->successful() && ($responseData['status'] ?? false)) {
                $log->update([
                    'status'             => 'sent',
                    'fonnte_message_id'  => $responseData['id'] ?? null,
                    'sent_at'            => now(),
                ]);
                Log::info("WA sent to {$group->pembina_name} ({$phone})");
                return true;
            }

            $log->update([
                'status'        => 'failed',
                'error_message' => json_encode($responseData),
            ]);
            return false;

        } catch (\Exception $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Fonnte send failed', ['error' => $e->getMessage()]);
            return false;
        }
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

        $message = "📋 *Laporan Kehadiran CAI LOMBOK 2026*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📅 Sesi: *{$session->name}*\n";
        $message .= "👥 Grup: *{$group->name}*\n";
        $message .= "🗓️ Hari ke-{$session->day_number} | " . $session->date->format('d M Y') . "\n\n";

        // Present list
        $message .= "✅ *Hadir ({$stats['present']}/{$stats['total']}):*\n";
        $no = 1;
        foreach ($present as $p) {
            $time = $p->attendances->first()->check_in_time->format('H:i');
            $message .= "{$no}. {$p->name} - ⏰ {$time}\n";
            $no++;
        }

        // Absent list
        if ($absent->isNotEmpty()) {
            $message .= "\n❌ *Belum Hadir ({$stats['absent']}):*\n";
            $no = 1;
            foreach ($absent as $p) {
                $message .= "{$no}. {$p->name}\n";
                $no++;
            }
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 Kehadiran: *{$stats['percentage']}%*\n";
        $message .= "⏳ Sisa waktu sesi: *{$minutesLeft} menit*\n";
        $message .= "\n_Pesan otomatis - CAI Lombok 2026_";

        return $message;
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
