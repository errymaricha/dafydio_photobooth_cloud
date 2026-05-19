# Dafydio Photobooth Cloud

Dafydio Photobooth Cloud adalah aplikasi SaaS cloud archive dan customer portal untuk ekosistem Dafydio Photobooth. Aplikasi ini terpisah dari `dafydio_photobooth_station`.

Cloud bertugas menyimpan arsip session, asset foto, subscription, marketplace template, editor cloud, billing, dan koordinasi print request. Station tetap menjadi sumber capture, render lokal, printer queue, dan eksekutor physical print.

## Stack

- Laravel 13
- Inertia Laravel + Vue 3
- MySQL
- Database queue/cache untuk shared hosting
- Laravel Sanctum
- Storage local public untuk deploy awal, dengan struktur siap S3/R2

## Batas Peran Sistem

Cloud:
- Menerima sync session dan asset dari station.
- Menyediakan portal customer via WhatsApp login.
- Menyediakan dashboard admin/tenant.
- Mengelola marketplace template, entitlement, payment manual, subscription, dan print request.
- Menyediakan URL asset/download untuk customer dan station.

Station:
- Capture dari device Android/MiniPC.
- Menjalankan workflow session lokal.
- Melakukan render lokal bila diperlukan.
- Mengelola printer queue dan physical print.
- Push session/asset/template ke cloud.
- Poll print request dari cloud dan update status.

Cloud tidak mengontrol printer secara langsung.

## Alur Login

URL login utama:

```text
/login
```

Mode login:
- Admin memakai Laravel session guard `web`.
- Customer memakai WhatsApp + password dari station, lalu menerima Sanctum token.

Area setelah login:
- Admin: `/admin/dashboard`
- Customer: `/customer/dashboard`

Customer login menerima variasi nomor WhatsApp Indonesia:
- `+628...`
- `628...`
- `08...`

## Credential Demo

Setelah menjalankan seeder:

```text
Admin email: admin@dafydio.local
Admin password: password

Customer WhatsApp: +628111111111
Customer password: password

Tenant slug: dafydio-demo
Station token: station-demo-token
```

## Alur Utama

1. Admin login ke `/login` mode admin.
2. Admin mengelola station, customer, session archive, templates, payments, print requests, sync logs, dan settings.
3. Station melakukan heartbeat dan sync session ke endpoint `/api/station/*` memakai token station.
4. Station register asset, upload file, lalu finalize session.
5. Customer login ke `/login` mode customer memakai WhatsApp dan password dari station.
6. Customer melihat session, download asset, membeli template, membuat edit job, dan membuat print request.
7. Admin melakukan review pembayaran manual jika dibutuhkan.
8. Station polling print request yang siap diproses, mencetak secara lokal, lalu update status ke cloud.

## Setup Lokal

Install dependency:

```bash
composer install
npm install
```

Siapkan environment:

```bash
cp .env.example .env
php artisan key:generate
```

Contoh database lokal MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dafydio_photobooth_cloud
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migration dan seed demo:

```bash
php artisan migrate:fresh --seed
```

Jalankan server cloud di port berbeda dari station:

```bash
php artisan serve --host=0.0.0.0 --port=8001
```

Jalankan frontend:

```bash
npm run dev
```

Jika `photobooth_station` sudah memakai port `8000`, gunakan cloud di `8001`.

## Build dan Verifikasi

Format kode:

```bash
vendor/bin/pint --dirty
```

Test backend:

```bash
php artisan test
```

Build frontend:

```bash
npm run build
```

Cek route:

```bash
php artisan route:list --except-vendor
```

Status verifikasi terakhir dari catatan progress:

```text
php artisan test: 51 tests passed
npm run build: berhasil
php artisan route:list --except-vendor: berhasil
```

## Deployment Shared Hosting

Target awal disiapkan untuk Hostinger/shared hosting:

- MySQL, bukan PostgreSQL.
- Queue/cache memakai database driver.
- Storage awal bisa memakai `public`.
- Struktur S3/R2 sudah disiapkan untuk tahap berikutnya.
- Cron dapat menjalankan Laravel scheduler untuk memproses queue.

Perintah penting setelah upload:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Cron scheduler:

```bash
php artisan schedule:run
```

Catatan: migration wajib dijalankan karena ada index performa admin dan kolom `stations.api_token_lookup`.

## Optimasi Data

Daftar admin sudah memakai pagination 20 data per halaman untuk mengurangi beban render dan query:

- Customers
- Customer detail sessions
- Sessions
- Payments
- Sync Logs
- Templates
- Stations

Index database sudah ditambahkan untuk pola query umum:

- `tenant_id`
- `created_at`
- `status`
- `topic`
- `plan`
- `customer_id`
- `station_id`
- `stations.api_token_lookup`

Untuk data sangat besar, pekerjaan lanjutan yang disarankan:
- Preview payload sync log, detail log dibuka terpisah.
- Full-text search atau kolom search ringkas untuk pencarian besar.
- S3/R2 signed URL untuk storage privat skala produksi.

## Dokumentasi Project

Dokumen utama:

- `AGENTS.md`: aturan kerja AI, stack, boundary, dan arah UI.
- `PROGRESS.md`: catatan perubahan, keputusan, verifikasi, dan blocker.
- `ARCHITECTURE.md`: arsitektur cloud/station, queue, storage, auth, deploy.
- `DATA_MODEL.md`: tabel, relasi, tenant scope, dan status.
- `API_CONTRACT.md`: kontrak API station, customer, dan admin.
- `HOSTINGER_DEPLOYMENT.md`: catatan deploy shared hosting.

Setiap perubahan teknis harus dicatat di `PROGRESS.md`.

## GitHub

Repository:

```text
git@github.com:errymaricha/dafydio_photobooth_cloud.git
https://github.com/errymaricha/dafydio_photobooth_cloud
```
