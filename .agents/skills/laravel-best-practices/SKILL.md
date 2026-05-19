---
name: laravel-best-practices
description: Use when editing Laravel backend code in this project, including routes, controllers, models, migrations, validation, queues, auth, Sanctum, tenant scoping, tests, database performance, and framework conventions. Pair with dafydio-cloud for product boundaries and tenant/station/customer rules.
---

# Laravel Best Practices

## Project Context
This is a Laravel 13 SaaS app for Dafydio Photobooth Cloud.

Always combine these Laravel rules with:
- `.agents/skills/dafydio-cloud/SKILL.md`
- `AGENTS.md`
- `PROGRESS.md`

## Core Rules
- Use Laravel conventions before custom abstractions.
- Keep cloud/station boundaries intact: cloud coordinates, station captures and prints.
- Scope domain data by `tenant_id`.
- Use MySQL-compatible migrations and explicit short index names.
- Use database queue/cache unless a feature explicitly requires a different driver.
- Use Sanctum for customer API token auth.
- Use session guard `web` for admin/tenant UI.
- Keep station token auth separate from customer Sanctum tokens.

## Backend Patterns
- Keep API controllers under `app/Http/Controllers/Api`.
- Keep admin web controllers under `app/Http/Controllers/Admin`.
- Keep models tenant-aware when they represent tenant-owned data.
- Prefer Form Request classes when validation grows beyond small controller actions.
- Keep JSON responses in `{ data, meta, message }` shape for API endpoints.
- Use policies or explicit tenant checks before exposing tenant-owned records.

## Tests and Verification
When changing backend behavior, add or update feature tests for:
- authentication and authorization
- tenant scoping
- station sync
- customer portal API
- admin workflows

Run:
- `vendor/bin/pint --dirty`
- `php artisan test`
- `php artisan route:list --except-vendor` after route changes

## References
Load specific rule files only when needed:
- `rules/migrations.md` for migration changes.
- `rules/routing.md` for route/controller changes.
- `rules/eloquent.md` for model/query changes.
- `rules/security.md` for auth, tokens, and authorization.
- `rules/testing.md` for tests.
- `rules/queue-jobs.md` for queue work.
- `rules/db-performance.md` for indexes and heavy queries.
- `rules/validation.md` for validation and request data.
