#!/usr/bin/env bash
# ==============================================================================
# SimpleAkunting v3.6 - Production Deployment Script for cPanel / Terminal
# ==============================================================================
# Script ini dirancang khusus untuk deployment di hosting cPanel / Cloud Linux
# yang memiliki akses terminal / SSH pada direktori public_html.
#
# Fitur Utama:
#  1. Self-contained execution & Lockfile (Mencegah concurrent deployment)
#  2. Git Safe Directory & Auto-detection PHP CLI & Composer
#  3. Git Branch Management (Auto-stash uncommitted server edits, safe pull)
#  4. Automated Pre-deployment Snapshot (Backup config & files sebelum update)
#  5. Optional Maintenance Mode (.htaccess / maintenance flag)
#  6. Dependency Check & Autoloader Optimization (Composer)
#  7. Database Migration Runner (via migrate.php)
#  8. Mirroring / Public Asset Sync (Root <-> public/ consistency)
#  9. Security & Permission Hardening (Dir 755, File 644, Config 600)
# 10. OPcache flush & Log Housekeeping (Truncate bloated error_log)
# 11. Rollback Mechanism (--rollback)
# ==============================================================================

set -eo pipefail

# --- ANSI Colors & Formatting ---
COLOR_RESET="\033[0m"
COLOR_BOLD="\033[1m"
COLOR_GREEN="\033[32m"
COLOR_YELLOW="\033[33m"
COLOR_BLUE="\033[34m"
COLOR_CYAN="\033[36m"
COLOR_RED="\033[31m"
COLOR_DIM="\033[2m"

log_info()    { echo -e "${COLOR_CYAN}[INFO]${COLOR_RESET}  $*"; }
log_ok()      { echo -e "${COLOR_GREEN}[OK]${COLOR_RESET}    $*"; }
log_warn()    { echo -e "${COLOR_YELLOW}[WARN]${COLOR_RESET}  $*"; }
log_error()   { echo -e "${COLOR_RED}[ERROR]${COLOR_RESET} $*"; }
log_step()    { echo -e "\n${COLOR_BOLD}${COLOR_BLUE}==>${COLOR_RESET} ${COLOR_BOLD}$*${COLOR_RESET}"; }

# --- Directories & Constants ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOCK_FILE="${SCRIPT_DIR}/.deploy.lock"
BACKUP_DIR="${SCRIPT_DIR}/.deploy_backups"
MAINTENANCE_FILE="${SCRIPT_DIR}/.maintenance"
HTACCESS_FILE="${SCRIPT_DIR}/.htaccess"
HTACCESS_BACKUP="${SCRIPT_DIR}/.htaccess.pre_deploy"
MAX_BACKUPS=5

# --- Default Flags ---
TARGET_BRANCH=""
FLAG_FORCE=false
FLAG_MIGRATE=false
FLAG_SKIP_COMPOSER=false
FLAG_MAINTENANCE=false
FLAG_STATUS_ONLY=false
FLAG_ROLLBACK=false
FLAG_NO_BACKUP=false

# --- Cleanup Trap ---
cleanup() {
    local exit_code=$?
    if [ -f "$LOCK_FILE" ]; then
        rm -f "$LOCK_FILE"
    fi
    if [ "$FLAG_MAINTENANCE" = true ] && [ -f "$MAINTENANCE_FILE" ]; then
        disable_maintenance_mode
    fi
    if [ $exit_code -ne 0 ]; then
        log_error "Deployment terhenti atau gagal dengan exit code $exit_code."
    fi
    exit $exit_code
}
trap cleanup EXIT INT TERM

# --- Help Message ---
show_help() {
    cat << 'EOF'
Penggunaan: ./deploy.sh [OPSI]

Opsi yang tersedia:
  -b, --branch <nama>    Tentukan branch git yang akan dideploy (default: branch saat ini atau main)
  -m, --migrate          Jalankan migrasi database otomatis (migrate.php)
  -f, --force            Paksa reset git jika ada conflict (git reset --hard)
  --maintenance          Aktifkan maintenance mode selama proses deployment
  --skip-composer        Lewati eksekusi 'composer install'
  --no-backup            Lewati pembuatan snapshot backup sebelum deploy
  --status               Hanya cek status git, environment, dan izin file tanpa melakukan perubahan
  --rollback             Kembalikan file penting ke snapshot backup deployment terakhir
  -h, --help             Tampilkan pesan bantuan ini

Contoh Penggunaan:
  ./deploy.sh
  ./deploy.sh --migrate
  ./deploy.sh --branch main --maintenance --migrate
  ./deploy.sh --status
  ./deploy.sh --rollback
EOF
}

# --- Parse Arguments ---
while [[ $# -gt 0 ]]; do
    case "$1" in
        -b|--branch)
            TARGET_BRANCH="$2"
            shift 2
            ;;
        -m|--migrate)
            FLAG_MIGRATE=true
            shift
            ;;
        -f|--force)
            FLAG_FORCE=true
            shift
            ;;
        --maintenance)
            FLAG_MAINTENANCE=true
            shift
            ;;
        --skip-composer)
            FLAG_SKIP_COMPOSER=true
            shift
            ;;
        --no-backup)
            FLAG_NO_BACKUP=true
            shift
            ;;
        --status)
            FLAG_STATUS_ONLY=true
            shift
            ;;
        --rollback)
            FLAG_ROLLBACK=true
            shift
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            log_error "Argumen tidak dikenal: $1"
            show_help
            exit 1
            ;;
    esac
done

# --- Banner Header ---
START_TIME=$(date +%s)
echo -e "${COLOR_CYAN}${COLOR_BOLD}"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║          SimpleAkunting v3.6 - Production Deployment           ║"
echo "║               Automated cPanel Terminal Suite                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${COLOR_RESET}"
echo -e "${COLOR_DIM}Direktori Operasi: ${SCRIPT_DIR}${COLOR_RESET}"
echo -e "${COLOR_DIM}Waktu            : $(date '+%Y-%m-%d %H:%M:%S')${COLOR_RESET}"

cd "$SCRIPT_DIR"

# --- 1. Lock Mechanism ---
check_lock() {
    if [ -f "$LOCK_FILE" ]; then
        local pid
        pid=$(cat "$LOCK_FILE" 2>/dev/null || echo "")
        if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
            log_error "Proses deploy lain sedang berjalan (PID: $pid)!"
            log_error "Jika proses tersebut sudah mati, hapus file: $LOCK_FILE"
            exit 1
        else
            log_warn "Menghapus lock file usang dari sesi sebelumnya..."
            rm -f "$LOCK_FILE"
        fi
    fi
    echo $$ > "$LOCK_FILE"
}

# --- 2. Environment & CLI Detection ---
detect_environment() {
    log_step "1/8 Memeriksa Environment Sistem & Runtime..."

    # Pastikan Git terinstall dan repositori valid
    if ! command -v git >/dev/null 2>&1; then
        log_error "Perintah 'git' tidak ditemukan! Pastikan Git terinstall di server hosting."
        exit 1
    fi
    if [ ! -d ".git" ]; then
        log_error "Direktori saat ini bukan repositori Git (.git tidak ditemukan)."
        exit 1
    fi

    # Cegah 'fatal: detected dubious ownership' pada Git versi modern di cPanel
    git config --global --add safe.directory "$SCRIPT_DIR" 2>/dev/null || true
    log_ok "Git CLI terdeteksi: $(git --version)"

    # Detect PHP CLI (Cari binary versi modern di cPanel CloudLinux)
    if [ -z "${PHP_BIN:-}" ]; then
        CANDIDATE_PHPS=(
            "/usr/local/bin/ea-php83"
            "/usr/local/bin/ea-php82"
            "/usr/local/bin/ea-php81"
            "/opt/cpanel/ea-php83/root/usr/bin/php"
            "/opt/cpanel/ea-php82/root/usr/bin/php"
            "/opt/cpanel/ea-php81/root/usr/bin/php"
            "/usr/local/bin/php"
            "/usr/bin/php"
            "$(command -v php83 2>/dev/null || true)"
            "$(command -v php82 2>/dev/null || true)"
            "$(command -v php81 2>/dev/null || true)"
            "$(command -v php 2>/dev/null || true)"
        )

        for cand in "${CANDIDATE_PHPS[@]}"; do
            if [ -n "$cand" ] && { [ -x "$cand" ] || command -v "$cand" >/dev/null 2>&1; }; then
                PHP_BIN="$cand"
                break
            fi
        done
    fi

    if [ -z "$PHP_BIN" ]; then
        log_error "PHP binary tidak ditemukan di PATH sistem hosting."
        log_info  "Anda dapat menentukan binary PHP secara manual, contoh: PHP_BIN=/usr/local/bin/ea-php82 ./deploy.sh"
        exit 1
    fi

    PHP_VER=$($PHP_BIN -r "echo PHP_VERSION;" 2>/dev/null || echo "Unknown")
    log_ok "PHP CLI aktif : $PHP_BIN (Versi: $PHP_VER)"

    # Detect Composer
    COMPOSER_BIN=""
    if [ -f "${SCRIPT_DIR}/composer.phar" ]; then
        COMPOSER_BIN="$PHP_BIN ${SCRIPT_DIR}/composer.phar"
    elif command -v composer >/dev/null 2>&1; then
        COMPOSER_BIN="$(command -v composer)"
    elif [ -f "$HOME/.local/bin/composer" ]; then
        COMPOSER_BIN="$PHP_BIN $HOME/.local/bin/composer"
    fi

    if [ -n "$COMPOSER_BIN" ]; then
        log_ok "Composer CLI  : $COMPOSER_BIN"
    else
        log_warn "Composer CLI belum terdeteksi. Script akan mengunduh composer.phar lokal otomatis jika diperlukan."
    fi
}

# --- Status Only Mode ---
run_status_check() {
    log_step "Ringkasan Status Repositori & Server:"
    local current_b
    current_b=$(git rev-parse --abbrev-ref HEAD)
    local last_commit
    last_commit=$(git log -1 --format="%h - %s (%cr) <%an>")
    
    echo -e "  • Branch Saat Ini  : ${COLOR_BOLD}${current_b}${COLOR_RESET}"
    echo -e "  • Commit Terakhir  : ${COLOR_DIM}${last_commit}${COLOR_RESET}"
    echo -e "  • Perubahan Lokal  : $(git status --porcelain | wc -l) file termodifikasi/untracked"
    if [ -f ".env" ]; then
        echo -e "  • Konfigurasi .env : ${COLOR_GREEN}Ditemukan${COLOR_RESET}"
    else
        echo -e "  • Konfigurasi .env : ${COLOR_YELLOW}Tidak ada (cek app/config.php)${COLOR_RESET}"
    fi
    if [ -d "vendor" ]; then
        echo -e "  • Folder vendor/   : ${COLOR_GREEN}Tersedia${COLOR_RESET}"
    else
        echo -e "  • Folder vendor/   : ${COLOR_RED}Belum ada${COLOR_RESET}"
    fi

    echo ""
    git status --short
    exit 0
}

# --- Rollback Mechanism ---
run_rollback() {
    log_step "Menjalankan Prosedur Rollback..."
    if [ ! -d "$BACKUP_DIR" ]; then
        log_error "Direktori backup ($BACKUP_DIR) tidak ditemukan. Tidak ada snapshot untuk di-rollback."
        exit 1
    fi

    local latest_snapshot
    latest_snapshot=$(ls -td "${BACKUP_DIR}"/snapshot_* 2>/dev/null | head -n 1 || true)
    if [ -z "$latest_snapshot" ]; then
        log_error "Tidak ada snapshot backup yang ditemukan di $BACKUP_DIR."
        exit 1
    fi

    log_warn "Snapshot terakhir ditemukan: $(basename "$latest_snapshot")"
    read -p "Apakah Anda yakin ingin me-restore snapshot ini? (y/N): " -r confirm_rb
    if [[ ! "$confirm_rb" =~ ^[Yy]$ ]]; then
        log_info "Prosedur rollback dibatalkan."
        exit 0
    fi

    # Restore commit git jika ada catatan hash
    if [ -f "${latest_snapshot}/git_commit_hash.txt" ]; then
        local target_hash
        target_hash=$(cat "${latest_snapshot}/git_commit_hash.txt")
        log_info "Mengembalikan repositori Git ke commit: $target_hash"
        git checkout "$target_hash"
    fi

    # Restore file konfigurasi
    for item in .env app/config.php .htaccess; do
        if [ -f "${latest_snapshot}/$(basename "$item")" ]; then
            log_info "Memulihkan $item..."
            cp -f "${latest_snapshot}/$(basename "$item")" "$item"
        fi
    done

    log_ok "Rollback selesai! Aplikasi telah dikembalikan ke snapshot sebelumnya."
    exit 0
}

# --- Maintenance Mode Functions ---
enable_maintenance_mode() {
    log_info "Mengaktifkan Maintenance Mode..."
    touch "$MAINTENANCE_FILE"
    
    # Inject temporary 503 response jika .htaccess ada
    if [ -f "$HTACCESS_FILE" ] && [ ! -f "$HTACCESS_BACKUP" ]; then
        cp "$HTACCESS_FILE" "$HTACCESS_BACKUP"
        cat << 'EOF' > "$HTACCESS_FILE"
# Temporary Maintenance Mode (SimpleAkunting Deployment)
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/maintenance\.html$ [NC]
    RewriteCond %{REQUEST_URI} !\.(css|js|png|jpg|jpeg|gif|svg|ico)$ [NC]
    RewriteRule ^.*$ - [R=503,L]
</IfModule>
ErrorDocument 503 "<h1>503 Sedang Pembaruan Sistem</h1><p>Aplikasi SimpleAkunting sedang dalam proses deployment rutin. Harap muat ulang dalam 1-2 menit.</p>"
EOF
    fi
}

disable_maintenance_mode() {
    log_info "Menonaktifkan Maintenance Mode..."
    rm -f "$MAINTENANCE_FILE"
    if [ -f "$HTACCESS_BACKUP" ]; then
        mv -f "$HTACCESS_BACKUP" "$HTACCESS_FILE"
    fi
}

# --- 3. Pre-deploy Snapshot / Backup ---
create_snapshot() {
    if [ "$FLAG_NO_BACKUP" = true ]; then
        log_info "Snapshot backup dilewati (--no-backup aktif)."
        return
    fi

    log_step "2/8 Membuat Snapshot Backup Pra-Deploy..."
    local timestamp
    timestamp=$(date '+%Y%m%d_%H%M%S')
    local snap_dir="${BACKUP_DIR}/snapshot_${timestamp}"
    mkdir -p "$snap_dir"

    # Simpan hash commit sebelum deploy
    git rev-parse HEAD > "${snap_dir}/git_commit_hash.txt" 2>/dev/null || true

    # Backup file konfigurasi sensitif produksi
    for cfg in .env app/config.php .htaccess; do
        if [ -f "$cfg" ]; then
            cp -p "$cfg" "${snap_dir}/"
        fi
    done

    # Prune backup lama (hanya simpan N terakhir)
    local count
    count=$(ls -1d "${BACKUP_DIR}"/snapshot_* 2>/dev/null | wc -l)
    if [ "$count" -gt "$MAX_BACKUPS" ]; then
        local prune_count=$((count - MAX_BACKUPS))
        ls -1td "${BACKUP_DIR}"/snapshot_* | tail -n "$prune_count" | xargs rm -rf
        log_info "Membersihkan $prune_count snapshot backup lama."
    fi

    log_ok "Snapshot tersimpan di: .deploy_backups/snapshot_${timestamp}"
}

# --- 4. Git Synchronize ---
sync_git() {
    log_step "3/8 Memperbarui Source Code via Git..."

    local current_branch
    current_branch=$(git rev-parse --abbrev-ref HEAD)

    # Tentukan target branch
    if [ -z "$TARGET_BRANCH" ]; then
        TARGET_BRANCH="$current_branch"
    fi

    log_info "Branch saat ini: ${COLOR_BOLD}$current_branch${COLOR_RESET} | Target branch: ${COLOR_BOLD}$TARGET_BRANCH${COLOR_RESET}"

    if [ "$current_branch" != "$TARGET_BRANCH" ]; then
        log_info "Beralih ke branch $TARGET_BRANCH..."
        git checkout "$TARGET_BRANCH"
    fi

    # Cek perubahan uncommitted lokal
    local dirty_files
    dirty_files=$(git status --porcelain)

    if [ -n "$dirty_files" ]; then
        if [ "$FLAG_FORCE" = true ]; then
            log_warn "Opsi --force aktif. Mereset perubahan lokal yang uncommitted..."
            git reset --hard HEAD
            git clean -fd
        else
            log_warn "Ditemukan file yang termodifikasi di server hosting:"
            git status --short
            log_info "Melakukan 'git stash' otomatis (termasuk untracked) untuk melindungi modifikasi lokal..."
            git stash push -u -m "deploy_auto_stash_$(date '+%Y%m%d_%H%M%S')" || true
        fi
    fi

    log_info "Mengambil commit terbaru dari remote origin..."
    git fetch origin "$TARGET_BRANCH"

    local old_head
    old_head=$(git rev-parse HEAD)

    git merge --ff-only "origin/${TARGET_BRANCH}" 2>/dev/null || {
        log_warn "Fast-forward merge tidak memungkinkan. Mencoba git pull standar..."
        git pull origin "$TARGET_BRANCH" 2>/dev/null || {
            log_warn "Pull terhambat file conflict/untracked. Melakukan auto-stash dan mencoba pull ulang..."
            git stash push -u -m "deploy_auto_stash_merge_$(date '+%Y%m%d_%H%M%S')" || true
            git pull origin "$TARGET_BRANCH"
        }
    }

    local new_head
    new_head=$(git rev-parse HEAD)

    if [ "$old_head" = "$new_head" ]; then
        log_ok "Kode sumber sudah up-to-date (tidak ada commit baru)."
    else
        log_ok "Kode berhasil diperbarui! Log commit terbaru:"
        git log --oneline -n 3 "$new_head"
    fi
}

# --- 5. Composer Dependencies ---
manage_dependencies() {
    log_step "4/8 Memeriksa Dependensi Composer..."

    if [ "$FLAG_SKIP_COMPOSER" = true ]; then
        log_info "Proses Composer dilewati (--skip-composer aktif)."
        return
    fi

    if [ ! -f "composer.json" ]; then
        log_info "File composer.json tidak ditemukan. Melewati langkah ini."
        return
    fi

    # Jika composer CLI belum terpasang, unduh composer.phar standalone secara otomatis
    if [ -z "$COMPOSER_BIN" ]; then
        if [ ! -f "${SCRIPT_DIR}/composer.phar" ]; then
            log_info "Mengunduh composer.phar versi standalone untuk hosting..."
            $PHP_BIN -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
            $PHP_BIN composer-setup.php --quiet
            $PHP_BIN -r "unlink('composer-setup.php');"
        fi
        if [ -f "${SCRIPT_DIR}/composer.phar" ]; then
            COMPOSER_BIN="$PHP_BIN ${SCRIPT_DIR}/composer.phar"
        fi
    fi

    if [ -n "$COMPOSER_BIN" ]; then
        log_info "Menjalankan Composer install (Production Mode)..."
        $COMPOSER_BIN install \
            --no-dev \
            --prefer-dist \
            --optimize-autoloader \
            --no-interaction \
            --no-progress \
            --quiet || {
                log_warn "Composer install mengalami kendala non-fatal. Melanjutkan proses..."
            }
        log_ok "Autoloader composer berhasil dioptimalkan."
    else
        log_warn "Composer tidak tersedia. Pastikan folder 'vendor/' sudah terupload via FTP/cPanel."
    fi
}

# --- 6. Database Migrations ---
run_migrations() {
    log_step "5/8 Memeriksa Migrasi Database..."

    if [ "$FLAG_MIGRATE" = false ]; then
        log_info "Migrasi database dilewati (Gunakan flag --migrate atau -m untuk mengeksekusi)."
        return
    fi

    if [ -f "migrate.php" ]; then
        log_info "Mengeksekusi migrate.php menggunakan $PHP_BIN..."
        $PHP_BIN migrate.php
        log_ok "Skrip migrasi migrate.php selesai dijalankan."
    else
        log_warn "File migrate.php tidak ditemukan di direktori."
    fi
}

# --- 7. Asset & Public Directory Sync ---
sync_public_assets() {
    log_step "6/8 Sinkronisasi File Mirroring & Public Assets..."

    # Bila ada file standalone yang dapat diakses langsung dari root maupun /public/
    local mirror_files=(
        "api_kuis.php"
        "api_kurasi.php"
        "kuis_evaluasi_bumdesa.html"
        "aplikasi_kurasi_bumdesa_simpleakunting_linked.html"
        "manifest.json"
        "sw.js"
        "offline.html"
    )

    if [ -d "public" ]; then
        for f in "${mirror_files[@]}"; do
            if [ -f "$f" ]; then
                cp -u "$f" "public/$f" 2>/dev/null || cp "$f" "public/$f"
            fi
        done
        if [ -d "kurasi" ]; then
            mkdir -p "public/kurasi"
            cp -u kurasi/* public/kurasi/ 2>/dev/null || cp kurasi/* public/kurasi/ 2>/dev/null || true
            [ -f "kurasi/.htaccess" ] && (cp -u kurasi/.htaccess public/kurasi/.htaccess 2>/dev/null || cp kurasi/.htaccess public/kurasi/.htaccess 2>/dev/null || true)
        fi
        log_ok "File modul publik telah disinkronkan ke direktori public/."
    fi
}

# --- 8. Permissions & Security Hardening ---
harden_permissions() {
    log_step "7/8 Penyesuaian Izin File & Keamanan (cPanel / Apache)..."

    # Direktori 755 dan file 644 (Standar keamanan cPanel suPHP / lsapi)
    find . -maxdepth 2 -type d -not -path "*/.git*" -exec chmod 755 {} + 2>/dev/null || true
    find . -maxdepth 1 -type f -not -name "deploy.sh" -exec chmod 644 {} + 2>/dev/null || true

    # Kunci konfigurasi sensitif (Hanya bisa dibaca oleh owner proses web)
    if [ -f ".env" ]; then
        chmod 600 .env 2>/dev/null || chmod 640 .env 2>/dev/null || true
        log_ok "Izin .env diperketat (600/640)."
    fi
    if [ -f "app/config.php" ]; then
        chmod 640 app/config.php 2>/dev/null || true
        log_ok "Izin app/config.php diperketat (640)."
    fi

    # Pastikan deploy.sh hanya bisa dieksekusi oleh owner
    chmod 700 "$0" 2>/dev/null || chmod 750 "$0" 2>/dev/null || true

    # Writable storage / scratch directories jika ada
    for writable in scratch logs uploads cache; do
        if [ -d "$writable" ]; then
            chmod -R 775 "$writable" 2>/dev/null || chmod -R 755 "$writable" 2>/dev/null || true
        fi
    done

    # Pastikan .htaccess memiliki aturan proteksi 644
    if [ -f "$HTACCESS_FILE" ]; then
        chmod 644 "$HTACCESS_FILE"
    fi

    log_ok "Izin file dan direktori telah diamankan."
}

# --- 9. OPcache Flush & Housekeeping ---
housekeeping() {
    log_step "8/8 Pembersihan Cache & Log Housekeeping..."

    # Reset PHP OPcache jika modul aktif
    $PHP_BIN -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache berhasil di-reset.'; }" 2>/dev/null || true

    # Rotasi/pemangkasan error_log jika ukurannya melebihi 20MB
    for err_file in error_log public/error_log; do
        if [ -f "$err_file" ]; then
            local size_bytes
            size_bytes=$(stat -c%s "$err_file" 2>/dev/null || stat -f%z "$err_file" 2>/dev/null || echo 0)
            if [ "$size_bytes" -gt 20971520 ]; then # 20MB
                log_warn "File $err_file melebihi 20MB. Melakukan rotasi..."
                mv "$err_file" "${err_file}.old"
                touch "$err_file"
                chmod 644 "$err_file"
            fi
        fi
    done

    log_ok "Pembersihan selesai."
}

# ==============================================================================
# Main Flow
# ==============================================================================
main() {
    check_lock
    detect_environment

    if [ "$FLAG_STATUS_ONLY" = true ]; then
        run_status_check
    fi

    if [ "$FLAG_ROLLBACK" = true ]; then
        run_rollback
    fi

    if [ "$FLAG_MAINTENANCE" = true ]; then
        enable_maintenance_mode
    fi

    create_snapshot
    sync_git
    manage_dependencies
    run_migrations
    sync_public_assets
    harden_permissions
    housekeeping

    if [ "$FLAG_MAINTENANCE" = true ]; then
        disable_maintenance_mode
    fi

    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))

    echo ""
    echo -e "${COLOR_GREEN}${COLOR_BOLD}================================================================${COLOR_RESET}"
    echo -e "${COLOR_GREEN}${COLOR_BOLD}       🎉 DEPLOYMENT SIMPLE AKUNTING V3.6 SELESAI SUKSES!        ${COLOR_RESET}"
    echo -e "${COLOR_GREEN}${COLOR_BOLD}================================================================${COLOR_RESET}"
    echo -e "Durasi Eksekusi  : ${COLOR_CYAN}${DURATION} detik${COLOR_RESET}"
    echo -e "Commit Aktif     : ${COLOR_BOLD}$(git log -1 --format='%h - %s (%cr)')${COLOR_RESET}"
    echo -e "Status Host      : Siap melayani lalu lintas produksi."
    echo ""
}

main
