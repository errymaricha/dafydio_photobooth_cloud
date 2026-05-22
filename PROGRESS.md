# Dafydio Photobooth Cloud Progress

Dokumen ini adalah catatan status kerja. Setiap perubahan penting harus ditambahkan di sini supaya proses tetap jelas untuk developer dan AI yang melanjutkan.

## Status Saat Ini
- Project sudah memakai Laravel 13.8.0.
- Frontend baseline memakai Inertia Vue.
- Database target deployment sudah disesuaikan ke MySQL.
- Queue/cache target hosting memakai database driver, bukan Redis.
- Storage tetap diarahkan ke S3/R2 compatible storage.
- Cloud tetap hanya menjadi arsip, portal, marketplace, billing, editor, dan print request coordinator.
- Station tetap menjadi executor capture dan physical printing.

## 2026-05-18 22:51 - SOP Concept Cloud Print Request
Perubahan dokumentasi:
- Menambahkan konsep SOP khusus request print dari cloud di `ARCHITECTURE.md`.
- Menegaskan bahwa cloud hanya membuat order/antrian print request, bukan menjalankan printer.
- Menambahkan status konseptual request print cloud:
  - `pending_payment`
  - `pending_operator`
  - `claimed`
  - `printing`
  - `printed`
  - `failed`
  - `cancelled`
- Menambahkan alur customer, admin cloud, dan station/operator.
- Memperbarui `API_CONTRACT.md` agar polling station memakai request siap operator (`pending_operator`) dan approve payment `template_print_request` mengubah request ke `pending_operator`.

Keputusan:
- Request print dari cloud hanya dieksekusi ketika operator station aktif memilih/memproses antrian.
- Setelah payment approve, status tidak langsung `printing`, tetapi `pending_operator`.

Verifikasi:
- Perubahan hanya dokumentasi, tidak menjalankan test.

## 2026-05-18 22:24 - Cloud Editor Render Result Asset
Perubahan:
- Menambahkan action `app/Actions/Customer/RenderCloudEditJob.php`.
- Create edit job customer sekarang langsung menjalankan render sederhana memakai source photo customer dan frame/source template cloud.
- Hasil render disimpan sebagai `cloud_session_assets` bertipe `edited` pada path `tenants/{tenant_id}/sessions/{session_id}/edited/{edit_job_id}.jpg`.
- `cloud_edit_jobs.status` berubah menjadi `completed` jika render berhasil, atau `failed` dengan `error_message` jika file source/frame belum tersedia.
- Endpoint `GET /api/customer/edit-jobs` sekarang mengembalikan `result_asset` berisi file URL hasil edit.
- Customer dashboard menampilkan tombol `Download Hasil Edit` pada edit job completed dan refresh session setelah edit.
- Memperbarui `API_CONTRACT.md` untuk response edit job completed.

Catatan:
- Render saat ini synchronous dan sederhana: source photo dipasang ke slot pertama template, lalu frame template dioverlay.
- Rotation/multi-slot kompleks belum diimplementasikan.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 44 tests passed dengan 392 assertions.

## 2026-05-18 22:14 - Manual Payment Marketplace
Perubahan:
- Menambahkan admin payment review untuk marketplace di `GET /admin/payments`.
- Admin bisa approve/reject payment pending lewat `POST /admin/payments/{payment}/approve` dan `POST /admin/payments/{payment}/reject`.
- Approve payment `template_purchase` otomatis membuat `customer_template_entitlements`.
- Menambahkan endpoint customer `GET /api/customer/payments`.
- Customer dashboard menampilkan payment pending dan instruksi pembayaran manual/QRIS setelah beli template berbayar.
- Purchase template manual sekarang mengembalikan `manual_instruction` dan `payment_url: null`.
- Dashboard admin sekarang menautkan menu Payments dan Logs ke route aktif.
- Memperbarui `API_CONTRACT.md` untuk payment manual marketplace.

File utama:
- `app/Http/Controllers/Admin/PaymentController.php`
- `app/Http/Controllers/Api/Customer/PaymentController.php`
- `app/Http/Controllers/Api/Customer/TemplateController.php`
- `app/Models/Payment.php`
- `routes/web.php`
- `routes/api.php`
- `resources/js/Pages/Admin/Payments/Index.vue`
- `resources/js/Pages/Customer/Dashboard.vue`
- `resources/js/Pages/Admin/Dashboard.vue`
- `tests/Feature/DocumentedApiContractTest.php`
- `tests/Feature/AdminAuthAndStationTokenTest.php`
- `API_CONTRACT.md`

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil dan menampilkan route payments.
- `php artisan test` berhasil, 44 tests passed dengan 390 assertions.

## 2026-05-18 22:03 - Admin Sync Logs UI
Perubahan:
- Menambahkan controller `app/Http/Controllers/Admin/SyncLogController.php`.
- Menambahkan route admin `GET /admin/sync-logs`.
- Menambahkan halaman `resources/js/Pages/Admin/SyncLogs/Index.vue`.
- Sync Logs UI menampilkan station, direction, topic, status, idempotency key, error, payload, dan response.
- Menambahkan filter/search berdasarkan station, idempotency key, topic, status, payload, response, dan error.
- Menambahkan menu Logs yang aktif di sidebar/mobile nav.
- Menambahkan test admin untuk melihat dan filter sync logs.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test tests\Feature\AdminAuthAndStationTokenTest.php` berhasil, 15 tests passed.
- `php artisan route:list --except-vendor --path=admin/sync-logs` berhasil.
- `php artisan test` berhasil, 42 tests passed dengan 354 assertions.

## 2026-05-18 21:55 - Admin Session Detail Identity UI
Perubahan:
- Memperjelas detail session admin dengan badge `Guest` atau `Customer` di header dan panel customer.
- Menambahkan tombol `Chat WhatsApp` untuk session yang sudah punya customer.
- Menambahkan tombol `Detail Customer` langsung dari detail session.
- Memperjelas panel guest dengan label `Guest - {session_code}` dan instruksi link WhatsApp.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test tests\Feature\AdminAuthAndStationTokenTest.php` berhasil, 14 tests passed.
- `php artisan test` berhasil, 41 tests passed dengan 332 assertions.

## 2026-05-18 21:54 - Admin Session Archive Search
Perubahan:
- Menambahkan pencarian global di `Admin/Sessions/Index.vue`.
- Search mendukung session title, station session ID, metadata/session code, nama/WhatsApp customer, serta nama/kode station.
- Menambahkan filter status session: all, pending, syncing, complete, failed.
- Filter identity guest/customer tetap dipertahankan dan digabung dengan search/status.
- Pagination session mempertahankan query string filter.
- Menambahkan test admin untuk pencarian archive berdasarkan WhatsApp dan station code.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test tests\Feature\AdminAuthAndStationTokenTest.php` berhasil, 14 tests passed.
- `npm run build` berhasil.
- `php artisan test` berhasil, 41 tests passed dengan 332 assertions.

## 2026-05-18 21:52 - Admin Guest Link UI
Perubahan:
- Menambahkan route web admin `POST /admin/sessions/{session}/link-customer`.
- Menambahkan form di detail session guest untuk isi WhatsApp, nama, dan tier customer.
- Setelah link, cloud membuat/mencari customer, mengisi `cloud_sessions.customer_id`, memperbarui metadata guest, dan membuat subscription default.
- Menambahkan tombol `Public Gallery` di detail session admin.
- Memastikan password auto-generated untuk customer hasil sync/link disimpan sebagai hash.
- Menambahkan test admin untuk link guest session ke customer.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test tests\Feature\AdminAuthAndStationTokenTest.php tests\Feature\StationApiTest.php` berhasil, 25 tests passed.
- `npm run build` berhasil.
- `php artisan test` berhasil, 40 tests passed dengan 302 assertions.
- `php artisan route:list --except-vendor` berhasil dan menampilkan route admin link customer.

## 2026-05-18 11:18 - Guest Session Linking and Archive Filter
Perubahan:
- Menambahkan endpoint station `POST /api/station/sessions/{cloud_session_id}/link-customer`.
- Endpoint link guest membuat/mencari customer berdasarkan WhatsApp, mengisi `cloud_sessions.customer_id`, dan memperbarui metadata session.
- Asset guest tidak dipindahkan saat link; path lama tetap valid.
- Admin sessions sekarang menampilkan guest sebagai `Guest - {session_code}`, bukan `null`.
- Menambahkan filter archive admin: `Semua`, `Customer dengan WA`, dan `Guest`.
- Detail session admin menampilkan label guest yang sama jika customer belum ada.
- Memperbarui `API_CONTRACT.md` dan `docs/photobooth-cloud-integration.md`.
- Menambahkan test station untuk link guest session ke customer belakangan.
- Menambahkan test admin untuk filter guest sessions.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test tests\Feature\StationApiTest.php tests\Feature\AdminAuthAndStationTokenTest.php` berhasil, 24 tests passed.
- `npm run build` berhasil.
- `php artisan test` berhasil, 39 tests passed dengan 295 assertions.
- `php artisan route:list --except-vendor` berhasil dan menampilkan `POST api/station/sessions/{cloudSession}/link-customer`.

## 2026-05-18 11:12 - Guest Session Sync Support
Perubahan:
- Mengubah `POST /api/station/sync/session` agar menerima `session.customer_whatsapp = null`.
- Menambahkan migrasi `2026_05_18_000000_allow_guest_cloud_sessions.php` agar `cloud_sessions.customer_id` nullable.
- Cloud tidak lagi membuat customer dummy jika WhatsApp kosong.
- Guest session tetap tersimpan sebagai archive session dengan `metadata.is_guest = true`.
- Asset guest session disimpan di path `tenants/{tenant_id}/guests/sessions/{cloud_session_id}/...`.
- Admin session list menampilkan `Guest Session` jika tidak ada customer.
- Memperbarui `API_CONTRACT.md`, `DATA_MODEL.md`, dan `docs/photobooth-cloud-integration.md`.
- Menambahkan test guest session sync tanpa membuat customer.

Keputusan:
- Guest session tidak masuk customer portal karena tidak punya identitas login WhatsApp.
- Linking guest session ke customer WhatsApp belakangan belum dibuat; perlu endpoint/action khusus berikutnya.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test tests\Feature\StationApiTest.php` berhasil, 11 tests passed.
- `php artisan test` berhasil, 37 tests passed dengan 269 assertions.
- `php artisan migrate` berhasil menjalankan migrasi guest session.

## 2026-05-17 14:07 - Customer Session Latest Ordering Fix
Perubahan:
- Memperbaiki urutan `GET /api/customer/sessions` agar memakai `COALESCE(started_at, created_at) desc`.
- Menambahkan test agar session baru dengan `started_at = null` tetap muncul paling atas di portal customer.

Temuan:
- Session `SES-WPNKQ2KY` sudah tersimpan untuk customer `6282118401998` dengan status `complete` dan 3 asset uploaded.
- Penyebab kemungkinan tidak terlihat di client: session baru punya `started_at` kosong, sementara API sebelumnya mengurutkan hanya dari `started_at`.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test tests\Feature\CustomerSanctumAuthTest.php` berhasil, 3 tests passed.
- `php artisan test` berhasil, 36 tests passed dengan 261 assertions.

## 2026-05-17 10:32 - Minimal Customer Cloud Editor Queue
Perubahan:
- Menambahkan endpoint customer `GET /api/customer/edit-jobs` untuk melihat daftar job editor.
- Menambahkan relasi `CloudEditJob` ke session, source asset, result asset, dan template.
- Menambahkan tab `Editor` di customer dashboard untuk melihat status edit job.
- Menambahkan pilihan template dan tombol `Edit` pada detail asset session customer.
- Memperbarui `API_CONTRACT.md` untuk flow create/list edit job.
- Menambahkan verifikasi test untuk list edit job setelah customer membuat job editor.

Keputusan:
- Cloud Editor tahap ini baru membuat job `queued`; render otomatis belum diaktifkan.
- Customer hanya bisa membuat edit job dengan template yang sudah owned/free purchased atau premium sesuai tier.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 36 tests passed dengan 261 assertions.
- `php artisan route:list --except-vendor` berhasil dan menampilkan `GET /api/customer/edit-jobs`.

## 2026-05-16 01:08 - Final Template Import API Contract
Perubahan:
- Menambahkan kontrak final import template station ke `API_CONTRACT.md`.
- Mendokumentasikan flow:
  - sync metadata template
  - register template assets
  - upload binary asset
  - complete asset
- Menegaskan bahwa template asset memakai `station_asset_id`, sehingga station tidak wajib menyimpan `cloud_asset_id`.

Verifikasi:
- Perubahan dokumentasi saja, tidak ada test yang diperlukan.

## 2026-05-16 00:56 - Four-App Offline-First Role Split
Perubahan:
- Memperjelas `ARCHITECTURE.md` untuk pembagian peran empat aplikasi:
  - `dafydio_photobooth_station`
  - `dafydio_photobooth_cloud`
  - `dafydio_photobooth_android`
  - `dafydio_photobooth_miniPC`
- Menambahkan topologi `Android/MiniPC -> Station -> Cloud`.
- Menegaskan bahwa device tidak langsung bergantung ke cloud dan station menjadi pusat operasional lokal.

Verifikasi:
- Perubahan dokumentasi saja, tidak ada test yang diperlukan.

## 2026-05-16 00:49 - LAN Offline-First Integration Rule
Perubahan:
- Menambahkan prinsip LAN/offline-first di `ARCHITECTURE.md`.
- Menambahkan aturan station LAN di `docs/photobooth-cloud-integration.md`.

Keputusan:
- Cloud tidak boleh pull data dari station.
- Cloud tidak boleh membutuhkan IP/URL/port station.
- Semua integrasi memakai station push ke cloud atau station polling cloud.
- Berlaku untuk session sync, asset upload, template publish, print request polling, dan status update.

Verifikasi:
- Perubahan dokumentasi saja, tidak ada test yang diperlukan.

## 2026-05-16 00:39 - Template Import Asset Upload Flow
Perubahan:
- Menambahkan flow utama import template dari station dengan register/upload/complete asset template.
- Endpoint baru:
  - `POST /api/station/templates/{template}/assets`
  - `PUT|POST /api/station/templates/{template}/assets/{stationAssetId}/upload`
  - `POST /api/station/templates/{template}/assets/{stationAssetId}/complete`
- Upload asset template menyimpan file ke public disk pada layout `tenants/{tenant_id}/templates/{template_id}/{asset_type}/{station_asset_id}.{ext}`.
- Complete asset preview mengupdate `preview_path`; complete asset frame/source mengupdate `source_path`.
- Admin cloud mendapat fitur tambahan terbatas untuk upload preview/source file langsung dari `/admin/templates`.
- Dokumentasi integrasi station template asset upload ditambahkan di `docs/photobooth-cloud-integration.md`.

Keputusan:
- Import utama tetap dari photobooth_station.
- Cloud admin upload hanya fitur tambahan terbatas, bukan master template workflow.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test --filter=StationApiTest` berhasil, 10 tests passed dengan 76 assertions.
- `php artisan test` berhasil, 36 tests passed dengan 257 assertions.
- `php artisan route:list --except-vendor` berhasil dan route template asset upload muncul.

## 2026-05-16 00:30 - Station Template Publish Sync
Perubahan:
- Menambahkan endpoint `POST /api/station/sync/template` untuk publish template dari station ke cloud marketplace.
- Menambahkan `app/Http/Controllers/Api/Station/TemplateSyncController.php`.
- Menambahkan migration `2026_05_16_002740_add_station_publish_fields_to_cloud_templates_table.php`.
- `cloud_templates` sekarang menyimpan `station_id`, `station_template_id`, `template_code`, `category`, `paper_size`, `slots`, `asset_manifest`, dan `published_at`.
- Menyesuaikan payload admin/customer template agar menampilkan metadata station template.
- Menambahkan dokumentasi kontrak endpoint template publish di `docs/photobooth-cloud-integration.md`.
- Menambahkan test station template sync idempotent.

Keputusan:
- Station tetap master template lokal.
- Cloud hanya menerima template yang sudah siap publish dan menyimpan struktur untuk marketplace/editor.
- Cloud tidak mengambil template langsung dari Android dan tidak menjalankan logic rendering station.

Verifikasi:
- `php artisan migrate` berhasil.
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test --filter=StationApiTest` berhasil, 10 tests passed dengan 68 assertions.
- `php artisan test` berhasil, 36 tests passed dengan 249 assertions.
- `php artisan route:list --except-vendor` berhasil dan route `api/station/sync/template` muncul.

## 2026-05-16 00:21 - Template Marketplace UI Tahap Awal
Perubahan:
- Menambahkan web admin Template Marketplace di `/admin/templates`.
- Menambahkan `app/Http/Controllers/Admin/TemplateController.php` untuk list/create/update/delete template tenant.
- Menambahkan halaman `resources/js/Pages/Admin/Templates/Index.vue` untuk form template dan daftar template.
- Menambahkan tab Marketplace di `resources/js/Pages/Customer/Dashboard.vue`.
- Customer marketplace bisa melihat template, preview, status owned/premium, harga, dan purchase template marketplace.
- Memperkaya `app/Http/Controllers/Api/Customer/TemplateController.php` dengan `preview_url`, `is_owned`, `is_available`, dan `is_premium_included`.
- Menambahkan link Templates di sidebar/bottom nav dashboard admin.
- Menambahkan coverage test admin template menu dan customer template payload.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil dan route `/admin/templates` muncul.
- `php artisan test` berhasil, 35 tests passed dengan 233 assertions.
- Setelah update nav dashboard: `npm run build` berhasil dan `php artisan test --filter=AdminAuthAndStationTokenTest` berhasil, 11 tests passed dengan 98 assertions.

## 2026-05-16 00:12 - Customer Dashboard Session Portal
Perubahan:
- Mengubah `resources/js/Pages/Customer/Dashboard.vue` menjadi portal customer mobile-first.
- Menambahkan hero latest session, riwayat session, modal detail session, preview asset, open public gallery, download, share WhatsApp, download semua, dan print request.
- Mengubah `app/Http/Controllers/Api/Customer/SessionController.php` agar API customer sessions mengirim `session_code`, `public_url`, `download_all_url`, dan `file_url` asset.
- Memperluas route public gallery di `routes/web.php` agar session code non-`SES-` tetap bisa dibuka jika dibutuhkan.
- Menambahkan coverage payload customer sessions di `tests/Feature/CustomerSanctumAuthTest.php`.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil.
- `php artisan test` berhasil, 34 tests passed dengan 211 assertions.

## 2026-05-16 00:05 - Public Gallery Remove Raw File Button
Perubahan:
- Menghapus tombol `Buka File Asli` dari fullscreen public gallery di `resources/js/Pages/Public/SessionShow.vue`.
- Aksi fullscreen sekarang fokus pada download dan share WhatsApp.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 24 assertions.

## 2026-05-16 00:04 - Public Gallery Download Semua dan Share Link
Perubahan:
- Menambahkan route `GET /{sessionCode}/download` untuk download semua foto public gallery sebagai ZIP.
- Menambahkan `share_url` dan `download_all_url` pada payload public gallery dari `app/Http/Controllers/PublicSessionController.php`.
- Menambahkan tombol `Download Semua` dan `Share Link Gallery` di `resources/js/Pages/Public/SessionShow.vue`.
- Merapikan ulang file Vue agar karakter panah bersih dan tetap ASCII.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 24 assertions.
- `php artisan route:list --except-vendor` berhasil dan route public download muncul.
- `php artisan test` berhasil, 34 tests passed dengan 207 assertions.
- `php -r "echo class_exists('ZipArchive') ? 'zip-ok' : 'zip-missing';"` menghasilkan `zip-ok`.

## 2026-05-15 23:59 - Public Gallery Header Restored
Perubahan:
- Menambahkan kembali header ringkas Dafydio Photobooth dan kode session di `resources/js/Pages/Public/SessionShow.vue`.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 20 assertions.

## 2026-05-15 23:57 - Public Gallery Card UI Restored
Perubahan:
- Mengembalikan tampilan card gallery utama di `resources/js/Pages/Public/SessionShow.vue`.
- Tetap menghapus thumbnail/info summary atas sesuai request.
- Foto utama kembali memakai tinggi card nyaman, bukan full viewport.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 20 assertions.

## 2026-05-15 23:50 - Public Gallery Direct Photo View
Perubahan:
- Menghapus header dan summary card atas di `resources/js/Pages/Public/SessionShow.vue`.
- Public gallery sekarang langsung menampilkan satu foto full viewport saat URL dibuka.
- Branding Dafydio Photobooth dipindah menjadi overlay kecil di atas foto.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 20 assertions.

## 2026-05-15 23:43 - Public Gallery Single Album Card
Perubahan:
- Mengubah `resources/js/Pages/Public/SessionShow.vue` menjadi satu card gallery saja.
- Urutan album sekarang frame terlebih dahulu, lalu original 1, original 2, dan seterusnya.
- Carousel utama dan preview fullscreen tetap mendukung swipe, panah, download, dan share WhatsApp.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 20 assertions.

## 2026-05-15 23:39 - Fix Public Gallery Share Session Reference
Perubahan:
- Memperbaiki error `session is not defined` di `resources/js/Pages/Public/SessionShow.vue`.
- Fungsi share WhatsApp sekarang memakai `props.session`.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 20 assertions.

## 2026-05-15 23:35 - Public Gallery Carousel dan WhatsApp Share
Perubahan:
- Mengubah `resources/js/Pages/Public/SessionShow.vue` agar Frame Photo dan Original Photo tampil sebagai carousel satu foto besar, bukan grid card kecil.
- Menambahkan navigasi panah dan gesture swipe kanan/kiri pada carousel dan preview fullscreen.
- Menambahkan tombol `Share WhatsApp` dengan teks branding Dafydio Photobooth.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 20 assertions.

## 2026-05-15 23:29 - Public Gallery Premium Preview
Perubahan:
- Mengubah `resources/js/Pages/Public/SessionShow.vue` menjadi tampilan gallery premium mobile.
- Menambahkan hero foto frame utama, grid 2 kolom untuk mobile, dan preview fullscreen.
- Menambahkan tombol download dan buka file asli di preview fullscreen.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed dengan 20 assertions.

## 2026-05-15 23:27 - Public Gallery Mobile Download UI
Perubahan:
- Memperbarui `resources/js/Pages/Public/SessionShow.vue` menjadi gallery mobile-friendly dengan branding Dafydio Photobooth.
- Menambahkan tombol download besar untuk frame dan original photo.
- Menambahkan nama file download yang ramah dari `app/Http/Controllers/PublicSessionController.php`, contoh `Dafydio-Photobooth-SES-NTRPXPBS-Frame-01.jpg`.
- Menyesuaikan `tests/Feature/PublicSessionGalleryTest.php` untuk validasi nama file download.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 34 tests passed dengan 203 assertions.

## 2026-05-14 22:40 - Melengkapi Endpoint Kontrak API
Perubahan:
- Menambahkan endpoint station `POST /api/station/customers`.
- Menambahkan endpoint customer purchase template dan create edit job.
- Menambahkan admin API tenant-scoped untuk stations, customers, sessions, print requests, dan template CRUD.
- Menambahkan middleware `tenant.admin` agar token customer tidak bisa mengakses admin API.
- Menambahkan test kontrak API terdokumentasi.

File utama:
- `routes/api.php`
- `bootstrap/app.php`
- `app/Http/Middleware/EnsureTenantAdmin.php`
- `app/Http/Controllers/Api/Station/CustomerController.php`
- `app/Http/Controllers/Api/Customer/TemplateController.php`
- `app/Http/Controllers/Api/Customer/EditJobController.php`
- `app/Http/Controllers/Api/Admin/*`
- `tests/Feature/DocumentedApiContractTest.php`

Verifikasi:
- `php artisan route:list --except-vendor` berhasil, 38 routes.
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test` berhasil, 24 tests passed dengan 96 assertions.

Catatan lanjutan:
- Payment provider Midtrans/Xendit masih placeholder webhook.
- Admin UI detail CRUD belum dibuat untuk semua endpoint API.

## 2026-05-14 22:50 - Endpoint Sync Session Photo Station
Perubahan:
- Menambahkan endpoint idempotent `POST /api/station/sync/session`.
- Endpoint menerima body event/session dari `dafydio_photobooth_station`.
- Wajib memakai header `Idempotency-Key`.
- Membuat/memperbarui customer berdasarkan WhatsApp tenant.
- Membuat/memperbarui subscription regular/premium dari `customer_tier`.
- Membuat/memperbarui `cloud_sessions` berdasarkan `tenant_id + station_id + station_session_id`.
- Menyimpan idempotency key dan response di `station_sync_logs`.
- Menambahkan migration unique key untuk idempotency sync log.
- Menambahkan test retry request sama tidak membuat customer/session/log double.

File utama:
- `routes/api.php`
- `app/Http/Controllers/Api/Station/SessionSyncController.php`
- `app/Models/StationSyncLog.php`
- `database/migrations/2026_05_14_224500_add_idempotency_key_to_station_sync_logs_table.php`
- `tests/Feature/StationApiTest.php`

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan route:list --except-vendor` berhasil, 39 routes.
- `php artisan test` berhasil, 26 tests passed dengan 107 assertions.

## 2026-05-14 23:07 - Log Penerimaan Asset Station
Perubahan:
- Menambahkan pencatatan `station_sync_logs` saat station register asset session.
- Menambahkan pencatatan `station_sync_logs` saat station menandai asset upload complete.
- Log asset memakai topic `asset-register` dan `asset-complete`.
- Jika request membawa `Idempotency-Key`, log di-update idempotently; jika tidak, dibuat sebagai log attempt baru.
- Test station asset sync diperluas untuk memastikan log asset tercatat.

File utama:
- `app/Http/Controllers/Api/Station/SessionSyncController.php`
- `tests/Feature/StationApiTest.php`

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test` berhasil, 26 tests passed dengan 109 assertions.

## 2026-05-14 23:10 - Tampilkan Recent Sessions di Admin Dashboard
Perubahan:
- Menambahkan prop `recentSessions` di `resources/js/Pages/Admin/Dashboard.vue`.
- Menambahkan panel `Recent Sessions` dengan title, customer, station, sync status, jumlah asset, dan waktu dibuat.
- Panel memakai empty state saat belum ada session.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 26 tests passed dengan 109 assertions.

## 2026-05-14 23:15 - Detail Session Admin
Perubahan:
- Menambahkan route admin `GET /admin/sessions/{session}`.
- Menambahkan controller `Admin\SessionController@show`.
- Menambahkan halaman Inertia `Admin/Sessions/Show.vue`.
- Detail session menampilkan data session, customer, station, dan tabel asset.
- Tombol `Detail` ditambahkan pada panel Recent Sessions dashboard.
- Menambahkan test tenant admin bisa melihat detail session beserta customer dan asset.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 27 tests passed dengan 123 assertions.

## 2026-05-14 23:21 - Layout Detail Session Disamakan Dashboard
Perubahan:
- Menyesuaikan `Admin/Sessions/Show.vue` agar memakai shell admin dashboard:
  - sidebar desktop.
  - mobile topbar.
  - bottom navigation mobile.
  - spacing konten `lg:ml-[260px]` dan header dashboard-style.
- Menambahkan logout action di shell detail session.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 27 tests passed dengan 123 assertions.

## 2026-05-14 23:25 - Menu Sessions dan Customers Admin
Perubahan:
- Menambahkan menu web admin `GET /admin/sessions`.
- Menambahkan menu web admin `GET /admin/customers`.
- Menambahkan halaman `Admin/Sessions/Index.vue` dengan layout dashboard.
- Menambahkan halaman `Admin/Customers/Index.vue` dengan layout dashboard.
- Sidebar dashboard dan detail session sekarang mengarah ke route menu asli.
- Menambahkan relasi customer untuk session, print request, dan subscription count.
- Menambahkan test akses menu sessions dan customers.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil, 42 routes.
- `php artisan test` berhasil, 29 tests passed dengan 143 assertions.

## 2026-05-15 02:41 - Kompatibilitas Field Upload Asset Station
Perubahan:
- Endpoint `POST /api/station/sessions/{cloud_session_id}/assets` sekarang menerima field station:
  - `asset_type`
  - `file_name`
  - `file_size`
- Format lama `type`, `filename`, dan `size_bytes` tetap diterima.
- Endpoint `POST /api/station/assets/{cloud_asset_id}/complete` sekarang menerima:
  - `status: completed|uploaded`
  - `file_size`
- Format lama `size_bytes` tetap diterima.
- Path storage disesuaikan ke pola `tenants/{tenant_id}/customers/{customer_id}/sessions/{cloud_session_id}/{asset_type}/{file}`.
- Menambahkan test flow station field names untuk original/framed asset.
- `API_CONTRACT.md` diperbarui mengikuti field yang dipakai station.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test` berhasil, 30 tests passed dengan 151 assertions.

## 2026-05-15 10:42 - Apply Migration Idempotency Key
Perubahan:
- Menjalankan migration pending `2026_05_14_224500_add_idempotency_key_to_station_sync_logs_table`.
- Kolom `station_sync_logs.idempotency_key` sekarang tersedia di database cloud lokal.

Verifikasi:
- `php artisan migrate` berhasil.
- `php artisan migrate:status` menunjukkan migration idempotency key sudah `Ran`.
- `php artisan test` berhasil, 30 tests passed dengan 151 assertions.

## 2026-05-15 10:46 - Tampilkan File Asset di Detail Session
Perubahan:
- Detail session admin sekarang mengirim `file_url` untuk asset uploaded.
- Halaman `Admin/Sessions/Show.vue` menampilkan preview image kecil jika asset berupa gambar.
- Tabel asset menambahkan tombol `Open File`.
- Test detail session memastikan `file_url` tersedia.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 30 tests passed dengan 153 assertions.

## 2026-05-15 11:04 - Fallback Upload Asset untuk Shared Hosting
Perubahan:
- Menambahkan fallback upload URL untuk disk `public`: `POST|PUT /api/station/assets/{cloudAsset}/upload`.
- Jika default disk `s3` tetapi `AWS_BUCKET` kosong, asset baru otomatis memakai disk `public`.
- Endpoint upload menerima raw binary body atau multipart `file`.
- `CloudAssetUrlService` tidak lagi error 500 saat S3/R2 belum lengkap; fallback ke endpoint upload Laravel untuk disk public.
- Menambahkan log `asset-upload`.
- Menambahkan test upload asset ke fallback public endpoint.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan route:list --except-vendor` berhasil, 43 routes.
- `php artisan test` berhasil, 31 tests passed dengan 160 assertions.

## 2026-05-15 11:07 - Payload Station Lengkap
Perubahan:
- Endpoint `POST /api/station/sync/session` sekarang menerima field tambahan:
  - `event.cloud_member_scope`
  - `event.cloud_sync_timing`
  - `session.customer_id`
  - `session.payment_method`
  - `session.captured_at`
  - `session.completed_at`
- Field tambahan disimpan di metadata session.
- Endpoint finalize menerima body `status: completed`.
- Test station sync diperluas sesuai payload lengkap dari station.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test` berhasil, 31 tests passed dengan 161 assertions.

## 2026-05-15 11:33 - Detail Customer Admin dengan Daftar Session
Perubahan:
- Menambahkan route `GET /admin/customers/{customer}`.
- Menambahkan `Admin\CustomerController@show`.
- Menambahkan halaman `Admin/Customers/Show.vue`.
- Menu `/admin/customers` sekarang punya tombol `Detail`.
- Detail customer menampilkan info customer, plan/status, dan daftar session customer.
- Daftar session pada detail customer bisa klik ke detail session.
- Menambahkan test detail customer dengan sessions.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 32 tests passed dengan 173 assertions.

## 2026-05-15 11:36 - Tombol Kembali Customer dari Detail Session
Perubahan:
- Link `Detail Session` dari halaman detail customer sekarang membawa query `from_customer=1`.
- Detail session menampilkan tombol `Kembali Customer` jika dibuka dari halaman customer.
- Tombol mengarah ke `/admin/customers/{customer_id}`.
- Test detail session diperbarui untuk memastikan `backToCustomerUrl` tersedia.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 32 tests passed dengan 175 assertions.

## 2026-05-15 15:13 - Endpoint Sync Cloud Account Customer
Perubahan:
- Menambahkan endpoint station `POST /api/station/customers/cloud-account`.
- Endpoint menerima payload dari station:
  - `customer_whatsapp`
  - `username`
  - `password`
  - `tier`
  - `status`
- Password plain dari station disimpan cloud sebagai hash lewat cast model Customer.
- Customer subscription dibuat/diperbarui berdasarkan `tier`.
- Menambahkan test station bisa sync password cloud account customer.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan route:list --except-vendor` berhasil, 45 routes.
- `php artisan test` berhasil, 33 tests passed dengan 181 assertions.

## 2026-05-15 23:20 - Public Session Gallery by Session Code
Perubahan:
- Menambahkan public route `/{sessionCode}` untuk kode session station seperti `SES-NTRPXPBS`.
- Route dibatasi regex `SES-*` agar tidak mengganggu route admin/login/customer.
- Menambahkan `PublicSessionController`.
- Menambahkan halaman `resources/js/Pages/Public/SessionShow.vue`.
- Public gallery menampilkan asset uploaded tipe `framed` dan `original`.
- URL asset memakai `CloudAssetUrlService`.
- Menambahkan test `PublicSessionGalleryTest`.

Keputusan:
- Lookup session memakai `station_session_id` atau `metadata.station_session.session_code`.
- Link publik tidak membutuhkan login agar bisa dipakai sebagai URL customer langsung.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil, 46 routes.
- `php artisan test` berhasil, 34 tests passed dengan 197 assertions.

## 2026-05-11 - Dokumentasi Arsitektur Awal
Perubahan:
- Menyusun blueprint di `ARCHITECTURE.md`.
- Menyusun model data di `DATA_MODEL.md`.
- Menyusun kontrak API di `API_CONTRACT.md`.

Keputusan:
- Single database multi-tenant dengan `tenant_id`.
- Station API memakai token khusus station atau Sanctum.
- Customer login memakai WhatsApp dan password dari station.
- Cloud tidak mengontrol printer langsung; station polling print request.

## 2026-05-11 - Scaffold Laravel dan Inertia Vue
Perubahan:
- Membuat scaffold Laravel 13.8.0 di root project.
- Menginstal `inertiajs/inertia-laravel`.
- Menginstal `laravel/sanctum`.
- Menginstal `vue`, `@inertiajs/vue3`, dan `@vitejs/plugin-vue`.
- Menambahkan Inertia root view `resources/views/app.blade.php`.
- Menambahkan page awal `resources/js/Pages/Dashboard.vue`.
- Mengaktifkan route web `/` ke Inertia dashboard.

Verifikasi:
- `php artisan route:list --except-vendor` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil.

## 2026-05-11 - Domain Model dan API Awal
Perubahan:
- Menambahkan model domain:
  - `Tenant`
  - `Station`
  - `Customer`
  - `CustomerSubscription`
  - `CloudSession`
  - `CloudSessionAsset`
  - `CloudTemplate`
  - `CustomerTemplateEntitlement`
  - `CloudEditJob`
  - `CloudPrintRequest`
  - `ArchiveExport`
  - `StationSyncLog`
  - `Payment`
- Menambahkan migration domain utama:
  - `database/migrations/2026_05_11_000000_create_dafydio_cloud_domain_tables.php`
- Menambahkan route API:
  - Station heartbeat.
  - Station session sync.
  - Station asset registration.
  - Station asset upload complete.
  - Station session finalize.
  - Station print request polling/update.
  - Customer login.
  - Customer session list/detail.
  - Customer template list.
  - Customer print request create.

Verifikasi:
- Migration syntax berhasil via SQLite in-memory.
- `php artisan route:list --except-vendor` berhasil.
- `vendor/bin/pint --dirty` berhasil.
- `php artisan test` berhasil.

## 2026-05-11 - Database PostgreSQL Lokal Sempat Dibuat
Perubahan:
- Membuat database PostgreSQL lokal `dafydio_photobooth_cloud`.
- Menjalankan `php artisan migrate`.
- Menambahkan seed tenant dan station demo.

Seed:
- Tenant slug: `dafydio-demo`
- Station code: `STATION-001`
- Station token testing: `station-demo-token`

Catatan:
- Keputusan ini kemudian dibatalkan untuk deployment karena hosting tidak menyediakan PostgreSQL.

## 2026-05-11 - Adaptasi Hosting ke MySQL
Perubahan:
- Mengubah `.env` dan `.env.example` dari PostgreSQL ke MySQL.
- Mengubah queue/cache dari Redis ke database driver.
- Mengubah `AGENTS.md` dan `ARCHITECTURE.md` agar stack menjadi MySQL + Database Queue.
- Merapikan nama index migration agar aman untuk limit nama index MySQL.

Konfigurasi target:
- `DB_CONNECTION=mysql`
- `DB_PORT=3306`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`
- `FILESYSTEM_DISK=s3`

Verifikasi:
- `php artisan config:clear` berhasil.
- `php artisan route:list --except-vendor` berhasil.
- Migration syntax berhasil via SQLite in-memory.
- `php artisan test` berhasil.
- `npm run build` berhasil.

Blocker:
- Selesai pada 2026-05-12: `php artisan migrate:fresh --seed` sudah dijalankan pada MySQL target.

## 2026-05-11 - Arahan UI Mobile-First
Perubahan:
- Menambahkan prinsip UI mobile-first di `AGENTS.md`.
- Mengubah dashboard awal `resources/js/Pages/Dashboard.vue` agar lebih ramah mobile.
- Dashboard awal sekarang memakai ringkasan portal, tombol aksi besar, status singkat, dan copy yang lebih dekat dengan alur customer.

Keputusan:
- Customer kemungkinan besar masuk dari WhatsApp lewat HP, jadi customer portal harus diprioritaskan untuk layar kecil.
- Halaman portal/tool tidak boleh terasa seperti landing page marketing.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-11 - Pembagian Arah UI Client dan Admin
Perubahan:
- Memperjelas aturan UI di `AGENTS.md`.
- Customer/client portal diprioritaskan mobile-first.
- Admin/tenant dashboard diprioritaskan menu lengkap dan efisiensi operasional.

Keputusan:
- Client membuka portal terutama dari WhatsApp/HP, jadi alur client harus ringkas dan nyaman disentuh.
- Admin membutuhkan kontrol lengkap, sehingga boleh memakai sidebar, tabel, filter, tab, bulk action, dan form yang lebih padat.
- Admin tetap responsif, tetapi kelengkapan menu lebih penting daripada kesederhanaan visual.

Verifikasi:
- Dokumentasi saja, tidak ada build/test yang diperlukan.

## 2026-05-11 - Admin Dashboard Shell
Perubahan:
- Menambahkan route web `/admin`.
- Menambahkan halaman Inertia `resources/js/Pages/Admin/Dashboard.vue`.
- Admin dashboard memakai sidebar/menu lengkap untuk modul operasional, customer, marketplace, dan bisnis.
- Menambahkan area metrik, queue/workflow, station health, dan prioritas berikutnya.

Keputusan:
- Admin UI dibuat lebih padat dan lengkap dibanding client portal.
- Admin tetap responsif: sidebar menjadi grid menu saat layar kecil.
- Belum ada auth/admin middleware karena tahap ini masih shell UI dan navigasi awal.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-12 - MySQL Migration dan Seed Selesai
Perubahan:
- User menjalankan `php artisan migrate:fresh --seed`.
- Semua migration utama sudah berstatus `Ran`.
- Seed awal sudah dipakai untuk membuat tenant/station demo sesuai `DatabaseSeeder`.

Verifikasi:
- `php artisan migrate:status` berhasil.
- Migration berikut sudah `Ran`:
  - `0001_01_01_000000_create_users_table`
  - `0001_01_01_000001_create_cache_table`
  - `0001_01_01_000002_create_jobs_table`
  - `2026_05_11_000000_create_dafydio_cloud_domain_tables`
  - `2026_05_11_121333_create_personal_access_tokens_table`

Catatan:
- Cek count via `php artisan tinker` tidak dipakai karena environment menolak penulisan history PsySH.

## 2026-05-12 - Admin Auth dan Station Token Management
Perubahan:
- Menambahkan migration `2026_05_12_000000_add_tenant_role_status_to_users_table.php`.
- Menambahkan `tenant_id`, `role`, dan `status` ke admin user.
- Menambahkan admin default lewat `DatabaseSeeder`.
- Menambahkan admin auth controller:
  - `GET /admin/login`
  - `POST /admin/login`
  - `POST /admin/logout`
- Memproteksi route admin dengan middleware `auth`.
- Mengatur guest auth redirect ke `/admin/login`.
- Menambahkan dashboard admin berbasis data metric dari database.
- Menambahkan station token management:
  - `GET /admin/stations`
  - `POST /admin/stations/{station}/token`
- Menambahkan halaman:
  - `resources/js/Pages/Admin/Login.vue`
  - `resources/js/Pages/Admin/Stations/Index.vue`
- Menambahkan test feature `tests/Feature/AdminAuthAndStationTokenTest.php`.

Seed admin:
- Email: `admin@dafydio.local`
- Password: `password`
- Role: `tenant_admin`

Keputusan:
- Token station hanya ditampilkan sekali setelah regenerate.
- Token disimpan sebagai hash di `stations.api_token_hash`.
- Station token management di-scope berdasarkan `tenant_id` admin yang login.

Verifikasi:
- `php artisan migrate` berhasil.
- `php artisan db:seed` berhasil.
- `vendor/bin/pint --dirty` berhasil.
- `php artisan test` berhasil, 5 tests passed.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-12 - Standarisasi Login Sanctum dan Inertia
Perubahan:
- Menambahkan aturan login/auth stack di `AGENTS.md`.
- Memastikan dependency standar login tersedia:
  - `laravel/sanctum`
  - `inertiajs/inertia-laravel`
  - `@inertiajs/vue3`
- Menambahkan auth provider/guard customer di `config/auth.php`.
- Menambahkan guard customer ke `config/sanctum.php`.
- Menambahkan endpoint logout customer:
  - `POST /api/customer/auth/logout`
- Endpoint customer yang butuh login tetap memakai middleware `auth:sanctum`.
- Menambahkan test feature `tests/Feature/CustomerSanctumAuthTest.php`.

Keputusan:
- Admin/tenant web login memakai Inertia Vue + Laravel session guard `web`.
- Customer API/mobile portal memakai WhatsApp + password dari station dan menerima Sanctum personal access token.
- Logout customer menghapus current Sanctum access token.
- Station token tetap terpisah dari customer Sanctum token.
- Admin session dan customer token tidak dicampur.

Verifikasi:
- `vendor/bin/pint --dirty` berhasil.
- `php artisan test` berhasil, 8 tests passed.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-12 - Customer Login Template
Perubahan:
- Menerapkan template login customer ke halaman Inertia Vue baru:
  - `resources/js/Pages/Customer/Login.vue`
- Menambahkan route:
  - `GET /customer/login`
- Tombol `Buka Portal` di dashboard client mengarah ke `/customer/login`.
- Form login customer submit ke `POST /api/customer/auth/login`.
- Sanctum token customer disimpan sementara di `localStorage` dengan key `dafydio_customer_token`.
- Data customer disimpan sementara di `localStorage` dengan key `dafydio_customer`.

Keputusan:
- Template diterapkan tanpa CDN Tailwind, Google Font, atau Material Symbols eksternal agar tetap masuk build Vite lokal.
- Efek blur/orb background dari template tidak dipakai karena aturan UI project menghindari dekorasi orb/bokeh.
- Tampilan tetap mempertahankan gaya utama template: mobile-first, logo kamera, card putih, warna primary biru, field WhatsApp/password, show password, dan helper text.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 8 tests passed.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-12 - Customer Dashboard Template
Perubahan:
- Menerapkan template dashboard customer ke halaman Inertia Vue baru:
  - `resources/js/Pages/Customer/Dashboard.vue`
- Menambahkan route:
  - `GET /customer/dashboard`
- Setelah login customer berhasil, redirect diarahkan ke `/customer/dashboard`.
- Dashboard mengambil session dari `GET /api/customer/sessions` memakai Sanctum bearer token dari `localStorage`.
- Jika token tidak ada atau invalid, customer diarahkan kembali ke `/customer/login`.
- Menambahkan bottom navigation mobile untuk Sessions, Gallery, Prints, dan Profile.

Keputusan:
- Template diterapkan tanpa CDN Tailwind, Google Font, Material Symbols, atau gambar eksternal agar tetap masuk build Vite lokal.
- Gambar session eksternal diganti dengan blok visual gradient lokal agar tidak bergantung pada remote asset.
- Dashboard tetap mobile-first, dengan top app bar fixed, session cards, action buttons besar, storage notice, dan bottom nav.
- Demo session hanya dipakai sebagai fallback tampilan jika API belum punya data session.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 8 tests passed.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-12 - Penyamaan Template Admin dan Customer
Perubahan:
- Menyeragamkan visual admin dengan template Dafydio customer.
- Mengubah `resources/js/Pages/Admin/Login.vue` agar memakai card, logo kamera, warna primary biru, surface terang, rounded-xl, dan tombol biru seperti customer login.
- Mengubah `resources/js/Pages/Admin/Dashboard.vue` agar memakai sidebar terang, card rounded-xl, badge biru, primary action biru, dan surface yang sama dengan customer dashboard.
- Mengubah `resources/js/Pages/Admin/Stations/Index.vue` agar memakai style Dafydio yang sama.

Alasan pemisahan route:
- `/customer` untuk customer portal dengan Sanctum token, WhatsApp login, session archive, download, edit, dan print request.
- `/admin` untuk tenant/admin dengan session auth, station management, template, billing, sync log, dan operasional SaaS.
- Route dipisah karena role, guard, security boundary, dan workflow berbeda, tetapi brand/template harus tetap seragam.

Keputusan:
- Customer tetap mobile-first.
- Admin tetap menu lengkap dan operasional, tetapi visual mengikuti design language Dafydio yang sama.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 8 tests passed.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-12 - Unified Login URL
Perubahan:
- Menambahkan halaman login tunggal:
  - `GET /login`
  - `resources/js/Pages/Auth/Login.vue`
- Halaman `/login` memiliki pilihan mode `Customer` dan `Admin`.
- Login customer tetap submit ke `POST /api/customer/auth/login` dan memakai Sanctum token.
- Login admin submit ke `POST /login/admin` dan memakai Laravel session guard `web`.
- Guest yang membuka `/admin` sekarang diarahkan ke `/login`.
- Route lama tidak menjadi layar login terpisah:
  - `/admin/login` redirect ke `/login?mode=admin`
  - `/customer/login` redirect ke `/login?mode=customer`
- Tombol portal dan redirect token invalid diarahkan ke `/login`.

Keputusan:
- URL login publik disatukan agar user tidak perlu mengingat dua alamat login.
- Fitur setelah login tetap dibedakan oleh role, guard, dan area:
  - Customer masuk ke `/customer/dashboard`.
  - Admin masuk ke `/admin`.
- Pemisahan `/admin` dan `/customer` tetap dipertahankan untuk security boundary, workflow, dan authorization.

Verifikasi:
- `vendor/bin/pint --dirty` berhasil.
- `php artisan test` berhasil, 10 tests passed.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-12 - Local AI Skill
Perubahan:
- Menambahkan skill lokal untuk konsistensi AI:
  - `.agents/skills/dafydio-cloud/SKILL.md`
  - `.agents/skills/dafydio-cloud/agents/openai.yaml`
- Menambahkan referensi skill lokal di `AGENTS.md`.

Isi skill:
- Batas cloud vs station.
- Stack Laravel/Inertia/Vue/MySQL/Sanctum.
- Auth rules untuk `/login`, customer token, admin session, dan station token.
- Route area `/customer` dan `/admin`.
- UI rules customer mobile-first dan admin menu lengkap dengan visual Dafydio seragam.
- Multi-tenancy `tenant_id`.
- Workflow verifikasi.
- Kewajiban update `PROGRESS.md`.

Keputusan:
- Skill dibuat di dalam repo agar AI berikutnya bisa membaca aturan lokal tanpa mengandalkan chat history.
- Detail panjang tetap berada di dokumen root (`AGENTS.md`, `ARCHITECTURE.md`, `DATA_MODEL.md`, `API_CONTRACT.md`, `PROGRESS.md`).

Verifikasi:
- Dokumentasi saja, tidak ada build/test yang diperlukan.

## 2026-05-12 - Penyesuaian Struktur Skills Lokal
Perubahan:
- Menyesuaikan struktur `.agents/skills` yang sudah dirapikan.
- Memperbaiki `.agents/skills/laravel-best-practices/SKILL.md` agar menjadi skill Laravel backend, bukan duplikasi `dafydio-cloud`.
- Menambahkan `.agents/skills/inertia-vue-development/SKILL.md`.
- Menambahkan `.agents/skills/tailwindcss-development/SKILL.md`.
- Menambahkan metadata `agents/openai.yaml` untuk:
  - `laravel-best-practices`
  - `inertia-vue-development`
  - `tailwindcss-development`
- Memperbarui `.agents/skills/dafydio-cloud/SKILL.md` agar menunjuk ke companion skills.
- Memperbarui `AGENTS.md` agar mencantumkan semua skill lokal.

Keputusan:
- `dafydio-cloud` menjadi skill utama domain/product.
- `laravel-best-practices` menjadi skill backend/framework.
- `inertia-vue-development` menjadi skill frontend page/state/auth flow.
- `tailwindcss-development` menjadi skill UI/responsive/visual language.

Verifikasi:
- Dokumentasi/skill saja, tidak ada build/test yang diperlukan.

## 2026-05-12 - Customer Dashboard Desktop Layout
Perubahan:
- Mengganti layout `resources/js/Pages/Customer/Dashboard.vue` dengan template customer dashboard terbaru.
- Desktop sekarang memakai:
  - top app bar dengan nav Sessions/Gallery/Prints/Profile
  - grid utama 12 kolom
  - recent sessions grid
  - sidebar Storage Status, Archive Warning, dan Quick Links
- Mobile tetap mempertahankan bottom navigation.
- Membersihkan simbol/ikon yang sebelumnya terbaca tidak stabil dari encoding dengan label/simbol ASCII sederhana.

Keputusan:
- Tidak memakai CDN Tailwind, Google Font, Material Symbols, atau gambar eksternal.
- Gambar template tetap diganti dengan gradient lokal sampai asset asli dari station tersedia.
- Layout tetap customer-first, tetapi desktop kini lebih kaya sesuai template.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 10 tests passed.
- `php artisan route:list --except-vendor` berhasil.

## Next Steps
- Tambahkan service storage signed URL S3/R2.
- Tambahkan test feature untuk Station API dan Customer API.

## 2026-05-12 - Finalisasi Struktur Skills Lokal
Perubahan:
- Menyesuaikan struktur `.agents/skills` dengan folder yang sudah dirapikan:
  - `.agents/skills/dafydio-cloud`
  - `.agents/skills/laravel-best-practices`
  - `.agents/skills/inertia-vue-development`
  - `.agents/skills/tailwindcss-development`
- Memastikan masing-masing folder memiliki `SKILL.md`.
- Memastikan skill pendamping memiliki `agents/openai.yaml`.
- Memperjelas `AGENTS.md` dengan urutan pemakaian skill:
  - `dafydio-cloud` sebagai konteks utama domain/project.
  - `laravel-best-practices` untuk backend Laravel.
  - `inertia-vue-development` untuk Inertia/Vue.
  - `tailwindcss-development` untuk UI Tailwind dan responsive layout.

Keputusan:
- Skill dibuat berlapis agar AI tidak mencampur aturan domain Dafydio dengan aturan teknis framework.
- `dafydio-cloud` tetap menjadi skill utama setiap pekerjaan di repo ini.
- Skill Laravel, Inertia Vue, dan Tailwind dipakai hanya saat layer tersebut disentuh.

Verifikasi:
- Struktur `.agents/skills` berhasil dibaca.
- Dokumentasi/skill saja, tidak ada build/test yang diperlukan.

## 2026-05-12 - Customer Asset Download URL dan Catatan Deploy Hostinger
Perubahan:
- Menambahkan `app/Services/Storage/CloudAssetUrlService.php`.
- Menambahkan endpoint customer:
  - `POST /api/customer/assets/{cloud_asset_id}/download-url`
- Menambahkan `app/Http/Controllers/Api/Customer/AssetDownloadController.php`.
- Menambahkan relasi `CloudSessionAsset::session()`.
- Mengubah station asset registration agar memakai `CloudAssetUrlService::uploadUrl()`.
- Menambahkan test `tests/Feature/CustomerAssetDownloadUrlTest.php`.
- Menambahkan `HOSTINGER_DEPLOYMENT.md` untuk panduan deploy shared hosting Hostinger.
- Mengubah `.env.example` agar default deploy awal memakai `FILESYSTEM_DISK=public`, dengan catatan bisa diganti ke `s3` untuk R2/S3.

Keputusan:
- Download asset customer hanya boleh untuk asset tenant/customer sendiri.
- Asset harus berstatus `uploaded` sebelum URL download diberikan.
- Disk `public` dipakai sebagai fallback shared-hosting friendly.
- Untuk signed URL yang benar-benar temporary dan privat, gunakan S3/R2.
- Hostinger shared hosting memungkinkan untuk deploy awal jika PHP minimal 8.3 tersedia, tetapi queue worker permanen tidak diasumsikan tersedia.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test` berhasil, 13 tests passed.
- `php artisan route:list --except-vendor` berhasil, 26 routes.

## 2026-05-12 - Shared Hosting Queue Scheduler dan Station API Coverage
Perubahan:
- Menambahkan scheduler di `routes/console.php`:
  - `queue:work --stop-when-empty --tries=3 --timeout=60` setiap menit.
- Menambahkan relasi `CloudPrintRequest::asset()` dan `CloudPrintRequest::session()`.
- Mengubah `Api\Station\PrintRequestController@index` agar `asset_download_url` diisi dari `CloudAssetUrlService`.
- Menambahkan test `tests/Feature/StationApiTest.php` untuk:
  - validasi station token
  - heartbeat station
  - sync session dari station
  - register asset
  - complete asset upload
  - finalize session
  - polling print request dengan `asset_download_url`
  - update status print request
- Menambahkan `league/flysystem-aws-s3-v3` ke `composer.json` agar S3/R2 storage siap dipakai.
- Menambahkan `FILESYSTEM_DISK=public` di `phpunit.xml` agar test tidak tergantung konfigurasi `.env` lokal.
- Memperbarui `CloudAssetUrlService` agar aman jika adapter disk belum tersedia atau belum terkonfigurasi.
- Memperbarui `HOSTINGER_DEPLOYMENT.md` dengan status scheduler dan S3/R2 adapter.

Keputusan:
- Shared hosting memproses queue lewat Laravel scheduler + cron, bukan worker permanen.
- Station tetap hanya polling print request dan meng-update status; cloud hanya menyediakan koordinasi dan URL asset.
- Deploy awal bisa memakai `public` disk, sedangkan R2/S3 siap jika credential sudah ada.

Verifikasi:
- `composer update league/flysystem-aws-s3-v3 --with-dependencies` berhasil dengan akses network disetujui.
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test` berhasil, 18 tests passed.
- `php artisan route:list --except-vendor` berhasil, 26 routes.
- `php artisan schedule:list` berhasil dan menampilkan queue worker setiap menit.
- `composer show league/flysystem-aws-s3-v3` berhasil, versi 3.32.0.

## 2026-05-12 - Admin Operational Dashboard
Perubahan:
- Memperluas `app/Http/Controllers/Admin/DashboardController.php` agar mengirim data operasional tenant:
  - metric station, session, asset, print request, customer, premium customer, template, revenue, dan sync failure.
  - recent stations.
  - recent sessions.
  - recent print requests.
  - sync logs.
  - storage readiness.
  - deployment readiness.
- Menambahkan relasi model:
  - `CloudSession::customer()`
  - `CloudSession::station()`
  - `CloudPrintRequest::customer()`
  - `CloudPrintRequest::station()`
  - `StationSyncLog::station()`
- Mengganti `resources/js/Pages/Admin/Dashboard.vue` menjadi dashboard operasional admin yang menu-rich dan seragam dengan visual Dafydio.
- Menambahkan test Inertia untuk memastikan dashboard admin mengirim props operasional.

Keputusan:
- Admin dashboard tetap memakai visual Dafydio yang sama dengan customer, tetapi lebih padat dan operasional.
- Menu admin menampilkan akses ke station, session, asset, print request, customer, subscription, archive, template, entitlement, edit job, billing, payment, dan settings.
- Beberapa menu masih berupa anchor/placeholder sampai halaman CRUD detail dibuat.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test` berhasil, 19 tests passed.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil, 26 routes.

## 2026-05-12 - Penyesuaian Admin Dashboard Berdasarkan Template Desktop/Mobile
Perubahan:
- Mengganti `resources/js/Pages/Admin/Dashboard.vue` agar mengikuti template HTML admin desktop dan mobile dari user.
- Desktop sekarang memakai:
  - sidebar fixed 260px.
  - header Dashboard Overview dengan aksi Export Report dan New Station.
  - empat summary card: Customers, Active Stations, Pending Prints, Storage Used.
  - tabel Recent Print Requests.
  - panel Station Sync Health.
  - panel System Logs bergaya terminal.
- Mobile sekarang memakai:
  - top app bar.
  - grid metric 2x2.
  - Station Sync Health list.
  - Recent Print Requests list.
  - bottom navigation.
  - FAB menuju station management.

Keputusan:
- Dashboard disesuaikan untuk role tenant admin/studio owner, bukan super admin platform.
- Menu `Tenants`, `New Tenant`, dan `Total Tenants` dari template diganti menjadi konteks tenant admin:
  - Business
  - New Station
  - Customers
- Tetap tidak memakai CDN Tailwind, Google Fonts remote, Material Symbols remote, atau gambar eksternal.
- Ikon template diganti dengan label pendek lokal agar build tetap mandiri untuk deploy hosting.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 19 tests passed.

## 2026-05-15 23:23 - Tombol Public Gallery di Detail Customer
Perubahan:
- Menambahkan URL public gallery per session di `app/Http/Controllers/Admin/CustomerController.php`.
- Menambahkan tombol `Public Gallery` pada daftar session customer di `resources/js/Pages/Admin/Customers/Show.vue`.
- Menyesuaikan test customer detail agar memvalidasi URL public gallery.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `npm run build` berhasil.
- `php artisan test` berhasil, 34 tests passed dengan 199 assertions.

## 2026-05-14 22:30 - Verifikasi Test Suite
Perubahan:
- Tidak ada perubahan kode.
- Menjalankan verifikasi test suite Laravel atas permintaan.

Verifikasi:
- `php artisan test` berhasil, 19 tests passed dengan 79 assertions.

## 2026-05-12 - Demo Customer Seeder
Perubahan:
- Menambahkan data demo customer di `database/seeders/DatabaseSeeder.php`.
- Menambahkan data demo pendukung agar dashboard tidak kosong:
  - customer demo
  - subscription regular
  - demo session
  - original asset
  - framed asset
  - pending print request
  - station sync log

Credential demo:
- Customer WhatsApp: `+628111111111`
- Customer password: `password`
- Tenant slug: `dafydio-demo`
- Admin email: `admin@dafydio.local`
- Admin password: `password`
- Station token: `station-demo-token`

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test` berhasil, 19 tests passed.
- `php artisan db:seed --force` berhasil.

## 2026-05-12 - Customer Dashboard Terhubung ke API Real
Perubahan:
- Mengganti `resources/js/Pages/Customer/Dashboard.vue` agar tidak lagi memakai fallback demo session statis.
- Dashboard customer sekarang:
  - membaca `dafydio_customer_token` dari `localStorage`
  - mengambil session dari `GET /api/customer/sessions`
  - redirect ke `/login?mode=customer` jika token hilang/invalid
  - menampilkan empty state jika belum ada session
  - menghitung status asset dari data real
  - membuat download URL lewat `POST /api/customer/assets/{cloud_asset_id}/download-url`
  - membuat print request lewat `POST /api/customer/print-requests`
  - logout lewat `POST /api/customer/auth/logout`

Keputusan:
- Customer dashboard memakai API sebagai sumber data utama.
- Tombol Save memakai asset uploaded pertama dari session.
- Tombol Print juga memakai asset uploaded pertama dari session dengan quantity default `1`.
- Detail session/gallery penuh masih menjadi tahap berikutnya.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 19 tests passed.
- `vendor\bin\pint --dirty` berhasil.

## 2026-05-19 - App Icon Branding
Perubahan:
- Menambahkan ikon aplikasi ke `public/images/dafydio-booth-icon.png`.
- Menambahkan favicon dan apple touch icon di `resources/views/app.blade.php`.
- Mengganti placeholder identitas `DF` dan ikon rusak di halaman login/admin/customer dengan ikon aplikasi lokal.
- Menambahkan ikon aplikasi pada brand header/sidebar halaman admin, customer, public session, dan halaman root dashboard.

Keputusan:
- Ikon disimpan sebagai public asset agar mudah dipakai di shared hosting tanpa pipeline asset tambahan.
- Di Vue template, path gambar memakai binding `:src="'/images/dafydio-booth-icon.png'"` agar Vite tidak mencoba resolve gambar sebagai module import.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 44 tests passed.
- `vendor\bin\pint --dirty` berhasil.
- Pencarian placeholder `DF`, karakter ikon rusak, dan `src="/images/dafydio...` tidak menemukan sisa di `resources/js/Pages`.

## 2026-05-19 - Normalisasi Login WhatsApp Customer
Perubahan:
- Menambahkan helper `app/Support/WhatsAppNumber.php`.
- Customer login sekarang mencari nomor dengan variasi input `+62...`, `62...`, dan `08...`.
- Mengubah `app/Http/Controllers/Api/Customer/AuthController.php` agar memakai `WhatsAppNumber::lookupVariants()`.
- Menambahkan test customer login untuk format `08111111111` dan `628111111111`.

Keputusan:
- Data lama tidak dipaksa dimigrasi; login melakukan lookup varian agar kompatibel dengan nomor yang sudah tersimpan sebagai `+62...` atau `62...`.
- `uniqueStrict()` dipakai agar varian `+628...` tidak hilang karena perbandingan longgar.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test --filter=CustomerSanctumAuthTest` berhasil, 5 tests passed.
- `php artisan test` berhasil, 46 tests passed.
- `npm run build` berhasil.

## 2026-05-19 - Filter Admin Customers
Perubahan:
- Menambahkan pencarian di `/admin/customers` untuk WhatsApp, nama customer, dan plan subscription.
- Menambahkan filter dropdown plan `all`, `regular`, dan `premium`.
- Pagination daftar customer mempertahankan query filter dengan `withQueryString()`.
- Menambahkan form filter pada `resources/js/Pages/Admin/Customers/Index.vue`.
- Menambahkan test `test_tenant_admin_can_search_and_filter_customers`.

Keputusan:
- Query bebas `q` mencari ke `customers.name`, `customers.whatsapp_number`, dan `customer_subscriptions.plan`.
- Filter plan memakai `whereHas('subscriptions')` agar hasil admin fokus ke customer yang punya subscription sesuai plan.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test --filter=AdminAuthAndStationTokenTest` berhasil, 17 tests passed.
- `php artisan test` berhasil, 47 tests passed.
- `npm run build` berhasil.

## 2026-05-19 - Edit Nama Customer dari Admin Customers
Perubahan:
- Menambahkan route `PATCH /admin/customers/{customer}` untuk update nama customer.
- Menambahkan method `update` di `app/Http/Controllers/Admin/CustomerController.php`.
- Menambahkan tombol `Edit Nama` dan form inline pada tabel `resources/js/Pages/Admin/Customers/Index.vue`.
- Menambahkan test admin bisa mengganti nama customer dan tidak bisa mengubah customer tenant lain.

Keputusan:
- Edit nama dilakukan inline dari daftar customer agar admin tidak perlu masuk halaman detail untuk koreksi cepat.
- Nama boleh dikosongkan; nilai kosong disimpan sebagai `null` sehingga fallback UI tetap menampilkan `Customer`.
- Update tetap tenant-scoped dengan `abort_unless($customer->tenant_id === $request->user()->tenant_id, 404)`.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan route:list --except-vendor` berhasil dan route update customer muncul sebelum route detail.
- `php artisan test --filter=AdminAuthAndStationTokenTest` berhasil, 19 tests passed.
- `php artisan test` berhasil, 49 tests passed.
- `npm run build` berhasil.

## 2026-05-19 - Customer Isi Nama Sendiri
Perubahan:
- Menambahkan endpoint `PATCH /api/customer/profile` dengan Sanctum `auth:sanctum`.
- Menambahkan controller `app/Http/Controllers/Api/Customer/ProfileController.php`.
- Menambahkan form "Nama kamu" di `resources/js/Pages/Customer/Dashboard.vue`.
- Customer dashboard otomatis meminta nama jika data customer di localStorage belum punya nama.
- Menambahkan tombol `Edit Nama` untuk customer yang sudah punya nama.
- Menambahkan test customer bisa update nama sendiri dan nama wajib diisi.

Keputusan:
- Customer hanya diminta satu data: `name`.
- Endpoint memakai customer dari token Sanctum, jadi customer tidak bisa mengubah data customer lain.
- Setelah update berhasil, data customer di localStorage ikut diperbarui agar sapaan dashboard langsung berubah.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test --filter=CustomerSanctumAuthTest` berhasil, 7 tests passed.
- `php artisan route:list --except-vendor` berhasil dan route `api/customer/profile` muncul.
- `npm run build` berhasil.
- `php artisan test` berhasil, 51 tests passed.

## 2026-05-19 - Optimasi Tabel Admin dengan Pagination dan Index
Perubahan:
- Menambahkan komponen reusable `resources/js/Components/AdminPagination.vue`.
- Menambahkan kontrol pagination ke tabel admin:
  - `Admin/Customers/Index.vue`
  - `Admin/Customers/Show.vue`
  - `Admin/Sessions/Index.vue`
  - `Admin/Payments/Index.vue`
  - `Admin/SyncLogs/Index.vue`
  - `Admin/Templates/Index.vue`
  - `Admin/Stations/Index.vue`
- Mengubah `app/Http/Controllers/Admin/StationController.php` dari `get()` menjadi `paginate(20)`.
- Menambahkan migration `2026_05_19_000001_add_admin_list_performance_indexes.php` untuk index daftar admin.

Keputusan:
- Semua daftar admin memakai pagination 20 data per halaman agar render dan query tetap ringan saat data ribuan.
- Index ditambahkan pada pola query umum: `tenant_id`, `created_at`, `status`, `topic`, `plan`, `customer_id`, dan `station_id`.
- Pencarian `LIKE` tetap tersedia, tetapi untuk data sangat besar nanti perlu dipertimbangkan full-text/search khusus.

Catatan deploy:
- Jalankan `php artisan migrate` agar index baru dibuat di database MySQL/hosting.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test --filter=AdminAuthAndStationTokenTest` berhasil, 19 tests passed.
- `php artisan test` berhasil, 51 tests passed.
- `npm run build` berhasil.

## 2026-05-19 - Audit dan Optimasi Query/Auth
Perubahan:
- Menambahkan helper `app/Support/StationToken.php` untuk lookup hash token station.
- Menambahkan migration `2026_05_19_000002_add_station_token_lookup.php`.
- Menambahkan kolom `stations.api_token_lookup` agar autentikasi station tidak perlu scan semua station aktif.
- Mengubah `app/Http/Middleware/AuthenticateStation.php` agar lookup station token memakai index `api_token_lookup`.
- Menambahkan fallback sekali untuk token station lama yang belum punya lookup hash; setelah cocok, lookup hash disimpan.
- Mengubah pembuatan token station di controller admin web dan API agar selalu mengisi `api_token_hash` dan `api_token_lookup`.
- Mengubah seeder demo station agar mengisi `api_token_lookup`.
- Mengurangi eager-load dashboard admin dengan memilih kolom relasi yang diperlukan saja.
- Mengubah urutan API customer sessions dari `orderByRaw(COALESCE(...))` menjadi `latest()` agar lebih ramah index `created_at`.
- Menambahkan index tambahan `sessions_tenant_customer_created_idx`.
- Memperbarui `DATA_MODEL.md` dan `ARCHITECTURE.md` untuk mendokumentasikan token lookup.

Keputusan:
- `api_token_hash` tetap disimpan untuk keamanan verifikasi token rahasia.
- `api_token_lookup` memakai SHA-256 deterministik untuk lookup cepat; token asli tetap tidak disimpan.
- Fallback legacy dipertahankan supaya station yang tokennya sudah ada sebelum migration tetap bisa autentikasi, lalu otomatis dimigrasikan saat dipakai.
- Customer session portal mengutamakan data terbaru berdasarkan `created_at`; ini lebih stabil untuk session yang belum punya `started_at`.

Risiko lanjutan:
- Sync Logs masih mengirim payload/response penuh per baris. Untuk log sangat besar, perlu mode detail terpisah atau preview payload agar daftar log makin ringan.
- Search `LIKE` pada JSON/payload masih cukup untuk fase awal, tetapi untuk data besar perlu kolom search ringkas atau full-text/search service.

Catatan deploy:
- Jalankan `php artisan migrate` agar kolom token lookup dan index baru dibuat.
- Token station lama akan mengisi `api_token_lookup` otomatis saat station pertama kali sukses memakai token lama.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test --filter=StationApiTest` berhasil, 12 tests passed.
- `php artisan test --filter=CustomerSanctumAuthTest` berhasil, 7 tests passed.
- `php artisan test --filter=DocumentedApiContractTest` berhasil, 7 tests passed.
- `php artisan test --filter=AdminAuthAndStationTokenTest` berhasil, 19 tests passed.
- `php artisan test` berhasil, 51 tests passed.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor` berhasil.

## 2026-05-19 - Upload Repository ke GitHub
Perubahan:
- Membuat commit awal `Initial Dafydio photobooth cloud app`.
- Menambahkan remote `origin` ke `git@github.com:errymaricha/errymaricha-dafydio_photobooth_cloud.git`.
- Push branch `main` ke GitHub.

Keputusan:
- `.env`, database lokal, `vendor`, `node_modules`, build output, cache, dan file upload storage tetap tidak ikut commit karena sudah di `.gitignore`.

Verifikasi:
- `git status --short --branch` bersih sebelum push awal.
- `git push -u origin main` berhasil dan branch `main` tracking `origin/main`.

## 2026-05-19 - Rename Remote Repository GitHub
Perubahan:
- Mengubah remote `origin` dari `git@github.com:errymaricha/errymaricha-dafydio_photobooth_cloud.git` ke `git@github.com:errymaricha/dafydio_photobooth_cloud.git`.

Keputusan:
- Nama repository GitHub disederhanakan menjadi `errymaricha/dafydio_photobooth_cloud`.
- Perubahan URL remote tersimpan di konfigurasi Git lokal, sedangkan catatan proses disimpan di `PROGRESS.md`.

Verifikasi:
- `git remote -v` berhasil menampilkan URL remote baru.
- `git ls-remote --heads origin main` berhasil dan menemukan branch `main` pada commit `c45fbfce5e48ee69eed1e80e8f5fa2dcb173e3d0`.

## 2026-05-19 - README Project Flow
Perubahan:
- Mengganti `README.md` bawaan Laravel menjadi README khusus Dafydio Photobooth Cloud.
- Menambahkan alur cloud/station/customer/admin sesuai catatan arsitektur dan progress.
- Menambahkan stack, credential demo, setup lokal, port cloud `8001`, verifikasi, deployment shared hosting, optimasi data, dan link dokumentasi project.

Keputusan:
- README dibuat sebagai pintu masuk praktis untuk developer/operator, sedangkan detail teknis tetap diarahkan ke `ARCHITECTURE.md`, `DATA_MODEL.md`, `API_CONTRACT.md`, `HOSTINGER_DEPLOYMENT.md`, dan `PROGRESS.md`.
- Bagian deploy menekankan MySQL, database queue/cache, storage public awal, dan struktur siap S3/R2.

Verifikasi:
- Perubahan dokumentasi saja; tidak menjalankan test aplikasi.

## 2026-05-20 - Fix Local Station Sync Migration
Perubahan operasional:
- Menjalankan migration lokal yang masih pending:
  - `2026_05_19_000001_add_admin_list_performance_indexes`
  - `2026_05_19_000002_add_station_token_lookup`
- Migration menambahkan kolom `stations.api_token_lookup` yang dibutuhkan middleware station token lookup.

Penyebab:
- Station gagal upload session `SES-1XCDSRVZ` karena cloud membalas HTTP 500.
- Error database: `Unknown column 'api_token_lookup' in 'where clause'`.
- Kode aplikasi sudah benar, tetapi database lokal belum menjalankan migration terbaru.

Keputusan:
- Tidak perlu perubahan kode.
- Station dapat menjalankan ulang `cloud:sync-pending` setelah migration cloud selesai.
- Jika setelah ini muncul HTTP 401, berarti token station yang dipakai tidak cocok dengan token station di database cloud.

Verifikasi:
- `php artisan migrate` berhasil menjalankan dua migration pending.
- `php artisan migrate:status` menampilkan semua migration sudah `Ran`.
- Cek kolom `stations` berhasil menemukan `api_token_lookup`.
- `php artisan test --filter=StationApiTest` berhasil, 12 tests passed.
- Request heartbeat lokal tidak lagi error 500; respons 401 karena token demo tidak cocok dengan data station lokal aktif.

## 2026-05-20 - Set Local Station Cloud Token
Perubahan operasional:
- Mengupdate station `STATION-001` di database lokal cloud agar cocok dengan token station yang dipakai aplikasi station.
- Token asli tidak disimpan di file/repo; database hanya menyimpan `api_token_hash` dan `api_token_lookup`.

Keputusan:
- Perubahan dilakukan langsung pada record station lokal karena ini sinkronisasi credential operasional, bukan perubahan kode.
- Station `STATION-001` dipastikan berstatus `active`.

Verifikasi:
- Record station `STATION-001` memiliki `api_token_hash` dan `api_token_lookup`.
- `POST http://localhost:8001/api/station/heartbeat` dengan token station berhasil dan mengembalikan `message: OK`.
- Session `SES-1XCDSRVZ` masih belum ada di cloud setelah token diperbaiki; station perlu menjalankan retry `cloud:sync-pending`.

## 2026-05-20 - Optimasi Detail Sync Logs
Perubahan:
- Mengubah daftar `/admin/sync-logs` agar hanya mengambil kolom ringkas dan tidak lagi membawa `payload`/`response` penuh per baris.
- Menambahkan route detail `GET /admin/sync-logs/{syncLog}`.
- Menambahkan halaman `resources/js/Pages/Admin/SyncLogs/Show.vue` untuk melihat payload dan response penuh.
- Mengubah UI daftar Sync Logs agar memakai tombol `Detail`, bukan expandable payload inline.
- Menambahkan test detail sync log dan tenant-scope protection.

Keputusan:
- Payload/response sync log tetap bisa dicari lewat filter awal, tetapi tidak dikirim ke browser pada daftar tabel.
- Payload/response penuh hanya dibuka saat admin masuk ke detail log tertentu.
- Fitur S3/R2 tetap diskip sesuai keputusan saat ini.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan route:list --except-vendor --path=admin/sync-logs` berhasil dan menampilkan route index/detail.
- `php artisan test --filter=AdminAuthAndStationTokenTest` berhasil, 21 tests passed.
- `npm run build` berhasil.
- `php artisan test` berhasil, 53 tests passed dengan 460 assertions.

## 2026-05-20 - Cloud Print Request Claim Flow untuk Station
Perubahan:
- Mematangkan endpoint polling station `GET /api/station/print-requests?status=pending&limit=25`.
- Response polling sekarang berbentuk `data.print_requests` dan menyertakan `station_session_id`, `session_code`, `copies`, `paper_size`, `priority`, dan `payment_status`.
- Polling hanya mengembalikan request milik tenant/station dari token, sudah siap diproses, payment `paid`/`not_required`, dan belum punya `claimed_at`.
- Query `status=pending` menjadi alias siap proses untuk status lama `pending` dan status baru `pending_operator`.
- Mematangkan `PATCH /api/station/print-requests/{id}` untuk status `claimed`, `printing`, `printed`, dan `failed`.
- Menambahkan migration `2026_05_20_000001_add_station_print_claim_fields.php`.
- Menambahkan field penyimpanan claim/print:
  - `station_local_id`
  - `station_print_order_id`
  - `station_print_queue_job_id`
  - `claimed_at`
  - `failed_at`
  - `last_error`
- Menambahkan status transition guard:
  - `pending`/`pending_operator -> claimed|failed`
  - `claimed -> claimed|printing|failed`
  - `printing -> printing|printed|failed`
  - `printed -> printed`
  - `failed -> failed`
- Claim dengan order/job sama dibuat idempotent; claim ulang dengan order/job berbeda ditolak `409`.
- Admin approve payment `template_print_request` sekarang dapat mengubah print request `pending_payment` menjadi `pending_operator`.
- Customer print request tanpa payment sekarang langsung dibuat sebagai `pending_operator`.
- Memperbarui `API_CONTRACT.md` dan `DATA_MODEL.md`.

Keputusan:
- `station_id` dari payload station disimpan sebagai `station_local_id` agar tidak bentrok dengan kolom `station_id` cloud.
- Cloud tetap tidak mengontrol printer; cloud hanya mengoordinasikan antrian, claim, dan status.
- Fitur Storage S3/R2 tetap diskip.

Catatan deploy:
- Jalankan `php artisan migrate` di environment cloud agar field claim print request tersedia.

Verifikasi:
- `php artisan migrate` berhasil menjalankan migration claim print request lokal.
- `vendor\bin\pint --dirty` berhasil.
- `php artisan route:list --except-vendor --path=api/station/print-requests` berhasil.
- `php artisan test --filter=StationApiTest` berhasil, 15 tests passed.
- `php artisan test --filter=AdminAuthAndStationTokenTest` berhasil, 21 tests passed.
- `npm run build` berhasil.
- `php artisan test` berhasil, 56 tests passed dengan 482 assertions.
- `php artisan migrate:status` menampilkan `2026_05_20_000001_add_station_print_claim_fields` sudah `Ran`.

## 2026-05-21 - OG Meta Public Gallery untuk Share WhatsApp
Perubahan:
- Menambahkan metadata Open Graph/Twitter Card pada halaman public gallery `/{sessionCode}`.
- `PublicSessionController` sekarang mengirim `session.cover_image_url` dan `session.og`.
- Cover preview memakai asset `framed` pertama yang uploaded, fallback ke asset pertama, lalu fallback ke ikon aplikasi.
- `resources/js/Pages/Public/SessionShow.vue` memakai Inertia `Head` untuk mengisi:
  - `og:title`
  - `og:description`
  - `og:image`
  - `og:url`
  - `og:type`
  - Twitter card metadata.

Keputusan:
- URL yang dishare tetap URL gallery pendek seperti `/SES-LM7CMO5G`, bukan URL file storage panjang.
- Thumbnail WhatsApp tetap membutuhkan domain publik yang bisa diakses WhatsApp; `localhost` tidak bisa dipakai untuk preview WhatsApp real.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed.
- `npm run build` berhasil.
- `php artisan test` berhasil, 56 tests passed dengan 492 assertions.

## 2026-05-21 - Dynamic SEO Meta Public Gallery
Perubahan:
- Mengubah title public gallery agar unik per session dengan format `{session_title} - {session_code} | Dafydio Photobooth`.
- Mengubah description agar menyebut `session_code`.
- Menambahkan canonical URL di Inertia `Head`.
- Memperbarui test public gallery untuk memastikan OG title, description, image, URL, dan canonical bersifat dinamis.

Keputusan:
- Meta tidak lagi statis untuk semua gallery agar lebih baik untuk SEO/social preview dan mengurangi risiko duplikat title/description.
- URL canonical tetap memakai public gallery URL pendek, bukan URL file storage.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test --filter=PublicSessionGalleryTest` berhasil, 1 test passed.
- `npm run build` berhasil.
- `php artisan test` berhasil, 56 tests passed dengan 494 assertions.

## 2026-05-21 - Production Hardening Tahap Awal
Perubahan:
- Menambahkan `.env.production.example` sebagai template konfigurasi production.
- Menambahkan rate limiter di `AppServiceProvider`:
  - `admin-login`: 5 request/menit.
  - `customer-login`: 5 request/menit per IP + tenant + WhatsApp.
  - `customer-api`: 90 request/menit per customer/IP.
  - `station-api`: 120 request/menit per station token/IP.
  - `station-upload`: 60 request/menit per station token/IP.
  - `webhooks`: 30 request/menit per IP.
- Menerapkan middleware throttle ke:
  - admin login web.
  - customer login API.
  - customer API Sanctum.
  - station API.
  - station upload endpoint.
  - payment webhook placeholder.
- Menambahkan `meta robots noindex,nofollow` untuk public gallery agar gallery customer tidak diindeks search engine.
- Menambahkan test rate limit customer login.

Keputusan:
- Public gallery tetap shareable via WhatsApp, tetapi tidak diarahkan untuk SEO indexing publik.
- `.env.production.example` mengaktifkan `APP_DEBUG=false`, HTTPS URL, encrypted/secure session cookie, database queue/cache, dan public disk awal.
- Storage R2/S3 tetap opsional dan belum diaktifkan sebagai default.

Verifikasi:
- `vendor\bin\pint --dirty` berhasil.
- `php artisan test --filter=CustomerSanctumAuthTest` berhasil, 8 tests passed.
- `php artisan test --filter=StationApiTest` berhasil, 15 tests passed.
- `npm run build` berhasil.
- `php artisan route:list --except-vendor --path=api/customer` berhasil.
- `php artisan route:list --except-vendor --path=api/station` berhasil.
- `php artisan route:list --except-vendor --path=api/webhooks` berhasil.
- `php artisan test` berhasil, 57 tests passed dengan 500 assertions.

## 2026-05-22 15:16 - Pembaruan Halaman Index Root URL
Perubahan:
- Memperbarui `resources/js/Pages/Dashboard.vue` sebagai halaman index `/` untuk Dafydio Photobooth Cloud.
- Menambahkan akses cepat ke login customer dan admin melalui `/login?mode=customer` dan `/login?mode=admin`.
- Menambahkan form buka public gallery memakai kode session pendek seperti `SES-LM7CMO5G`.
- Menambahkan ringkasan area produk: Customer Portal, Admin Console, Public Gallery, dan Station API.
- Menambahkan status kesiapan cloud untuk database, auth, print flow, dan storage.
- Menambahkan title dan description meta melalui Inertia `Head`.

Keputusan:
- Root URL dibuat sebagai access hub operasional, bukan landing page marketing.
- URL gallery pendek tetap menjadi jalur utama untuk share WhatsApp.
- R2/S3 tetap disebut sebagai struktur yang disiapkan, belum diaktifkan sebagai default.

Verifikasi:
- `php artisan test` berhasil, 57 tests passed dengan 500 assertions.
- `npm run build` sempat gagal karena path icon absolut di template Vue dibaca Vite sebagai import.
- Path icon diperbaiki menjadi binding runtime `:src="'/images/dafydio-booth-icon.png'"`.
- `npm run build` berhasil setelah perbaikan.

## 2026-05-22 - Dekorasi Anime.js Halaman Index
Perubahan:
- Menambahkan dependency `animejs` untuk animasi dekoratif halaman root `/`.
- Memperbarui `resources/js/Pages/Dashboard.vue` dengan animasi ringan:
  - garis scan transparan di background.
  - node kecil bergerak halus.
  - elemen konten masuk bertahap saat halaman dimuat.
- Menambahkan guard `prefers-reduced-motion` agar animasi tidak berjalan untuk user yang memilih reduced motion.
- Membersihkan animasi saat komponen dilepas dengan `revert()`.

Keputusan:
- Animasi hanya dipasang di halaman index, bukan global layout.
- Dekorasi dibuat non-interaktif (`pointer-events-none`) dan tidak menutupi alur utama login/gallery.
- Tidak memakai bokeh/orb dekoratif; bentuk dekorasi memakai garis, kotak, dan node kecil agar tetap sesuai arah UI Dafydio.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 57 tests passed dengan 500 assertions.

## 2026-05-22 - SVG Animasi Alur Android Station Cloud
Perubahan:
- Memperbarui halaman index `resources/js/Pages/Dashboard.vue` dengan SVG inline untuk menjelaskan alur aplikasi photobooth.
- SVG menggambarkan:
  - Android sebagai perangkat capture foto di event.
  - Station sebagai pusat render lokal, session workflow, queue printer, dan print fisik.
  - Cloud sebagai archive, customer portal, admin console, dan sinkronisasi request.
  - Customer/Admin sebagai pihak yang mengakses gallery, billing, dan monitoring.
- Menambahkan animasi `anime.js` pada garis alur dan titik pulse di SVG.
- Mengganti kartu kanan menjadi keunggulan per komponen: Android, Station, dan Cloud.

Keputusan:
- SVG dibuat inline agar mudah dikontrol oleh Vue dan `anime.js` tanpa asset eksternal.
- Animasi tetap menghormati `prefers-reduced-motion` lewat guard yang sudah ada.
- Narasi alur tetap menjaga boundary produk: cloud tidak menangani capture atau cetak fisik; station tetap eksekutor lokal.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 57 tests passed dengan 500 assertions.

## 2026-05-22 - Revisi Diagram Alur Tanpa Payload JSON
Perubahan:
- Merevisi SVG alur di `resources/js/Pages/Dashboard.vue` agar lebih dekat dengan contoh diagram Dafydio Booth.
- Diagram kini menampilkan blok:
  - Android Device.
  - Photobooth Station API.
  - Station Database.
  - Cloud.
- Menambahkan panah berlabel untuk create event, update event, create session, save/update, sync session, upload asset, dan station polling print request.
- Menambahkan catatan source-of-truth bahwa event berada di Station DB.
- Menambahkan catatan bahwa cloud tidak membuat event.
- Tidak menampilkan panel payload JSON pada halaman index.

Keputusan:
- Diagram index dibuat sebagai penjelasan visual high-level untuk visitor/admin, bukan dokumentasi payload API.
- Informasi teknis payload tetap berada di dokumen kontrak API, bukan di halaman utama.

Verifikasi:
- `npm run build` berhasil.
- `php artisan test` berhasil, 57 tests passed dengan 500 assertions.
