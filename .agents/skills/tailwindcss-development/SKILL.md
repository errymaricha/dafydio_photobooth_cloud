---
name: tailwindcss-development
description: Use when editing Tailwind CSS classes, responsive UI, mobile-first customer portal screens, admin dashboard surfaces, Dafydio visual consistency, spacing, colors, cards, buttons, and layout behavior in this project.
---

# Tailwind CSS Development

## Dafydio Visual Language
Use the established Dafydio UI style:
- primary blue: `#004ac6`
- bright app surface: `#faf8ff`
- dashboard surface: `#F8FAFC`
- soft border: `#c3c6d7`
- subtle card fill: white
- soft container fill: `#f3f3fe`
- rounded cards: `rounded-xl`
- large touch targets: `min-h-11` or `min-h-12`

## Customer UI
- Build customer pages mobile-first.
- Prioritize session history, gallery/assets, download, edit, subscription, and print request actions.
- Keep bottom navigation for mobile portal screens when useful.
- Use concise labels and large touch targets.
- Desktop may show richer grid/sidebar layouts, but mobile must remain first-class.

## Admin UI
- Keep admin UI menu-rich and efficient.
- Use tables, grids, filters, tabs, and sidebars when they improve operations.
- Keep the same Dafydio visual language as customer pages.
- Do not make admin look like a separate product.

## Constraints
- Do not add CDN Tailwind scripts.
- Do not add remote Google Fonts or remote icon fonts in app pages.
- Do not use decorative orb, bokeh, or blob backgrounds.
- Avoid one-hue monotony: use Dafydio blue as primary, but support it with neutral surfaces and status colors.
- Ensure text does not overflow buttons/cards on mobile.
- Prefer stable layout dimensions for cards, buttons, nav, and grids.

## Verification
Run after UI changes:
- `npm run build`
- inspect affected Vue files for long class strings that hurt readability
- `php artisan test` when navigation/auth flows changed
