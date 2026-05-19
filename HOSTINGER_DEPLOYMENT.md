# Hostinger Shared Hosting Deployment Notes

## Status
Deploy ke Hostinger shared hosting memungkinkan, dengan catatan:
- Hosting harus menyediakan PHP 8.3 atau lebih tinggi karena `composer.json` memakai `"php": "^8.3"`.
- Database memakai MySQL, sudah sesuai dengan project ini.
- Queue memakai `database`, sudah sesuai untuk shared hosting.
- Jangan mengandalkan worker queue permanen seperti Supervisor/Horizon di shared hosting.

## Recommended Production `.env`
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.tld

DB_CONNECTION=mysql
DB_HOST=host_mysql_hostinger
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

Jika sudah memakai Cloudflare R2/S3 compatible storage:
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=auto
AWS_BUCKET=...
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_URL=
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Adapter S3/R2 sudah tersedia lewat package:
- `league/flysystem-aws-s3-v3`

## Build and Upload Flow
Di lokal:
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan test
```

Upload project ke hosting, pastikan document root mengarah ke folder `public`.

Di hosting via SSH:
```bash
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Queue on Shared Hosting
Karena shared hosting biasanya tidak cocok untuk proses worker permanen, gunakan cron.

Cron scheduler Laravel:
```bash
/usr/bin/php /home/USER/domains/DOMAIN/public_html/artisan schedule:run
```

Untuk memproses queue secara periodik tanpa worker permanen, project sudah menyiapkan scheduler di `routes/console.php`:
```php
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=60')->everyMinute();
```

## Storage Notes
- `FILESYSTEM_DISK=public` paling mudah untuk deploy awal di shared hosting.
- Jalankan `php artisan storage:link`.
- Untuk asset customer yang harus lebih privat, lebih baik pindah ke R2/S3 agar signed URL benar-benar temporary.
- Station print polling sudah mengembalikan `asset_download_url` dari storage service jika asset tersedia.
- Endpoint customer download URL sudah tersedia di:
  - `POST /api/customer/assets/{cloud_asset_id}/download-url`

## Blockers to Check Before Deploy
- Pastikan hPanel domain memakai PHP 8.3 atau lebih tinggi.
- Pastikan Composer bisa dijalankan via SSH.
- Pastikan folder `storage` dan `bootstrap/cache` writable.
- Pastikan database MySQL sudah dibuat dan `.env` production benar.
- Pastikan `APP_URL` memakai domain final dengan HTTPS.
