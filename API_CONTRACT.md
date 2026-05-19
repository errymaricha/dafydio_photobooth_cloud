# Dafydio Photobooth Cloud API Contract

Base URL:

```text
https://cloud.dafydio.com
```

Semua response JSON memakai format umum:

```json
{
  "data": {},
  "meta": {},
  "message": null
}
```

Error memakai format:

```json
{
  "message": "Validation failed",
  "errors": {}
}
```

## Auth

### Station API
Station memakai token khusus station atau Sanctum token.

Header:

```text
Authorization: Bearer {station_token}
Accept: application/json
```

Token selalu terikat ke:
- `tenant_id`
- `station_id`

### Customer API
Customer login memakai WhatsApp dan password yang dibuat/dikirim dari station.

Header setelah login:

```text
Authorization: Bearer {customer_token}
Accept: application/json
```

## Station API

### Heartbeat
```http
POST /api/station/heartbeat
```

Request:
```json
{
  "device_identifier": "android-device-id",
  "app_version": "1.0.0",
  "local_time": "2026-05-11T18:00:00+07:00"
}
```

Response:
```json
{
  "data": {
    "station_id": "01H...",
    "server_time": "2026-05-11T11:00:00Z"
  },
  "meta": {},
  "message": "OK"
}
```

### Upsert Customer
```http
POST /api/station/customers
```

Request:
```json
{
  "name": "Customer Name",
  "whatsapp_number": "+6281234567890",
  "password": "plain-password-from-station"
}
```

Response:
```json
{
  "data": {
    "customer_id": "01H...",
    "whatsapp_number": "+6281234567890"
  },
  "meta": {},
  "message": "Customer synced"
}
```

### Create Session
```http
POST /api/station/sessions
```

Request:
```json
{
  "station_session_id": "local-session-uuid",
  "customer": {
    "whatsapp_number": "+6281234567890",
    "name": "Customer Name"
  },
  "title": "Wedding Session",
  "started_at": "2026-05-11T18:00:00+07:00",
  "ended_at": "2026-05-11T18:10:00+07:00",
  "metadata": {}
}
```

Response:
```json
{
  "data": {
    "cloud_session_id": "01H...",
    "sync_status": "pending"
  },
  "meta": {},
  "message": "Session created"
}
```

### Sync Session From Station
Endpoint utama untuk session dari `dafydio_photobooth_station`.

```http
POST /api/station/sync/session
Authorization: Bearer {station_token}
Idempotency-Key: station:{station_id}:event:{event_id}:session:{session_id}
Accept: application/json
```

Request:
```json
{
  "event": {
    "id": "event-001",
    "event_code": "WED-001",
    "event_name": "Wedding A",
    "cloud_upload_mode": "originals_and_framed",
    "cloud_member_scope": "regular_and_premium",
    "cloud_sync_timing": "after_payment"
  },
  "session": {
    "id": "SES-LOCAL-001",
    "session_code": "SES-ABC123",
    "station_id": "station-local-001",
    "customer_id": null,
    "customer_whatsapp": null,
    "customer_tier": "regular",
    "payment_status": "paid",
    "payment_method": "manual",
    "status": "uploaded"
  }
}
```

Response:
```json
{
  "data": {
    "cloud_session_id": "01H...",
    "customer_id": null,
    "is_guest": true,
    "sync_status": "complete"
  },
  "meta": {
    "idempotency_key": "station:station-001:event:event-001:session:SES-LOCAL-001"
  },
  "message": "Session synced"
}
```

Aturan guest session:
- `session.customer_whatsapp` boleh `null`.
- Jika `customer_whatsapp = null`, cloud menyimpan session sebagai guest session.
- Cloud tidak membuat customer dummy untuk guest session.
- Guest session tetap masuk archive event/admin session.
- Guest session tidak masuk customer portal dan tidak bisa login sampai WhatsApp diisi/link belakangan.
- `customer_tier` guest boleh dikirim `regular` atau `guest`; akses customer portal tetap tidak aktif tanpa WhatsApp.

### Register Session Assets
```http
POST /api/station/sessions/{cloud_session_id}/assets
```

Request:
```json
{
  "assets": [
    {
      "station_asset_id": "local-asset-uuid",
      "asset_type": "original",
      "file_name": "photo-001.jpg",
      "mime_type": "image/jpeg",
      "file_size": 1200000,
      "checksum": "sha256-hash",
      "width": 4000,
      "height": 6000
    }
  ]
}
```

Catatan kompatibilitas:
- Cloud juga masih menerima nama lama `type`, `filename`, dan `size_bytes`.

Response:
```json
{
  "data": {
    "assets": [
      {
        "cloud_asset_id": "01H...",
        "station_asset_id": "local-asset-uuid",
        "upload_url": "https://signed-upload-url",
        "status": "pending_upload"
      }
    ]
  },
  "meta": {},
  "message": "Assets registered"
}
```

### Link Guest Session To Customer
Dipakai ketika session awalnya guest karena `customer_whatsapp = null`, lalu WhatsApp diisi belakangan dari station/admin.

```http
POST /api/station/sessions/{cloud_session_id}/link-customer
Authorization: Bearer {station_token}
Accept: application/json
```

Request:
```json
{
  "customer_whatsapp": "6282118401998",
  "customer_name": "Customer Name",
  "customer_tier": "regular",
  "customer_id": "station-customer-id"
}
```

Response:
```json
{
  "data": {
    "cloud_session_id": "01H...",
    "customer_id": "01H...",
    "customer_whatsapp": "6282118401998",
    "is_guest": false
  },
  "meta": {},
  "message": "Guest session linked to customer"
}
```

Catatan:
- Cloud mencari/membuat customer berdasarkan WhatsApp dalam tenant yang sama.
- `cloud_sessions.customer_id` diisi setelah link berhasil.
- Metadata session diperbarui dengan `customer_whatsapp`.
- File asset tidak wajib dipindahkan dari path guest.
- Setelah link, session bisa muncul di customer portal jika customer login memakai WhatsApp tersebut.

### Complete Asset Upload
```http
POST /api/station/assets/{cloud_asset_id}/complete
```

Request:
```json
{
  "status": "completed",
  "checksum": "sha256-hash",
  "file_size": 1200000
}
```

Catatan kompatibilitas:
- Cloud juga masih menerima `size_bytes`.
- `status` dari station boleh `completed` atau `uploaded`; keduanya disimpan sebagai status cloud `uploaded`.

Response:
```json
{
  "data": {
    "cloud_asset_id": "01H...",
    "status": "uploaded"
  },
  "meta": {},
  "message": "Asset uploaded"
}
```

### Finalize Session Sync
```http
POST /api/station/sessions/{cloud_session_id}/finalize
```

Response:
```json
{
  "data": {
    "cloud_session_id": "01H...",
    "sync_status": "complete"
  },
  "meta": {},
  "message": "Session sync complete"
}
```

### Sync Template Metadata
Import template utama berasal dari `dafydio_photobooth_station`. Cloud tidak pull template dari station atau device LAN.

```http
POST /api/station/sync/template
Authorization: Bearer {station_token}
Idempotency-Key: station:{station_id}:template:{template_id}
Accept: application/json
```

Request:
```json
{
  "template": {
    "station_template_id": "tpl-local-001",
    "template_code": "WEDDING-001",
    "template_name": "Wedding Elegant",
    "category": "wedding",
    "paper_size": "4R",
    "status": "published",
    "access_tier": "regular"
  },
  "slots": [
    {
      "slot_index": 1,
      "x": 120,
      "y": 180,
      "width": 800,
      "height": 1200,
      "rotation": 0
    }
  ],
  "assets": []
}
```

Response minimal:
```json
{
  "data": {
    "cloud_template_id": "cloud-tpl-001"
  },
  "meta": {
    "idempotency_key": "station:station-001:template:tpl-local-001"
  },
  "message": "Template synced"
}
```

Cloud response saat ini juga boleh menyertakan `station_template_id`, `template_code`, dan `status`.

### Register Template Assets
Station tidak wajib menyimpan `cloud_asset_id` untuk template asset. Identitas asset cukup memakai `station_asset_id`.

```http
POST /api/station/templates/{cloud_template_id}/assets
Authorization: Bearer {station_token}
Accept: application/json
```

Request:
```json
{
  "assets": [
    {
      "station_asset_id": "asset-frame-001",
      "asset_type": "frame",
      "file_name": "frame.png",
      "mime_type": "image/png",
      "file_size": 2450000,
      "checksum": "sha256-frame"
    },
    {
      "station_asset_id": "asset-preview-001",
      "asset_type": "preview",
      "file_name": "preview.jpg",
      "mime_type": "image/jpeg",
      "file_size": 320000,
      "checksum": "sha256-preview"
    }
  ]
}
```

Response:
```json
{
  "data": {
    "assets": [
      {
        "station_asset_id": "asset-frame-001",
        "asset_type": "frame",
        "upload_url": "https://cloud.dafydio.com/api/station/templates/cloud-tpl-001/assets/asset-frame-001/upload",
        "status": "pending_upload"
      }
    ]
  },
  "meta": {},
  "message": "Template assets registered"
}
```

### Upload Template Asset
```http
PUT /api/station/templates/{cloud_template_id}/assets/{station_asset_id}/upload
Authorization: Bearer {station_token}
Content-Type: image/png
```

Body:
```text
(binary file)
```

### Complete Template Asset
```http
POST /api/station/templates/{cloud_template_id}/assets/{station_asset_id}/complete
Authorization: Bearer {station_token}
Accept: application/json
```

Request:
```json
{
  "status": "completed",
  "checksum": "sha256-frame",
  "file_size": 2450000
}
```

Response:
```json
{
  "data": {
    "station_asset_id": "asset-frame-001",
    "status": "uploaded",
    "storage_path": "tenants/tenant-id/templates/cloud-tpl-001/frame/asset-frame-001.png",
    "file_url": "https://cloud.dafydio.com/storage/tenants/tenant-id/templates/cloud-tpl-001/frame/asset-frame-001.png"
  },
  "meta": {},
  "message": "Template asset completed"
}
```

Station wajib menyimpan minimal:
- template id lokal
- `cloud_template_id`
- `station_asset_id`
- sync status
- last error

### Poll Print Requests
```http
GET /api/station/print-requests?status=pending_operator&limit=10
```

Response:
```json
{
  "data": [
    {
      "print_request_id": "01H...",
      "cloud_session_id": "01H...",
      "asset_id": "01H...",
      "asset_download_url": "https://signed-download-url",
      "template": {
        "cloud_template_id": "01H...",
        "template_code": "WEDDING-001",
        "template_name": "Wedding Elegant",
        "slots": [],
        "assets": []
      },
      "quantity": 1,
      "priority": "normal",
      "created_at": "2026-05-11T11:00:00Z"
    }
  ],
  "meta": {},
  "message": null
}
```

Catatan request print dari cloud:
- Station/operator memproses request yang sudah `pending_operator`.
- Status `pending_payment` tidak boleh dipolling sebagai pekerjaan siap print.
- Cloud tidak menjalankan printer dan tidak mengirim command print langsung.
- Operator station harus aktif memilih/memproses antrian print.

### Update Print Request Status
```http
PATCH /api/station/print-requests/{print_request_id}
```

Request:
```json
{
  "status": "printing",
  "error_message": null
}
```

Allowed status dari station:
- `claimed`
- `printing`
- `printed`
- `failed`

## Customer API

### Login
```http
POST /api/customer/auth/login
```

Request:
```json
{
  "whatsapp_number": "+6281234567890",
  "password": "password-from-station"
}
```

Response:
```json
{
  "data": {
    "token": "customer-token",
    "customer": {
      "id": "01H...",
      "name": "Customer Name",
      "subscription_plan": "regular"
    }
  },
  "meta": {},
  "message": "Logged in"
}
```

### List Sessions
```http
GET /api/customer/sessions
```

### Session Detail
```http
GET /api/customer/sessions/{cloud_session_id}
```

### Download Asset
```http
POST /api/customer/assets/{cloud_asset_id}/download-url
```

Response:
```json
{
  "data": {
    "download_url": "https://signed-download-url",
    "expires_at": "2026-05-11T11:10:00Z"
  },
  "meta": {},
  "message": null
}
```

### List Templates
```http
GET /api/customer/templates
```

Query:
- `access=marketplace|premium|owned`

### Purchase Template
```http
POST /api/customer/templates/{template_id}/purchase
```

Response:
```json
{
  "data": {
    "payment_id": "01H...",
    "payment_url": null,
    "status": "pending",
    "manual_instruction": "Transfer manual/QRIS lalu kirim bukti pembayaran ke admin Dafydio Photobooth."
  },
  "meta": {},
  "message": "Payment created"
}
```

Catatan payment manual:
- Template gratis langsung menghasilkan payment `paid` dan entitlement aktif.
- Template berbayar menghasilkan payment `pending`.
- Entitlement template baru aktif setelah admin cloud approve payment.
- Saat belum punya payment gateway, bukti transfer/QRIS diverifikasi manual oleh admin.

### List Payments
```http
GET /api/customer/payments
```

Response:
```json
{
  "data": [
    {
      "id": "01H...",
      "purpose": "template_purchase",
      "template_name": "Wedding Elegant",
      "amount": 75000,
      "currency": "IDR",
      "status": "pending",
      "provider": "manual",
      "manual_instruction": "Transfer manual/QRIS lalu kirim bukti pembayaran ke admin Dafydio Photobooth.",
      "created_at": "2026-05-18T15:00:00Z"
    }
  ],
  "meta": {},
  "message": null
}
```

### Create Edit Job
```http
POST /api/customer/edit-jobs
```

Request:
```json
{
  "cloud_session_id": "01H...",
  "source_asset_id": "01H...",
  "cloud_template_id": "01H...",
  "editor_payload": {}
}
```

Response:
```json
{
  "data": {
    "edit_job_id": "01H...",
    "result_asset_id": "01H...",
    "status": "completed",
    "error_message": null
  },
  "meta": {},
  "message": "Edit job completed"
}
```

Catatan:
- Tahap saat ini menjalankan render sederhana secara synchronous saat request dibuat.
- Render memakai source photo customer dan frame/source template yang sudah tersimpan di cloud.
- Jika frame template belum tersedia, job menjadi `failed` dengan `error_message`.
- Hasil render disimpan sebagai asset session bertipe `edited`.
- Customer hanya bisa memakai template yang sudah dimiliki, template free yang sudah di-purchase, atau template premium jika customer premium.

### List Edit Jobs
```http
GET /api/customer/edit-jobs
```

Response:
```json
{
  "data": [
    {
      "id": "01H...",
      "cloud_session_id": "01H...",
      "source_asset_id": "01H...",
      "cloud_template_id": "01H...",
      "result_asset_id": "01H...",
      "result_asset": {
        "id": "01H...",
        "type": "edited",
        "file_url": "https://signed-download-url"
      },
      "status": "completed",
      "error_message": null,
      "created_at": "2026-05-17T10:00:00Z",
      "session_title": "Wedding Session",
      "template_name": "Wedding Elegant",
      "source_asset_type": "framed"
    }
  ],
  "meta": {},
  "message": null
}
```

### Create Print Request
```http
POST /api/customer/print-requests
```

Request:
```json
{
  "cloud_session_id": "01H...",
  "cloud_session_asset_id": "01H...",
  "cloud_template_id": "01H...",
  "quantity": 1
}
```

Response:
```json
{
  "data": {
    "print_request_id": "01H...",
    "status": "pending_payment",
    "payment_status": "pending"
  },
  "meta": {},
  "message": "Print request created"
}
```

Konsep beli template + request print:
- Jika request membutuhkan pembayaran, cloud membuat payment `pending` dan print request `pending_payment`.
- Admin cloud approve payment setelah bukti bayar valid.
- Setelah approve, print request berubah menjadi `pending_operator`.
- Station polling dan operator lokal mengeksekusi print dari antrian `pending_operator`.
- Station mengirim status print ke cloud setelah operator memproses.

## Admin/Tenant API

### List Stations
```http
GET /api/admin/stations
```

### Create Station Token
```http
POST /api/admin/stations/{station_id}/tokens
```

### List Customers
```http
GET /api/admin/customers
```

### List Sessions
```http
GET /api/admin/sessions
```

### List Print Requests
```http
GET /api/admin/print-requests
```

### Manage Templates
```http
GET /api/admin/templates
POST /api/admin/templates
PATCH /api/admin/templates/{template_id}
DELETE /api/admin/templates/{template_id}
```

### Manual Payment Review
Admin cloud melakukan review pembayaran marketplace secara manual.

```http
GET /admin/payments
POST /admin/payments/{payment_id}/approve
POST /admin/payments/{payment_id}/reject
```

Aturan:
- `approve` hanya untuk payment `pending`.
- Jika `purpose = template_purchase`, approve otomatis membuat `customer_template_entitlements`.
- Jika `purpose = template_print_request`, approve mengubah print request terkait menjadi `pending_operator`.
- `reject` mengubah payment menjadi `failed` dan tidak memberi entitlement.

## Billing Webhooks

### Midtrans Webhook
```http
POST /api/webhooks/midtrans
```

### Xendit Webhook
```http
POST /api/webhooks/xendit
```

Webhook wajib:
- validasi signature provider
- idempotent berdasarkan provider event/payment id
- update `payments`
- grant subscription/template entitlement/print request access sesuai `purpose`

## Status Ownership

Cloud boleh membuat:
- session record
- asset record
- customer portal state
- payment state
- print request pending

Station boleh memperbarui:
- heartbeat
- asset upload completion
- session sync completion
- print request status setelah dipolling

Cloud tidak boleh langsung mengirim command print ke station.
