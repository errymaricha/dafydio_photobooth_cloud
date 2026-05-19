# Dafydio Photobooth Cloud Data Model

Semua tabel domain memakai `tenant_id` kecuali tabel platform-level yang memang global. Gunakan UUID atau ULID sebagai primary key agar aman untuk sync dari station.

## tenants
Pemilik studio/vendor photobooth.

Kolom utama:
- `id`
- `name`
- `slug`
- `business_name`
- `whatsapp_number`
- `email`
- `timezone`
- `status`
- `created_at`
- `updated_at`

## stations
Device/station milik tenant.

Kolom utama:
- `id`
- `tenant_id`
- `name`
- `code`
- `api_token_hash`
- `api_token_lookup`
- `device_identifier`
- `app_version`
- `last_seen_at`
- `status`
- `created_at`
- `updated_at`

Index:
- unique `tenant_id, code`
- unique `api_token_lookup`
- index `tenant_id, last_seen_at`
- index `status, api_token_lookup`

## customers
Customer akhir yang mengakses portal.

Kolom utama:
- `id`
- `tenant_id`
- `name`
- `whatsapp_number`
- `password`
- `last_login_at`
- `status`
- `created_at`
- `updated_at`

Index:
- unique `tenant_id, whatsapp_number`

## customer_subscriptions
Subscription customer per tenant.

Kolom utama:
- `id`
- `tenant_id`
- `customer_id`
- `plan`
- `status`
- `starts_at`
- `ends_at`
- `print_quota`
- `storage_retention_days`
- `provider`
- `provider_subscription_id`
- `created_at`
- `updated_at`

Nilai `plan`:
- `regular`
- `premium`

Nilai `status`:
- `active`
- `past_due`
- `cancelled`
- `expired`

## cloud_sessions
Session photobooth yang disinkronkan dari station.

Kolom utama:
- `id`
- `tenant_id`
- `station_id`
- `customer_id` nullable for guest sessions
- `station_session_id`
- `title`
- `started_at`
- `ended_at`
- `sync_status`
- `metadata`
- `created_at`
- `updated_at`

Nilai `sync_status`:
- `pending`
- `syncing`
- `complete`
- `failed`

Catatan:
- Jika station mengirim `customer_whatsapp = null`, session disimpan sebagai guest session dengan `customer_id = null`.
- Guest session tetap masuk arsip admin/event, tetapi tidak masuk customer portal sampai dilink ke customer WhatsApp.

Index:
- unique `tenant_id, station_id, station_session_id`
- index `tenant_id, customer_id, started_at`

## cloud_session_assets
Asset foto milik session.

Kolom utama:
- `id`
- `tenant_id`
- `cloud_session_id`
- `station_asset_id`
- `type`
- `disk`
- `path`
- `mime_type`
- `size_bytes`
- `checksum`
- `width`
- `height`
- `status`
- `created_at`
- `updated_at`

Nilai `type`:
- `original`
- `framed`
- `edited`

Nilai `status`:
- `pending_upload`
- `uploaded`
- `processed`
- `failed`

Index:
- unique `tenant_id, cloud_session_id, station_asset_id`
- index `tenant_id, cloud_session_id, type`

## cloud_templates
Template marketplace dan premium library.

Kolom utama:
- `id`
- `tenant_id`
- `name`
- `description`
- `access_level`
- `price_amount`
- `price_currency`
- `preview_path`
- `source_path`
- `status`
- `created_at`
- `updated_at`

Nilai `access_level`:
- `marketplace`
- `premium`
- `private`

## customer_template_entitlements
Hak akses customer terhadap template berbayar.

Kolom utama:
- `id`
- `tenant_id`
- `customer_id`
- `cloud_template_id`
- `source`
- `payment_id`
- `granted_at`
- `expires_at`
- `created_at`
- `updated_at`

Nilai `source`:
- `purchase`
- `premium`
- `admin_grant`

Index:
- unique `tenant_id, customer_id, cloud_template_id`

## cloud_edit_jobs
Job editing cloud yang menghasilkan edited asset.

Kolom utama:
- `id`
- `tenant_id`
- `customer_id`
- `cloud_session_id`
- `source_asset_id`
- `cloud_template_id`
- `result_asset_id`
- `status`
- `editor_payload`
- `error_message`
- `created_at`
- `updated_at`

Nilai `status`:
- `draft`
- `queued`
- `processing`
- `complete`
- `failed`

## cloud_print_requests
Request print dari customer yang dipolling station.

Kolom utama:
- `id`
- `tenant_id`
- `station_id`
- `customer_id`
- `cloud_session_id`
- `cloud_session_asset_id`
- `quantity`
- `status`
- `priority`
- `payment_status`
- `station_claimed_at`
- `printed_at`
- `cancelled_at`
- `metadata`
- `created_at`
- `updated_at`

Nilai `status`:
- `pending`
- `claimed`
- `printing`
- `printed`
- `failed`
- `cancelled`
- `expired`

Nilai `payment_status`:
- `not_required`
- `pending`
- `paid`
- `failed`
- `refunded`

Index:
- index `tenant_id, station_id, status, created_at`
- index `tenant_id, customer_id, created_at`

## archive_exports
Zip/export archive untuk download customer.

Kolom utama:
- `id`
- `tenant_id`
- `customer_id`
- `cloud_session_id`
- `disk`
- `path`
- `status`
- `expires_at`
- `created_at`
- `updated_at`

Nilai `status`:
- `queued`
- `processing`
- `ready`
- `failed`
- `expired`

## station_sync_logs
Log sync dari station ke cloud.

Kolom utama:
- `id`
- `tenant_id`
- `station_id`
- `direction`
- `topic`
- `status`
- `payload`
- `response`
- `error_message`
- `created_at`
- `updated_at`

Nilai `direction`:
- `station_to_cloud`
- `cloud_to_station`

## payments
Transaksi pembayaran lokal/global.

Kolom utama:
- `id`
- `tenant_id`
- `customer_id`
- `provider`
- `provider_payment_id`
- `purpose`
- `amount`
- `currency`
- `status`
- `payload`
- `paid_at`
- `created_at`
- `updated_at`

Nilai `purpose`:
- `subscription`
- `template_purchase`
- `print_request`

Nilai `status`:
- `pending`
- `paid`
- `failed`
- `expired`
- `refunded`
