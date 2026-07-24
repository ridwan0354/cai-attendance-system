@extends('layouts.app')
@section('title', 'Pengaturan')
@section('content')
<div style="padding: 1.25rem; max-width: 1100px; margin: 0 auto;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 1.5rem; padding-bottom: 0.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🎁 Barang</a>
        <a href="{{ route('admin.settings.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">⚙️ Pengaturan</a>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 800; color: var(--neutral-900);">⚙️ Pengaturan Sistem & Notifikasi WhatsApp</h1>
            <p style="font-size: 0.8rem; color: var(--neutral-500); margin-top: 2px;">Kelola gateway WhatsApp, sakelar konfirmasi otomatis, dan templat pesan untuk Peserta & Pembina.</p>
        </div>
        <form action="{{ route('admin.settings.lock') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; display: flex; align-items: center; gap: 4px; border: 1px solid var(--neutral-300); color: var(--neutral-600); border-radius: 6px; background: white; cursor: pointer;">
                🔒 Kunci Akses
            </button>
        </form>
    </div>

    @if(session('success'))
        <div style="background: var(--success-lt); border: 1px solid var(--success); color: var(--success); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .875rem;">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: var(--danger-lt); border: 1px solid var(--danger); color: var(--danger); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .875rem;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.store') }}" method="POST">
        @csrf

        <!-- Grid Top: Gateway & Toggles -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start; margin-bottom: 1.5rem;">
            
            <!-- Card 1: Configuration Gateway -->
            <div class="card" style="border: 1px solid var(--neutral-200); box-shadow: var(--shadow-sm); border-radius: 10px; overflow: hidden; background: white; height: 100%;">
                <div class="card-header" style="background: var(--neutral-50); padding: 1rem 1.25rem; border-bottom: 1px solid var(--neutral-200);">
                    <span class="card-title" style="font-size: 0.95rem; font-weight: 800; color: var(--neutral-800);">📡 Gateway WhatsApp</span>
                </div>
                <div class="card-body" style="padding: 1.25rem;">
                    <!-- Gateway Selection -->
                    <div style="margin-bottom: 1.25rem;">
                        <label for="wa_gateway" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Pilih Gateway API</label>
                        <select name="wa_gateway" id="wa_gateway" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; background: white;" onchange="toggleGatewayFields()">
                            <option value="fonnte" {{ $waGateway === 'fonnte' ? 'selected' : '' }}>📡 Fonnte Gateway (fonnte.com)</option>
                            <option value="groovite" {{ $waGateway === 'groovite' ? 'selected' : '' }}>⚡ Custom Gateway (Groovite / Galipat)</option>
                        </select>
                    </div>

                    <!-- Fonnte Fields -->
                    <div id="fonnte_fields" style="margin-bottom: 1rem; display: {{ $waGateway === 'fonnte' ? 'block' : 'none' }};">
                        <label for="fonnte_api_key" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Token API Fonnte (fonnte.com)</label>
                        <input type="text" name="fonnte_api_key" id="fonnte_api_key" value="{{ $fonnteApiKey }}" placeholder="Masukkan token API Fonnte..." 
                               style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                               onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                        <span style="font-size: 0.75rem; color: var(--neutral-500); display: block; margin-top: 6px; line-height: 1.4;">
                            Token API diambil dari akun Fonnte Anda di <a href="https://fonnte.com" target="_blank" style="color:var(--primary);font-weight:700;">https://fonnte.com/</a> (Menu Device → Token).
                        </span>
                    </div>

                    <!-- Groovite Fields -->
                    <div id="groovite_fields" style="margin-bottom: 1rem; display: {{ $waGateway === 'groovite' ? 'block' : 'none' }};">
                        <div style="margin-bottom: 1rem;">
                            <label for="groovite_api_url" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Base API URL</label>
                            <input type="text" name="groovite_api_url" id="groovite_api_url" value="{{ $grooviteApiUrl }}" placeholder="Contoh: https://waa.galipatsistem.com/api" 
                                   style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                                   onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                        </div>
                        <div>
                            <label for="groovite_wa_key" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">API Key (waKey)</label>
                            <input type="text" name="groovite_wa_key" id="groovite_wa_key" value="{{ $grooviteWaKey }}" placeholder="Masukkan waKey..." 
                                   style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                                   onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                        </div>
                        <span style="font-size: 0.75rem; color: var(--neutral-500); display: block; margin-top: 6px; line-height: 1.4;">
                            API Key (waKey) dan Base URL ini digunakan untuk mengirim pesan via Custom Gateway.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Notification Switch ON/OFF -->
            <div class="card" style="border: 1px solid var(--neutral-200); box-shadow: var(--shadow-sm); border-radius: 10px; overflow: hidden; background: white; height: 100%;">
                <div class="card-header" style="background: var(--neutral-50); padding: 1rem 1.25rem; border-bottom: 1px solid var(--neutral-200);">
                    <span class="card-title" style="font-size: 0.95rem; font-weight: 800; color: var(--neutral-800);">🔔 Sakelar Konfirmasi Otomatis (ON / OFF)</span>
                </div>
                <div class="card-body" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1.25rem;">
                    
                    <!-- Toggle 1: Peserta -->
                    <div style="background: var(--neutral-50); border: 1.5px solid var(--neutral-200); border-radius: 8px; padding: 1rem; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem; color: var(--neutral-900);">📱 Konfirmasi Kehadiran ke PESERTA</div>
                            <div style="font-size: 0.75rem; color: var(--neutral-500); margin-top: 2px;">Kirim pesan WhatsApp otomatis ke peserta saat absensi berhasil di-scan.</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <select name="notify_peserta_enabled" id="notify_peserta_enabled" style="padding: 0.45rem 0.75rem; font-weight: 700; font-size: 0.85rem; border-radius: 6px; border: 1.5px solid {{ $notifyPesertaEnabled == '1' ? 'var(--success)' : 'var(--neutral-300)' }}; background: {{ $notifyPesertaEnabled == '1' ? 'var(--success-lt)' : 'white' }}; color: {{ $notifyPesertaEnabled == '1' ? 'var(--success)' : 'var(--neutral-600)' }}; cursor: pointer;" onchange="updateSelectStyle(this)">
                                <option value="1" {{ $notifyPesertaEnabled == '1' ? 'selected' : '' }}>🟢 AKTIF (ON)</option>
                                <option value="0" {{ $notifyPesertaEnabled == '0' ? 'selected' : '' }}>🔴 NONAKTIF (OFF)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Toggle 2: Pembina -->
                    <div style="background: var(--neutral-50); border: 1.5px solid var(--neutral-200); border-radius: 8px; padding: 1rem; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem; color: var(--neutral-900);">👨‍🏫 Konfirmasi / Laporan ke PEMBINA</div>
                            <div style="font-size: 0.75rem; color: var(--neutral-500); margin-top: 2px;">Kirim laporan ringkasan absensi kelompok ke WhatsApp Pembina secara otomatis.</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <select name="notify_pembina_enabled" id="notify_pembina_enabled" style="padding: 0.45rem 0.75rem; font-weight: 700; font-size: 0.85rem; border-radius: 6px; border: 1.5px solid {{ $notifyPembinaEnabled == '1' ? 'var(--success)' : 'var(--neutral-300)' }}; background: {{ $notifyPembinaEnabled == '1' ? 'var(--success-lt)' : 'white' }}; color: {{ $notifyPembinaEnabled == '1' ? 'var(--success)' : 'var(--neutral-600)' }}; cursor: pointer;" onchange="updateSelectStyle(this)">
                                <option value="1" {{ $notifyPembinaEnabled == '1' ? 'selected' : '' }}>🟢 AKTIF (ON)</option>
                                <option value="0" {{ $notifyPembinaEnabled == '0' ? 'selected' : '' }}>🔴 NONAKTIF (OFF)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Item 3: Anti-Spam Delay -->
                    <div style="background: var(--neutral-50); border: 1.5px solid var(--neutral-200); border-radius: 8px; padding: 1rem; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem; color: var(--neutral-900);">⏱️ Jeda Pengiriman WA (Anti-Spam)</div>
                            <div style="font-size: 0.75rem; color: var(--neutral-500); margin-top: 2px;">Interval bertahap antrian pengiriman WA antar peserta agar nomor aman dari spam ban.</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <input type="number" name="wa_send_delay_seconds" id="wa_send_delay_seconds" value="{{ $waSendDelaySeconds }}" min="0" max="120" style="width: 70px; padding: 0.45rem; font-weight: 800; font-size: 0.85rem; border-radius: 6px; border: 1.5px solid var(--neutral-300); text-align: center; background: white;" required>
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--neutral-700);">Detik</span>
                        </div>
                    </div>

                    <!-- Item 4: Choice of WA Link Format (wa.me / fonnte.com / both) -->
                    <div style="background: var(--neutral-50); border: 1.5px solid var(--neutral-200); border-radius: 8px; padding: 1rem; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem; color: var(--neutral-900);">🔗 Opsi Format Link "Hubungi WA"</div>
                            <div style="font-size: 0.75rem; color: var(--neutral-500); margin-top: 2px;">Pilih link tujuan saat mengklik tombol Hubungi WA (wa.me, fonnte.com, atau keduanya).</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <select name="wa_contact_link_type" id="wa_contact_link_type" style="padding: 0.45rem 0.75rem; font-weight: 700; font-size: 0.85rem; border-radius: 6px; border: 1.5px solid var(--neutral-300); background: white; color: var(--neutral-800); cursor: pointer;">
                                <option value="both" {{ $waContactLinkType == 'both' ? 'selected' : '' }}>✨ Keduanya (wa.me & fonnte.com)</option>
                                <option value="fonnte" {{ $waContactLinkType == 'fonnte' ? 'selected' : '' }}>📡 fonnte.com (Fonnte Web)</option>
                                <option value="wa_me" {{ $waContactLinkType == 'wa_me' ? 'selected' : '' }}>💬 wa.me (WhatsApp Direct)</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Full Row: Message Templates -->
        <div class="card" style="border: 1px solid var(--neutral-200); box-shadow: var(--shadow-sm); border-radius: 10px; overflow: hidden; background: white; margin-bottom: 1.5rem;">
            <div class="card-header" style="background: var(--neutral-50); padding: 1rem 1.25rem; border-bottom: 1px solid var(--neutral-200); display: flex; align-items: center; justify-content: space-between;">
                <span class="card-title" style="font-size: 0.95rem; font-weight: 800; color: var(--neutral-800);">📝 Format & Isi Pesan WhatsApp (Dapat Disesuaikan)</span>
                <span class="badge badge-primary">WhatsApp Template Editor</span>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    
                    <!-- Template Peserta -->
                    <div style="display: flex; flex-direction: column;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <label for="peserta_message_template" style="font-size: 0.85rem; font-weight: 800; color: var(--neutral-800);">📱 Pesan Konfirmasi Kehadiran (Ke PESERTA)</label>
                        </div>
                        
                        <textarea name="peserta_message_template" id="peserta_message_template" rows="12" 
                                  style="width: 100%; padding: 0.75rem 0.85rem; border: 1.5px solid var(--neutral-300); border-radius: 8px; font-size: 0.85rem; font-family: monospace, sans-serif; line-height: 1.5; outline: none; background: #fafafa;"
                                  onfocus="this.style.borderColor='var(--primary)'; this.style.background='white';" 
                                  onblur="this.style.borderColor='var(--neutral-300)'; this.style.background='#fafafa';">{{ $pesertaMessageTemplate }}</textarea>
                        
                        <!-- Available Variable Tags for Peserta -->
                        <div style="margin-top: 0.65rem;">
                            <span style="font-size: 0.72rem; font-weight: 700; color: var(--neutral-600); display: block; margin-bottom: 4px;">Klik tag untuk menyisipkan variabel ke dalam pesan:</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                <button type="button" onclick="insertTag('peserta_message_template', '{nama_peserta}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{nama_peserta}</button>
                                <button type="button" onclick="insertTag('peserta_message_template', '{nama_sesi}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{nama_sesi}</button>
                                <button type="button" onclick="insertTag('peserta_message_template', '{kelompok}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{kelompok}</button>
                                <button type="button" onclick="insertTag('peserta_message_template', '{jam_absen}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{jam_absen}</button>
                                <button type="button" onclick="insertTag('peserta_message_template', '{metode}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{metode}</button>
                            </div>
                        </div>
                    </div>

                    <!-- Template Pembina -->
                    <div style="display: flex; flex-direction: column;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <label for="pembina_message_template" style="font-size: 0.85rem; font-weight: 800; color: var(--neutral-800);">👨‍🏫 Pesan Laporan Absensi Kelompok (Ke PEMBINA)</label>
                        </div>
                        
                        <textarea name="pembina_message_template" id="pembina_message_template" rows="12" 
                                  style="width: 100%; padding: 0.75rem 0.85rem; border: 1.5px solid var(--neutral-300); border-radius: 8px; font-size: 0.85rem; font-family: monospace, sans-serif; line-height: 1.5; outline: none; background: #fafafa;"
                                  onfocus="this.style.borderColor='var(--primary)'; this.style.background='white';" 
                                  onblur="this.style.borderColor='var(--neutral-300)'; this.style.background='#fafafa';">{{ $pembinaMessageTemplate }}</textarea>

                        <!-- Available Variable Tags for Pembina -->
                        <div style="margin-top: 0.65rem;">
                            <span style="font-size: 0.72rem; font-weight: 700; color: var(--neutral-600); display: block; margin-bottom: 4px;">Klik tag untuk menyisipkan variabel ke dalam pesan:</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                <button type="button" onclick="insertTag('pembina_message_template', '{nama_pembina}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{nama_pembina}</button>
                                <button type="button" onclick="insertTag('pembina_message_template', '{nama_sesi}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{nama_sesi}</button>
                                <button type="button" onclick="insertTag('pembina_message_template', '{kelompok}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{kelompok}</button>
                                <button type="button" onclick="insertTag('pembina_message_template', '{total_peserta}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{total_peserta}</button>
                                <button type="button" onclick="insertTag('pembina_message_template', '{jumlah_hadir}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{jumlah_hadir}</button>
                                <button type="button" onclick="insertTag('pembina_message_template', '{jumlah_tidak_hadir}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{jumlah_tidak_hadir}</button>
                                <button type="button" onclick="insertTag('pembina_message_template', '{persentase}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{persentase}</button>
                                <button type="button" onclick="insertTag('pembina_message_template', '{daftar_belum_hadir}')" style="background: var(--neutral-100); border: 1px solid var(--neutral-300); padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; color: var(--primary);">+{daftar_belum_hadir}</button>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            <div class="card-footer" style="background: var(--neutral-50); padding: 1rem 1.5rem; border-top: 1px solid var(--neutral-200); display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.78rem; color: var(--neutral-500);">💡 Tips: Gunakan tanda <code>*teks*</code> untuk bercetak tebal atau <code>_teks_</code> untuk miring di WhatsApp.</span>
                <button type="submit" class="btn" style="background: var(--primary); color: white; border: none; padding: 0.65rem 2rem; font-size: 0.95rem; font-weight: 700; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 6px rgba(0, 82, 204, 0.25);">
                    💾 Simpan Semua Pengaturan
                </button>
            </div>
        </div>

    </form>

    <!-- Test Connection Card -->
    <div class="card" style="border: 1px solid var(--neutral-200); box-shadow: var(--shadow-sm); border-radius: 10px; overflow: hidden; background: white;">
        <div class="card-header" style="background: var(--neutral-50); padding: 1rem 1.25rem; border-bottom: 1px solid var(--neutral-200);">
            <span class="card-title" style="font-size: 0.95rem; font-weight: 800; color: var(--neutral-800);">🚀 Uji Coba Pengiriman Pesan WhatsApp (Test Send)</span>
        </div>
        <div class="card-body" style="padding: 1.25rem;">
            <form action="{{ route('admin.settings.test-wa') }}" method="POST">
                @csrf
                <div style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label for="test_phone" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Nomor WhatsApp Penerima Tes</label>
                        <input type="text" name="test_phone" id="test_phone" required placeholder="Contoh: 081234567890..." 
                               style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                               onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                    </div>
                    <button type="submit" class="btn btn-outline" style="border: 1.5px solid var(--primary); color: var(--primary); background: transparent; padding: 0.65rem 1.5rem; font-size: 0.9rem; font-weight: 700; border-radius: 6px; cursor: pointer; transition: all 0.15s; white-space: nowrap;"
                             onmouseover="this.style.background='var(--primary-lt)';" onmouseout="this.style.background='transparent';">
                        🚀 Kirim Pesan Tes
                    </button>
                </div>
                <span style="font-size: 0.75rem; color: var(--neutral-500); display: block; margin-top: 6px; line-height: 1.4;">
                    Masukkan nomor WhatsApp tujuan untuk memastikan koneksi Gateway WhatsApp (Fonnte / Groovite) berjalan tanpa kendala.
                </span>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleGatewayFields() {
        const gateway = document.getElementById('wa_gateway').value;
        const fonnteFields = document.getElementById('fonnte_fields');
        const grooviteFields = document.getElementById('groovite_fields');
        
        if (gateway === 'groovite') {
            fonnteFields.style.display = 'none';
            grooviteFields.style.display = 'block';
        } else {
            fonnteFields.style.display = 'block';
            grooviteFields.style.display = 'none';
        }
    }

    function updateSelectStyle(selectEl) {
        if (selectEl.value === '1') {
            selectEl.style.border = '1.5px solid var(--success)';
            selectEl.style.background = 'var(--success-lt)';
            selectEl.style.color = 'var(--success)';
        } else {
            selectEl.style.border = '1.5px solid var(--danger)';
            selectEl.style.background = 'var(--danger-lt)';
            selectEl.style.color = 'var(--danger)';
        }
    }

    function insertTag(textareaId, tag) {
        const area = document.getElementById(textareaId);
        if (!area) return;

        const start = area.selectionStart;
        const end = area.selectionEnd;
        const text = area.value;

        area.value = text.substring(0, start) + tag + text.substring(end);
        area.selectionStart = area.selectionEnd = start + tag.length;
        area.focus();
    }
</script>
@endsection
