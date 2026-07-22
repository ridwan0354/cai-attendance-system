<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get setting value by key.
     */
    public static function getVal(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key.
     */
    public static function setVal(string $key, $value)
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Default message template for participants.
     */
    public static function defaultPesertaTemplate(): string
    {
        return "✅ *Konfirmasi Kehadiran CAI LOMBOK 2026*\n" .
            "━━━━━━━━━━━━━━━━━━━━\n\n" .
            "Halo *{nama_peserta}*,\n" .
            "Kehadiran Anda berhasil tercatat di sistem kami:\n\n" .
            "📅 Sesi: *{nama_sesi}*\n" .
            "👥 Kelompok: *{kelompok}*\n" .
            "⏰ Waktu Absen: *{jam_absen}*\n" .
            "👤 Metode: *{metode}*\n\n" .
            "📸 *Jangan lupa cetak dokumentasi foto-foto keren kamu selama acara hanya dengan Rp10.000 saja!*\n" .
            "Kunjungi link berikut untuk mencetak: https://twibbon.galipatsistem.com/\n\n" .
            "━━━━━━━━━━━━━━━━━━━━\n" .
            "Terima kasih atas partisipasinya!\n\n" .
            "_Pesan otomatis - CAI Lombok 2026_";
    }

    /**
     * Default message template for pembina/reports.
     */
    public static function defaultPembinaTemplate(): string
    {
        return "📋 *Laporan Kehadiran CAI LOMBOK 2026*\n" .
            "━━━━━━━━━━━━━━━━━━━━\n" .
            "📅 Sesi: *{nama_sesi}*\n" .
            "👥 Kelompok: *{kelompok}*\n" .
            "👤 Pembina: *{nama_pembina}*\n" .
            "⏰ Waktu Laporan: *{jam_absen}*\n\n" .
            "📊 *Ringkasan Kehadiran:*\n" .
            "• Total Peserta: *{total_peserta}*\n" .
            "• Hadir: *{jumlah_hadir}* ({persentase}%)\n" .
            "• Belum Hadir: *{jumlah_tidak_hadir}*\n\n" .
            "👨 *Laki-laki:* {hadir_laki_laki}/{total_laki_laki} hadir ({absent_laki_laki} belum)\n" .
            "👩 *Perempuan:* {hadir_perempuan}/{total_perempuan} hadir ({absent_perempuan} belum)\n\n" .
            "❌ *Daftar Peserta Belum Hadir:*\n" .
            "{daftar_belum_hadir}\n\n" .
            "━━━━━━━━━━━━━━━━━━━━\n" .
            "_Pesan otomatis - CAI Lombok 2026_";
    }
}
