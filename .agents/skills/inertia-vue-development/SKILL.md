---
name: inertia-vue-development
description: Use when editing Inertia Laravel + Vue 3 pages, layouts, forms, client-side navigation, state, API calls, customer/admin UI flows, and Vite frontend behavior in this Dafydio Cloud project. Pair with dafydio-cloud and tailwindcss-development for product and UI rules.
---

# Inertia Vue Development

## Project Context
This project uses Laravel 13, Inertia Laravel, Vue 3, Vite, and `@inertiajs/vue3`.

Read `.agents/skills/dafydio-cloud/SKILL.md` first for route areas, auth boundaries, and UI direction.

## Route Areas
- `/login` is the unified login UI with Customer/Admin modes.
- `/customer/*` is the customer portal after Sanctum token login.
- `/admin/*` is the admin/tenant area after Laravel session login.
- Keep customer and admin code visually consistent but behaviorally separate.

## Page Organization
- Put pages under `resources/js/Pages`.
- Customer pages go under `resources/js/Pages/Customer`.
- Admin pages go under `resources/js/Pages/Admin`.
- Shared login pages go under `resources/js/Pages/Auth`.

## Inertia Rules
- Use `useForm` for Inertia-backed web forms such as admin login/logout.
- Use `window.axios` for token-based customer API calls when using Sanctum bearer tokens.
- Preserve redirects:
  - customer login success -> `/customer/dashboard`
  - admin login success -> `/admin`
  - invalid customer token -> `/login`
- Do not mix admin session state with customer local token state.

## Vue Rules
- Use Composition API with `<script setup>`.
- Keep component state small and explicit.
- Use computed values for derived UI text and session lists.
- Keep customer mobile interactions touch-friendly.
- Keep admin controls discoverable and operational.

## Verification
Run after frontend changes:
- `npm run build`
- `php artisan test`
- `php artisan route:list --except-vendor` if route behavior changed
