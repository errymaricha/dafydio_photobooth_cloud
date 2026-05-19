# Photobooth Cloud Integration Rules

This document defines the integration contract between `dafydio_photobooth_station` and `dafydio_photobooth_cloud`.

`dafydio_photobooth_cloud` is a separate SaaS system. It is integrated with station, but it is not a replacement for station.

## System Boundary

Station is responsible for:

- Android device capture.
- Local session workflow.
- Local asset storage before sync.
- Local rendering and editing when performed on station.
- Manual payment approval.
- Local print queue.
- Physical printing.
- Syncing session metadata and assets to cloud.
- Polling cloud print requests.

Cloud is responsible for:

- SaaS tenant and customer portal.
- Long-term session archive.
- Original, framed, and edited photo storage.
- Customer login and subscription access.
- Template marketplace for regular customers.
- Premium cloud editing.
- Cloud print request coordination.
- Download archive ZIP generation.
- Billing and subscription logic.

Cloud must never directly control local printers. Station must execute all physical printing.

## Integration Direction

Use async, resilient integration.

Allowed flows:

- Station pushes session metadata to cloud.
- Station uploads assets to cloud.
- Station polls cloud for print requests.
- Station updates cloud print request status.
- Cloud returns signed upload/download URLs or controlled API upload targets.

Avoid:

- Cloud calling station directly over the public internet.
- Cloud requiring station to always be online.
- Synchronous print execution from cloud.

## Event-Driven Cloud Upload

Cloud upload is controlled by event settings in station.

Station must not upload every session to cloud by default. Station may only create cloud sync jobs when the related event has cloud upload enabled.

Recommended event settings:

- `cloud_enabled`: whether this event uploads sessions to cloud.
- `cloud_upload_mode`: `none`, `originals_only`, `framed_only`, or `originals_and_framed`.
- `cloud_member_scope`: `regular_and_premium`, `premium_only`, or `all_customers`.
- `cloud_sync_timing`: `after_payment`, `after_session_complete`, or `after_render`.
- `cloud_retention_days`: optional retention hint for cloud archive cleanup.
- `cloud_template_marketplace_enabled`: whether regular customers can access marketplace templates for this event.

When `cloud_enabled` is false, station must not create cloud sync jobs for the event.

When `cloud_enabled` is true, station creates local durable sync jobs. Upload must not happen inside the primary capture, payment, render, or print request path.

Membership tier controls what a customer can do in cloud after upload. Membership tier does not decide whether an event session is uploaded unless the event explicitly sets `cloud_member_scope` to a tier-limited value.

For the default Dafydio flow:

- Event setting controls whether sessions are uploaded.
- Regular and premium member sessions are uploaded when event cloud upload is enabled.
- Regular customers can view/download cloud archives and use purchased marketplace templates.
- Premium customers can use premium cloud features such as full editor, premium templates, and print request quota.

## Local Durable Sync Queue

Station internet may be unavailable or unstable. Cloud upload must be implemented with a durable local queue.

Recommended local sync job fields:

- `event_id`
- `session_id`
- `customer_id`
- `status`: `pending`, `syncing`, `synced`, `failed`, or `skipped`
- `upload_mode`
- `attempts`
- `next_retry_at`
- `last_error`
- `idempotency_key`
- `started_at`
- `finished_at`

Recommended session sync fields, if denormalized status is useful:

- `cloud_sync_status`: `not_required`, `pending`, `syncing`, `synced`, or `failed`
- `cloud_sync_attempts`
- `cloud_synced_at`
- `cloud_last_error`

Required behavior:

- Capture must continue when cloud is unavailable.
- Payment approval must continue when cloud is unavailable.
- Local rendering must continue when cloud is unavailable.
- Local printing must continue when cloud is unavailable.
- Cloud-origin print requests may require cloud connectivity, but existing local station workflows must not.
- Failed sync jobs must be retryable.
- Local assets must not be deleted before cloud sync succeeds or retention policy allows cleanup.
- Admin UI should show pending, synced, failed, and retryable cloud sync counts.

Recommended retry backoff:

- Attempt 1: immediately.
- Attempt 2: after 1 minute.
- Attempt 3: after 5 minutes.
- Attempt 4: after 15 minutes.
- Attempt 5 and later: after 1 hour, or according to configured retry policy.

## Station Sync Rules

When a photo session changes to a cloud-syncable state, station may sync it.

Syncable states:

- Session completed.
- Payment approved.
- Rendered output available.
- Print order completed.
- Customer identity linked.

Cloud syncable state alone is not enough. The related event must also have cloud upload enabled.

Station should sync:

- Station ID or station code.
- Station session ID.
- Session code.
- Customer WhatsApp, nullable for guest sessions.
- Customer ID if available.
- Customer tier at sync time.
- Payment status.
- Payment method.
- Session status.
- Captured and completed timestamps.
- Original photos.
- Framed or rendered outputs.
- Selected template metadata if available.

Station must not assume sync is instant. Every sync operation must be retryable and idempotent.

Guest session rules:

- `customer_whatsapp` may be `null`.
- If `customer_whatsapp` is `null`, cloud stores the archive as a guest session.
- Cloud must not create dummy customers for guest sessions.
- Guest sessions remain visible in admin/event archive.
- Guest sessions do not appear in customer portal until they are linked to a real customer WhatsApp later.
- Cloud may treat guest tier as `regular` for archive retention defaults, but portal access remains disabled without WhatsApp login identity.
- When WhatsApp is known later, station can link the guest archive using `POST /api/station/sessions/{cloud_session_id}/link-customer`.
- Archive UI should display guest identity as `Guest - {session_code}`, never as `null`.
- Archive UI should provide filters: all sessions, customer sessions with WhatsApp, and guest sessions.

## Cloud Identity Mapping

Customer identity between station and cloud is based primarily on normalized WhatsApp number.

Rules:

- Normalize WhatsApp before sync.
- Use Indonesian normalized format such as `62812...`.
- Do not create duplicate cloud customers for the same normalized WhatsApp within the same tenant.
- Do not create dummy cloud customers when station sends `customer_whatsapp = null`.
- Station customer ID and cloud customer ID are separate IDs.
- Cloud should store station customer ID only as an external reference.
- Guest-to-customer linking should be done by a dedicated station/admin action after WhatsApp is known.

## API Authentication

Station-to-cloud API must use station-scoped credentials.

Allowed:

- Station API key hashed in cloud database.
- Sanctum-style bearer token.
- HMAC signed requests for upload and status endpoints.

Required:

- Every station API request includes a station identifier.
- Cloud validates that the station belongs to the tenant.
- Tokens and API keys are revocable.
- Never store raw API keys.

## Idempotency

All station-to-cloud write endpoints must be idempotent.

Recommended idempotency keys:

- Session sync: `station:{station_id}:session:{station_session_id}`
- Asset sync: `station:{station_id}:asset:{asset_id}`
- Print status: `station:{station_id}:print_request:{print_request_id}:{status}`

Cloud must update existing records instead of creating duplicates when the same station object is synced again.

## Asset Sync Rules

Station assets uploaded to cloud must include:

- `asset_type`: `original`, `framed`, `edited`, or `thumbnail`.
- Station asset ID.
- Session ID.
- Capture index when applicable.
- File name.
- MIME type.
- File size.
- Width and height when available.
- Checksum when available.

Cloud storage path pattern:

```text
tenants/{tenant_id}/customers/{customer_id}/sessions/{cloud_session_id}/{asset_type}/{file_name}
tenants/{tenant_id}/guests/sessions/{cloud_session_id}/{asset_type}/{file_name}
```

Cloud must serve customer downloads using temporary signed URLs or controlled download endpoints. Do not expose permanent public object URLs.

## Archive Download Rules

Archive ZIP generation belongs to cloud, not station.

Cloud creates ZIP archives containing:

- `original/`
- `framed/`
- `edited/` when available
- `metadata.json` when useful

ZIP generation must run as a queue job. If an archive already exists and assets have not changed, reuse it.

## Subscription Access Rules

Regular customer:

- Can view session history.
- Can download original and framed assets.
- Can access template marketplace.
- Can edit only with purchased marketplace templates.
- Can request print only if paid or allowed by policy.

Premium customer:

- Can view session history.
- Can download all allowed assets.
- Can access full editor.
- Can use premium template library.
- Can request print based on quota or priority rules.

Station may sync customer tier if known, but cloud is the final authority for cloud feature access.

## Template Marketplace Rules

Template marketplace is primarily for regular customers.

Regular:

- Can browse marketplace templates.
- Must purchase or receive entitlement before using a marketplace template.
- Can edit sessions only with owned templates.

Premium:

- Uses premium template library by subscription.
- Marketplace may be hidden or reserved for exclusive paid add-ons.

Cloud owns marketplace purchase and entitlement logic. Station may sync templates, but cloud decides customer access.

### Station Template Publish Endpoint

Station remains the master for local Android/minipc templates. Cloud only receives templates that station marks ready to publish.

Endpoint:

```http
POST /api/station/sync/template
Authorization: Bearer {station_token}
Accept: application/json
Idempotency-Key: station:{station_id}:template:{station_template_id}
```

Payload:

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
  "assets": [
    {
      "station_asset_id": "asset-frame-001",
      "asset_type": "frame",
      "file_name": "wedding-frame.png",
      "mime_type": "image/png",
      "checksum": "sha256...",
      "storage_path": "templates/wedding-frame.png"
    },
    {
      "station_asset_id": "asset-preview-001",
      "asset_type": "preview",
      "file_name": "preview.jpg",
      "mime_type": "image/jpeg",
      "storage_path": "templates/preview.jpg"
    }
  ]
}
```

Response:

```json
{
  "data": {
    "cloud_template_id": "01...",
    "station_template_id": "tpl-local-001",
    "template_code": "WEDDING-001",
    "status": "active"
  },
  "meta": {
    "idempotency_key": "station:STATION-001:template:tpl-local-001"
  },
  "message": "Template synced"
}
```

Cloud stores slot geometry and asset manifest, but does not pull directly from Android or control station template rendering.

If the template file/preview must be uploaded after metadata sync:

1. Register template assets:

```http
POST /api/station/templates/{cloud_template_id}/assets
Authorization: Bearer {station_token}
Accept: application/json
```

```json
{
  "assets": [
    {
      "station_asset_id": "asset-preview-001",
      "asset_type": "preview",
      "file_name": "preview.jpg",
      "mime_type": "image/jpeg",
      "file_size": 123456,
      "checksum": "sha256..."
    },
    {
      "station_asset_id": "asset-frame-001",
      "asset_type": "frame",
      "file_name": "frame.png",
      "mime_type": "image/png"
    }
  ]
}
```

2. Upload binary:

```http
PUT /api/station/templates/{cloud_template_id}/assets/{station_asset_id}/upload
Authorization: Bearer {station_token}
Content-Type: image/png
```

3. Complete asset:

```http
POST /api/station/templates/{cloud_template_id}/assets/{station_asset_id}/complete
Authorization: Bearer {station_token}
Accept: application/json
```

```json
{
  "status": "completed",
  "checksum": "sha256...",
  "file_size": 123456
}
```

When a `preview` asset is completed, cloud updates `preview_path`. When a `frame` or `source` asset is completed, cloud updates `source_path`.

## Print Request Rules

Cloud print request is only a request, not direct printing.

Flow:

1. Customer creates print request in cloud.
2. Cloud stores request as `pending`.
3. Station polls cloud for pending requests.
4. Station accepts request.
5. Station creates local print order.
6. Station prints locally.
7. Station updates cloud status.

Statuses:

- `pending`
- `accepted`
- `printing`
- `completed`
- `failed`
- `cancelled`

Cloud must show station offline or pending state when a request has not been accepted.

## Error Handling and Retry

Station sync must be resilient.

Required:

- Store sync status locally.
- Store last sync error.
- Retry failed sync jobs with backoff.
- Do not block local station operations if cloud sync fails.
- Log all cloud sync attempts.
- Provide an admin UI indicator for sync status.

## Local Operation Priority

Station must continue working offline.

Rules:

- Capture must not depend on cloud availability.
- Manual payment approval must not depend on cloud availability.
- Local printing must not depend on cloud availability except for cloud-origin print requests.
- Failed cloud sync should be retried later.

## LAN Station Rule

Photobooth station is usually inside a local area network, mini PC, or Android device without a stable public endpoint.

Rules:

- Cloud must not pull data from station.
- Cloud must not require station IP, station URL, or port forwarding.
- Station must push data to cloud when online.
- Station must poll cloud for work such as print requests.
- Station must retry failed sync jobs locally.

Applied flows:

- Session sync: station pushes metadata to cloud.
- Asset sync: station uploads files to cloud.
- Template marketplace: station publishes template metadata and asset files to cloud.
- Print request: station polls cloud, prints locally, then updates status to cloud.

This keeps station offline-first and avoids exposing local network devices to the internet.
