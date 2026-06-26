#!/bin/bash

# CAI Lombok 2026 - VPS Automated Setup Script
# Works on Ubuntu 20.04 / 22.04 / 24.04 LTS

set -e

# Colors for log output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}===============================================${NC}"
echo -e "${GREEN}  CAI LOMBOK 2026 Attendance System Setup      ${NC}"
echo -e "${GREEN}===============================================${NC}"

# Check if script is run as root
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}Error: Jalankan script ini sebagai root (gunakan sudo).${NC}"
  exit 1
fi

PROJECT_ROOT="$(pwd)"

# ─── 1. SETUP SWAP MEMORY (4GB) ────────────────────────────────────────────────
echo -e "\n${YELLOW}[1/4] Menyiapkan SWAP Memory 4GB...${NC}"
if [ -f /swapfile ]; then
  echo -e "${GREEN}Swapfile sudah ada. Melewati pembuatan...${NC}"
else
  echo -e "${YELLOW}Membuat swapfile 4GB (ini mungkin memakan waktu beberapa detik)...${NC}"
  fallocate -l 4G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  # Make persistent
  if ! grep -q "/swapfile" /etc/fstab; then
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
  fi
  # Adjust swappiness
  sysctl vm.swappiness=10
  if ! grep -q "vm.swappiness" /etc/sysctl.conf; then
    echo 'vm.swappiness=10' >> /etc/sysctl.conf
  fi
  echo -e "${GREEN}✓ SWAP Memory 4GB berhasil dibuat dan aktif!${NC}"
fi


# ─── 2. SYSTEM PACKAGE INSTALLATION ──────────────────────────────────────────
echo -e "\n${YELLOW}[2/4] Menginstal dependensi sistem (Python3, pip, OpenCV libraries)...${NC}"
apt-get update

# Install required system packages for python virtual env and OpenCV
apt-get install -y \
  python3-pip \
  python3-venv \
  python3-dev \
  libgl1-mesa-glx \
  libglib2.0-0 \
  curl \
  git

echo -e "${GREEN}✓ Dependensi sistem terinstal!${NC}"


# ─── 3. PYTHON FACE SERVICE SETUP ─────────────────────────────────────────────
echo -e "\n${YELLOW}[3/4] Menyiapkan Python Face Service...${NC}"
PYTHON_DIR="${PROJECT_ROOT}/python-face-service"

if [ -d "$PYTHON_DIR" ]; then
  cd "$PYTHON_DIR"
  
  # Create face_db directory if not exists
  mkdir -p face_db
  
  echo -e "${YELLOW}Membuat Python Virtual Environment (.venv)...${NC}"
  python3 -m venv .venv
  
  echo -e "${YELLOW}Menginstal requirements.txt (TensorFlow & DeepFace)...${NC}"
  .venv/bin/pip install --upgrade pip
  .venv/bin/pip install -r requirements.txt
  
  echo -e "${GREEN}✓ Python Face Service berhasil disiapkan!${NC}"
  cd "$PROJECT_ROOT"
else
  echo -e "${RED}Error: Folder python-face-service tidak ditemukan di ${PROJECT_ROOT}!${NC}"
  exit 1
fi


# ─── 4. REGISTER SYSTEMD SERVICES ─────────────────────────────────────────────
echo -e "\n${YELLOW}[4/4] Mendaftarkan Systemd Service (Autostart Background)...${NC}"

# Define service files paths
FACE_SERVICE_FILE="/etc/systemd/system/cai-face.service"
REVERB_SERVICE_FILE="/etc/systemd/system/cai-reverb.service"

# Create python face service systemd
echo -e "${YELLOW}Membuat Systemd Service untuk Face Recognition...${NC}"
cat <<EOT > $FACE_SERVICE_FILE
[Unit]
Description=CAI Lombok 2026 Face Recognition Service
After=network.target

[Service]
User=root
WorkingDirectory=${PYTHON_DIR}
ExecStart=${PYTHON_DIR}/.venv/bin/python main.py
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOT

# Create laravel reverb systemd if laravel exists
LARAVEL_DIR="${PROJECT_ROOT}/laravel-app"
if [ -d "$LARAVEL_DIR" ]; then
  echo -e "${YELLOW}Membuat Systemd Service untuk Laravel Reverb (WebSockets)...${NC}"
  # Check path to PHP binary
  PHP_PATH=$(which php || echo "/usr/bin/php")
  
  cat <<EOT > $REVERB_SERVICE_FILE
[Unit]
Description=CAI Lombok 2026 Reverb WebSockets
After=network.target

[Service]
User=root
WorkingDirectory=${LARAVEL_DIR}
ExecStart=${PHP_PATH} artisan reverb:start --port=8080
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOT
fi

# Reload systemd and start services
echo -e "${YELLOW}Menjalankan service di background...${NC}"
systemctl daemon-reload

# Start and enable face service
systemctl enable cai-face
systemctl restart cai-face

# Start and enable reverb service if created
if [ -f $REVERB_SERVICE_FILE ]; then
  systemctl enable cai-reverb
  systemctl restart cai-reverb
  echo -e "${GREEN}✓ Laravel Reverb Service didaftarkan dan dijalankan!${NC}"
fi

echo -e "${GREEN}✓ Python Face Recognition Service didaftarkan dan dijalankan!${NC}"

echo -e "${GREEN}================================================================${NC}"
echo -e "${GREEN}  ✓ PENGINSTALAN SELESAI!                                       ${NC}"
echo -e "${GREEN}  - Python Service berjalan di port 8001                        ${NC}"
if [ -f $REVERB_SERVICE_FILE ]; then
  echo -e "${GREEN}  - Laravel Reverb berjalan di port 8080                        ${NC}"
fi
echo -e "${GREEN}  - Memori Swap 4GB aktif                                       ${NC}"
echo -e "${GREEN}================================================================${NC}"
echo -e "${YELLOW}Catatan untuk Laravel:${NC}"
echo -e "1. Konfigurasikan file .env di laravel-app/"
echo -e "2. Jalankan: composer install && php artisan migrate --seed"
echo -e "3. Jalankan: npm install && npm run build"
echo -e "${GREEN}================================================================${NC}"
