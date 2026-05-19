# Dafydio Photobooth Cloud Architecture

Laravel 13 + Inertia Vue + MySQL + Database Queue + S3/R2 Storage
Single database multi-tenant dengan tenant_id
Station API pakai Sanctum/token khusus station
Customer auth pakai WhatsApp + Password dari station
Billing lokal pakai Midtrans/Xendit, global opsional Paddle/Stripe Cashier

## Login/Auth Stack
Gunakan paket berikut sebagai standar login:
- `laravel/sanctum`
- `inertiajs/inertia-laravel`
- `@inertiajs/vue3`

Pola auth:
- Admin/tenant web login memakai Inertia Vue + Laravel session guard `web`.
- Customer API/mobile portal memakai WhatsApp + password dari station dan mengeluarkan Sanctum personal access token.
- Route API customer yang butuh login memakai middleware `auth:sanctum`.
- Logout customer menghapus current Sanctum access token.
- Station API memakai token khusus station yang di-hash di database; ini terpisah dari customer token.
- Jangan mencampur admin session dengan customer token.

## Progress Documentation Rule
Setiap perubahan teknis, capaian, keputusan arsitektur, blocker, dan hasil verifikasi harus dicatat di `PROGRESS.md`.

Saat mengerjakan fitur:
- Tambahkan entry baru dengan tanggal/waktu singkat.
- Catat file utama yang berubah.
- Catat command verifikasi yang dijalankan dan hasilnya.
- Catat blocker atau pekerjaan lanjutan jika ada.
- Jangan hanya mengandalkan chat sebagai dokumentasi.

## Local AI Skill
Gunakan skill lokal di `.agents/skills` untuk menjaga konsistensi AI saat mengerjakan repo ini.

Skill utama:
- `.agents/skills/dafydio-cloud/SKILL.md`

Skill pendamping:
- `.agents/skills/laravel-best-practices/SKILL.md`
- `.agents/skills/inertia-vue-development/SKILL.md`
- `.agents/skills/tailwindcss-development/SKILL.md`

Urutan pemakaian:
- Mulai dari `dafydio-cloud` untuk memahami konteks produk, boundary cloud/station, auth, tenant, dan dokumentasi progres.
- Tambahkan `laravel-best-practices` saat mengubah backend Laravel, route, controller, model, migration, auth, queue, validation, atau test.
- Tambahkan `inertia-vue-development` saat mengubah halaman Inertia, Vue state, form, login flow, dashboard customer/admin, atau navigasi frontend.
- Tambahkan `tailwindcss-development` saat mengubah layout, responsive behavior, warna, spacing, card, tombol, atau konsistensi visual Dafydio.

Skill utama merangkum:
- batas cloud vs station
- stack dan auth
- route area `/login`, `/customer`, `/admin`
- aturan UI customer/admin
- multi-tenancy
- workflow verifikasi
- kewajiban update `PROGRESS.md`

## UI Direction
Tampilan customer/client harus mobile-first karena customer kemungkinan besar membuka portal dari WhatsApp di HP.

Prinsip tampilan customer/client:
- Prioritaskan alur customer mobile sebelum desktop.
- Gunakan teks singkat, tombol besar, dan jarak antar elemen yang nyaman disentuh.
- Hindari layout yang terasa seperti landing page marketing jika halaman tersebut seharusnya menjadi portal/tool.
- Customer portal harus langsung menunjukkan session, foto, download, edit, subscription, dan print request.
- Desktop boleh lebih luas, tetapi desain dasar tetap harus enak dipakai di layar kecil.

Prinsip tampilan admin/tenant:
- Admin tidak harus sesederhana customer portal; yang penting menu lengkap, jelas, dan mudah dipindai.
- Admin dashboard harus menyediakan akses ke tenant, station, customer, session archive, asset, template, print request, billing, subscription, sync log, dan settings.
- Admin boleh memakai sidebar, tabel, filter, tab, bulk action, dan form yang lebih padat.
- Tetap responsif, tetapi prioritas utama admin adalah kelengkapan kontrol dan efisiensi operasional.
 
## Product Role
This app is a SaaS customer portal and cloud archive for Dafydio Photobooth.
It is separate from dafydio_photobooth_station, but integrates with it.

## Core Responsibilities
- Store customer session archives.
- Store original photos, framed photos, and edited photos.
- Provide customer portal by WhatsApp login.
- Provide subscription access: regular and premium.
- Provide template marketplace for regular customers.
- Provide cloud editor and print request flow.
- Sync sessions and print requests with station.

## Station Responsibilities
dafydio_photobooth_station handles:
- Android device capture.
- Local session workflow.
- Local rendering if needed.
- Local printer queue.
- Actual printing.
- Syncing session assets to cloud.
- Polling cloud print requests.

## SaaS Model
Use single database multi-tenancy with tenant_id.
Tenant represents photobooth owner/studio/vendor.
Station belongs to tenant.
Customer belongs to tenant.
Sessions belong to tenant and customer.

## Subscription Rules
Regular:
- Can view session history.
- Can download original and framed photos.
- Can buy marketplace templates.
- Can edit only using purchased templates.
- Print request may be paid per request.

Premium:
- Can view and download all assets.
- Can use full editor.
- Can access premium template library.
- Can request print using quota or priority.
- Gets longer storage retention.

## Integration Pattern
Cloud does not directly control station printer.
Station polls cloud for print requests and updates status.

## Rekomendasi Folder Cloud
app/
  Actions/
    Station/
    Customer/
    Archive/
    Template/
    PrintRequest/
  Http/
    Controllers/
      Api/Station/
      Api/Customer/
      Api/Admin/
  Models/
  Policies/
  Jobs/
    Station/
    Archive/
    PrintRequest/
  Services/
    Storage/
    Sync/
    Billing/


When implementing features, never treat cloud as the printer/capture source.
Cloud is the archive, customer portal, SaaS billing, marketplace, and print request coordinator.
Station remains the capture and physical print executor.
