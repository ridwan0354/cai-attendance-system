# 📱 CAI Attendance - Android App

Aplikasi Android untuk sistem absensi CAI Lombok 2026 dengan **face recognition lokal** menggunakan Google ML Kit + TensorFlow Lite FaceNet.

---

## ⚡ Fitur Utama

- **Face Recognition Lokal** — proses wajah di device, tanpa kirim ke server (~50-200ms)
- **Offline-First** — absensi tetap bisa dilakukan tanpa internet, upload otomatis saat online
- **Incremental Sync** — hanya download foto yang berubah/baru
- **Background Sync** — WorkManager sync otomatis setiap 30 menit
- **Dark Theme** — UI premium dengan tema navy blue

---

## 🚀 Setup Android Studio

### Langkah 1: Buka Project

1. Buka **Android Studio**
2. Klik **File → Open**
3. Pilih folder `cai-android/`
4. Tunggu Gradle sync selesai (bisa 5-10 menit pertama kali)

### Langkah 2: Download Model FaceNet ⚠️ WAJIB

Model FaceNet tidak bisa disertakan di sini karena ukurannya ~8MB.

1. Download dari: https://github.com/shubham0204/FaceRecognition_With_FaceNet_Android/raw/master/app/src/main/assets/facenet.tflite
2. Simpan file ke: **`app/src/main/assets/facenet.tflite`**

> **Tanpa file ini, face recognition tidak akan berfungsi!**

### Langkah 3: Konfigurasi API Key

Pastikan file `.env` di Laravel sudah punya:
```
MOBILE_API_KEY=cai-mobile-2026-change-this-key
```

### Langkah 4: Build APK

**Debug APK** (untuk testing):
```
Build → Build Bundle(s) / APK(s) → Build APK(s)
```
APK akan ada di: `app/build/outputs/apk/debug/app-debug.apk`

**Release APK** (untuk produksi):
```
Build → Generate Signed Bundle / APK
```

---

## 📱 Cara Pakai

### Pertama Kali:
1. **Login** — masukkan URL server dan API Key
2. **Sync Data** — download semua foto peserta & generate embedding
3. **Scan!** — buka scanner, arahkan wajah peserta

### Scan Wajah:
- Kamera depan digunakan secara default
- Frame diproses setiap ~500ms
- Jika wajah cocok → langsung catat absensi
- Absensi disimpan lokal jika offline, otomatis upload saat ada internet

### Incremental Sync:
- Setelah sync pertama, gunakan **"Sync Incremental"** untuk update saja
- Hanya peserta yang fotonya diperbarui di server yang akan didownload ulang

---

## 🗂️ Struktur Project

```
cai-android/
├── app/src/main/
│   ├── assets/
│   │   └── facenet.tflite          ← Download manual!
│   ├── java/com/cai/attendance/
│   │   ├── CaiApplication.kt       ← Hilt + WorkManager
│   │   ├── MainActivity.kt
│   │   ├── data/
│   │   │   ├── local/              ← Room DB (SQLite lokal)
│   │   │   ├── remote/             ← Retrofit API
│   │   │   ├── repository/         ← Business logic
│   │   │   └── preferences/        ← DataStore
│   │   ├── di/                     ← Hilt dependency injection
│   │   ├── ml/                     ← FaceNet + ML Kit wrapper
│   │   ├── navigation/             ← Jetpack Navigation
│   │   ├── sync/                   ← WorkManager
│   │   └── ui/                     ← Jetpack Compose screens
│   └── res/
└── build.gradle.kts
```

---

## 🔗 API Endpoints yang Digunakan

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/mobile/sync/info` | Status sync |
| GET | `/api/mobile/participants` | Daftar peserta |
| GET | `/api/mobile/participants/{id}/photo` | Download foto |
| GET | `/api/mobile/sessions/active` | Sesi aktif |
| POST | `/api/mobile/attendance` | Catat absensi |

**Header wajib di setiap request:**
```
X-Api-Key: [nilai MOBILE_API_KEY di .env Laravel]
```

---

## ⚙️ Konfigurasi Teknis

### Threshold Face Recognition
Di `FaceMatcher.kt`, ubah nilai `DEFAULT_THRESHOLD`:
```kotlin
const val DEFAULT_THRESHOLD = 0.65f  // 0.5 = lebih longgar, 0.8 = lebih ketat
```

### Cooldown Antar Scan
Di `ScannerViewModel.kt`:
```kotlin
private const val COOLDOWN_MS = 2500L  // jeda antar deteksi (ms)
```

### Frekuensi Frame
Di `CameraPreviewView.kt`:
```kotlin
if (frameCount % 3 == 0) { ... }  // proses setiap 3 frame
```

---

## 🛠️ Troubleshooting

| Masalah | Solusi |
|---------|--------|
| "Model FaceNet belum siap" | Download `facenet.tflite` ke `assets/` |
| "Tidak ada sesi aktif" | Aktifkan sesi di admin Laravel |
| Wajah tidak terdeteksi | Pastikan pencahayaan cukup |
| Sync gagal | Cek URL server dan API key |
| APK tidak bisa install | Aktifkan "Install unknown apps" di Android |

---

## 📋 Requirements

- Android 8.0 (API 26) atau lebih baru
- Kamera depan
- ~50MB storage untuk foto 500 peserta
- Internet untuk sync (tidak perlu untuk scan)
