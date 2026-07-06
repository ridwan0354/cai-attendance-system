# Model FaceNet - WAJIB DIDOWNLOAD

## Langkah Download

1. Buka link berikut:
   https://github.com/shubham0204/FaceRecognition_With_FaceNet_Android/raw/master/app/src/main/assets/facenet.tflite

2. Download file **facenet.tflite** (~8 MB)

3. Salin file tersebut ke folder ini:
   `app/src/main/assets/facenet.tflite`

## Alternatif Download

Jika link di atas tidak bisa diakses, bisa download dari:
https://tfhub.dev/google/lite-model/facenet/1/default/1

Atau dari Google Drive:
https://drive.google.com/file/d/1EXPBSXwTaqrSC0OhUdXNIDKWer7aoPPB/view

## Spesifikasi Model

- Input  : 160 x 160 pixel, RGB, normalized ke [-1, 1]
- Output : 512-dimensional float array (face embedding)
- Ukuran : ~8 MB
- Engine : TensorFlow Lite (MobileNet-based FaceNet)
