# Dafydio Photobooth Cloud Architecture

## Stack
- Laravel 13
- Inertia Vue
- MySQL
- Database Queue
- S3/R2 object storage
- Sanctum atau token khusus untuk station API
- WhatsApp + password dari station untuk customer auth
- Midtrans/Xendit untuk billing lokal
- Paddle/Stripe Cashier opsional untuk global billing

## Boundary
`dafydio_photobooth_cloud` adalah SaaS customer portal, arsip cloud, marketplace, editor, billing, dan koordinator print request.

Cloud tidak menjadi sumber capture dan tidak mengontrol printer secara langsung. `dafydio_photobooth_station` tetap menjadi executor untuk capture, render lokal, printer queue, dan physical print.

## LAN / Offline-First Integration Rule
`dafydio_photobooth_station` berjalan sebagai pusat operasional lokal. Device capture seperti Android dan MiniPC berada di jaringan lokal dan tidak punya endpoint publik stabil. Karena itu cloud tidak boleh bergantung pada kemampuan memanggil endpoint station atau device secara langsung.

Prinsip integrasi:
- Station selalu menjadi pihak yang aktif menghubungi cloud.
- Cloud tidak melakukan pull data dari station.
- Cloud tidak perlu mengetahui IP lokal, port, atau URL station.
- Tidak perlu port forwarding atau expose station ke internet.
- Station tetap bisa bekerja offline dan melakukan retry sync ketika cloud tersedia.
- Device tidak langsung bergantung ke cloud.
- Device mengirim data ke station, lalu station yang sync ke cloud.

Pola yang wajib dipakai:
- Session archive: station push metadata session ke cloud.
- Asset storage: station upload asset ke cloud.
- Template marketplace: station publish template dan asset template ke cloud.
- Print request: cloud menyimpan request, station polling request dari cloud.
- Print status: station push update status print ke cloud.

Keputusan ini menjaga station tetap kuat offline, aman di jaringan lokal, dan kompatibel dengan hosting cloud/shared hosting.

## Apps
- `dafydio_photobooth_station`: Laravel management photobooth station app dan pusat operasional lokal.
- `dafydio_photobooth_cloud`: Laravel SaaS portal dan sync API.
- `dafydio_photobooth_android`: Android agent photo device untuk capture.
- `dafydio_photobooth_miniPC`: Laravel agent photo device lokal alternatif untuk capture/render/print.

## App Responsibilities
### `dafydio_photobooth_station`
- Event management.
- Customer management.
- Local payment flow.
- Template master lokal.
- Session management.
- Original photo archive lokal.
- Framed photo/render lokal.
- Local print queue dan physical print.
- Async sync ke cloud.

### `dafydio_photobooth_cloud`
- SaaS customer portal.
- Session archive.
- Download foto.
- Template marketplace.
- Customer login.
- Premium online edit.
- Print request coordinator.
- Menerima sync dari station.

### `dafydio_photobooth_android`
- Capture foto.
- Upload original photo ke station.
- Pilih session/event.
- Cek payment/status ke station.
- Tidak bergantung langsung ke cloud.

### `dafydio_photobooth_miniPC`
- Device/agent lokal alternatif.
- Bisa capture, render, atau print sesuai kebutuhan booth PC.
- Berkomunikasi dengan station.
- Tidak bergantung langsung ke cloud.

## Local-To-Cloud Topology
```text
Android / MiniPC
      ↓
Photobooth Station
      ↓ async sync
Photobooth Cloud
```

Jika internet mati:
- Android/MiniPC tetap bisa capture.
- Station tetap bisa menjalankan payment lokal, render, dan print.
- Cloud sync menyusul saat koneksi stabil.

## Multi-Tenancy
Model yang dipakai adalah single database multi-tenant dengan kolom `tenant_id`.

Prinsip:
- Semua data operasional milik tenant harus membawa `tenant_id`.
- Station, customer, session, asset, template entitlement, edit job, print request, billing, dan sync log harus ter-scope ke tenant.
- Query aplikasi harus selalu difilter berdasarkan tenant aktif.
- Admin platform boleh lintas tenant, tenant admin hanya boleh melihat data tenant sendiri.

## Folder Aplikasi
```text
app/
  Actions/
    Archive/
    Billing/
    Customer/
    PrintRequest/
    Station/
    Template/
  Http/
    Controllers/
      Api/
        Admin/
        Customer/
        Station/
      Web/
        Admin/
        Customer/
        Tenant/
    Middleware/
  Jobs/
    Archive/
    Billing/
    PrintRequest/
    Station/
  Models/
  Policies/
  Services/
    Billing/
    Storage/
    Sync/
```

## Main Modules
- Tenant Management
- Station API
- Customer Portal
- Session Archive
- Asset Storage
- Template Marketplace
- Cloud Editor
- Print Request
- Billing and Subscription
- Notifications
- Audit and Sync Logs

## Data Flow
1. Station membuat session lokal setelah customer menggunakan photobooth.
2. Station menyimpan original photo dan hasil render frame secara lokal.
3. Station mengirim metadata session ke cloud.
4. Station upload asset ke S3/R2 melalui signed URL atau endpoint upload cloud.
5. Cloud menyimpan metadata, lokasi asset, dan status sync.
6. Customer login ke cloud portal memakai WhatsApp dan password yang dibuat/dikirim dari station.
7. Customer melihat history session, download asset sesuai subscription, membeli template, atau membuat edit job.
8. Customer membuat print request di cloud.
9. Station polling print request dari cloud.
10. Station menjalankan physical printing secara lokal.
11. Station mengirim update status print request ke cloud.

## Cloud Print Request SOP Concept
Bagian ini menjadi catatan dasar untuk SOP customer, admin, dan operator station.

Prinsip khusus request print dari cloud:
- Cloud hanya membuat order/antrian print request.
- Cloud tidak otomatis menjalankan printer.
- Cloud tidak mengirim command langsung ke printer.
- Station/operator lokal tetap menjadi pihak yang mengeksekusi print.
- Request print dari cloud baru dieksekusi setelah operator station aktif dan memilih/memproses antrian.

Alur customer:
1. Customer membuka cloud portal dari WhatsApp/login.
2. Customer memilih session dan foto.
3. Customer memilih template/layanan print jika tersedia.
4. Customer membuat request print dari cloud.
5. Jika berbayar, customer mengikuti instruksi pembayaran manual/QRIS.
6. Customer menunggu status request diproses admin/operator.

Alur admin cloud:
1. Admin melihat payment/request print masuk.
2. Admin memverifikasi pembayaran manual.
3. Jika pembayaran valid, admin approve payment.
4. Print request berubah menjadi `pending_operator`, bukan langsung `printing`.
5. Admin dapat memantau status sampai station mengirim update.

Alur station/operator:
1. Station polling print request dari cloud.
2. Operator melihat daftar antrian `pending_operator`.
3. Operator memilih request yang akan diproses.
4. Station mengambil asset/template yang diperlukan.
5. Station melakukan render/print secara lokal.
6. Station mengirim status ke cloud: `claimed`, `printing`, `printed`, atau `failed`.

Status print request khusus cloud:
- `pending_payment`: request sudah dibuat, menunggu pembayaran/approval.
- `pending_operator`: pembayaran sudah valid, menunggu operator station aktif mengeksekusi.
- `claimed`: request sudah diambil/dipilih station/operator.
- `printing`: sedang diproses print lokal.
- `printed`: print selesai.
- `failed`: print gagal.
- `cancelled`: request dibatalkan.

## Access Rules
### Regular
- Bisa melihat session history.
- Bisa download original dan framed photos.
- Bisa membeli marketplace templates.
- Bisa edit hanya memakai template yang sudah dibeli.
- Print request dapat ditagih per request.

### Premium
- Bisa melihat dan download semua asset.
- Bisa memakai full editor.
- Bisa mengakses premium template library.
- Bisa request print memakai quota atau priority.
- Mendapat storage retention lebih panjang.

## Queue Jobs
- `Archive\ProcessUploadedAsset`: validasi metadata asset dan update status.
- `Archive\GenerateArchiveExport`: membuat zip export untuk customer.
- `Station\FinalizeSessionSync`: menandai session complete setelah asset lengkap.
- `PrintRequest\ExpireStalePrintRequest`: membatalkan request yang terlalu lama pending.
- `Billing\SyncPaymentStatus`: sinkronisasi status invoice/payment gateway.

## Storage Layout
```text
tenants/{tenant_id}/sessions/{session_id}/originals/{asset_id}.{ext}
tenants/{tenant_id}/sessions/{session_id}/framed/{asset_id}.{ext}
tenants/{tenant_id}/sessions/{session_id}/edited/{asset_id}.{ext}
tenants/{tenant_id}/exports/{archive_export_id}.zip
tenants/{tenant_id}/templates/{template_id}/{file}
```

## Security
- Station API memakai token yang terikat ke `station_id` dan `tenant_id`.
- Token station disimpan sebagai hash rahasia (`api_token_hash`) dan lookup hash deterministik (`api_token_lookup`) agar autentikasi tidak perlu scan semua station aktif.
- Customer API/web session memakai customer auth berbasis WhatsApp + password.
- Download asset sebaiknya memakai temporary signed URL.
- Upload asset harus dibatasi berdasarkan session, station, tenant, content type, dan ukuran file.
- Print request hanya boleh dibaca station dalam tenant yang sama.
- Webhook payment harus diverifikasi signature-nya.

## Implementation Notes
- Jangan membuat fitur yang menganggap cloud bisa mengambil foto dari kamera station.
- Jangan membuat fitur yang membuat cloud mengirim perintah langsung ke printer.
- Jangan membuat fitur yang membutuhkan cloud memanggil endpoint station di LAN.
- Semua integrasi station-cloud harus berbasis station push atau station polling ke cloud.
- Print selalu berbasis request yang dipolling station.
- Semua tabel domain memakai UUID atau ULID agar aman untuk sync lintas device.
