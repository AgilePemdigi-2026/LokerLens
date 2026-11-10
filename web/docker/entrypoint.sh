#!/bin/sh
# Dijalankan setiap kali container web mulai, sebelum Apache menyala.
set -e
cd /var/www/html

# APP_KEY wajib ada (buat sekali dengan: php artisan key:generate --show)
if [ -z "$APP_KEY" ]; then
    echo "[LokerLens] APP_KEY kosong. Isi APP_KEY di pengaturan environment, lalu deploy ulang." >&2
    exit 1
fi

# storage/ dipasang sebagai volume. Volume baru masih kosong, jadi buat struktur foldernya.
mkdir -p storage/app/private storage/app/public \
         storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs

# Tautan public/storage -> storage/app/public (berkas bukti TIDAK di sini, tapi di storage/app/private)
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

# Migrasi database. Coba ulang beberapa kali karena database bisa belum siap saat container baru mulai.
percobaan=1
until php artisan migrate --force; do
    if [ "$percobaan" -ge 10 ]; then
        echo "[LokerLens] Migrasi gagal setelah 10 kali percobaan. Periksa pengaturan DB_*." >&2
        exit 1
    fi
    echo "[LokerLens] Database belum siap, coba lagi dalam 5 detik ($percobaan/10)..."
    percobaan=$((percobaan + 1))
    sleep 5
done

# Cache konfigurasi, route, view, dan event (dibuat saat container mulai, karena butuh env server)
php artisan optimize

# Semua berkas yang dibuat perintah di atas harus bisa ditulis Apache (www-data)
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
