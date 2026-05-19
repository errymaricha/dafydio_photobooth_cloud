---
name: dafydio-cloud
description: Use when working in the Dafydio Photobooth Cloud Laravel/Inertia project, especially for SaaS cloud architecture, station sync boundaries, customer/admin auth, mobile-first customer UI, admin operational UI, MySQL/database queue deployment, API contracts, data model changes, or progress documentation.
---

# Dafydio Cloud Skill

## First Steps
Before editing code, read these files when relevant:
- `AGENTS.md` for project rules and product boundaries.
- `PROGRESS.md` for current status, decisions, blockers, and completed work.
- `ARCHITECTURE.md` for module, queue, storage, and security direction.
- `DATA_MODEL.md` for tables, relationships, and status values.
- `API_CONTRACT.md` for station/customer/admin API expectations.

Always update `PROGRESS.md` after meaningful changes.

## Companion Skills
Use these local skills when the task touches their layer:
- `.agents/skills/laravel-best-practices/SKILL.md` for Laravel backend, database, auth, routing, validation, and tests.
- `.agents/skills/inertia-vue-development/SKILL.md` for Inertia/Vue pages, forms, client state, and frontend auth flows.
- `.agents/skills/tailwindcss-development/SKILL.md` for Tailwind classes, responsive layout, and Dafydio visual consistency.

## Project Boundaries
- Treat this repo as `dafydio_photobooth_cloud`, not the station app.
- Cloud stores archive data, manages SaaS billing, customer portal, marketplace, editor, and print request coordination.
- Cloud must not directly capture photos or control printers.
- Station remains responsible for Android capture, local session flow, local render when needed, printer queue, physical print, asset sync, and polling print requests.

## Stack
- Laravel 13
- Inertia Laravel
- Vue 3 via `@inertiajs/vue3`
- MySQL
- Database queue/cache for hosting compatibility
- S3/R2 compatible storage
- Sanctum for customer API tokens

## Auth Rules
- Public login URL is `/login`.
- `/login` contains both Customer and Admin modes.
- Customer login uses WhatsApp + station-created password and receives a Sanctum personal access token.
- Customer API routes that need auth use `auth:sanctum`.
- Customer token is separate from station token.
- Admin login uses Laravel session guard `web`.
- Admin and customer sessions/tokens must not be mixed.
- Station API uses a hashed station token stored on `stations.api_token_hash`.

## Route Areas
- `/customer/*` is the customer portal area after login.
- `/admin/*` is tenant/admin operations after login.
- Keep these areas separate for authorization and workflow even when the visual template is shared.
- Legacy `/admin/login` and `/customer/login` should redirect to `/login` with a mode query.

## UI Rules
- Customer UI is mobile-first because customers usually enter from WhatsApp on phones.
- Customer portal must prioritize session history, gallery/assets, download, edit, subscription, and print request actions.
- Admin UI can be denser and menu-rich, but must share the Dafydio visual language.
- Keep admin access to tenant, station, customer, session archive, asset, template, print request, billing, subscription, sync log, and settings discoverable.
- Use the established Dafydio visual language: bright surface, primary blue `#004ac6`, white rounded-xl cards, soft borders, large touch targets.
- Do not add decorative orb/bokeh backgrounds.

## Multi-Tenancy
- Use single database multi-tenancy with `tenant_id`.
- Tenant represents photobooth owner/studio/vendor.
- Station, customer, sessions, assets, print requests, templates, entitlements, payments, and sync logs must be tenant-scoped.
- Admin actions must only operate within the logged-in user's tenant unless explicitly implementing platform-admin behavior.

## Implementation Workflow
- Prefer existing patterns in `app/Http/Controllers`, `app/Models`, `routes`, and `resources/js/Pages`.
- Keep API JSON responses in `{ data, meta, message }` shape where applicable.
- Add/adjust feature tests for auth, tenant scope, station sync, and customer API behavior when changing those paths.
- Run relevant checks after code changes:
  - `vendor/bin/pint --dirty`
  - `php artisan test`
  - `npm run build` for frontend changes
  - `php artisan route:list --except-vendor` for route changes

## Documentation Workflow
Update `PROGRESS.md` with:
- What changed.
- Files or areas touched.
- Important decisions.
- Verification commands and results.
- Blockers or next steps.
