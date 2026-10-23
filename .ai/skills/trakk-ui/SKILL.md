---
name: trakk-ui
description: Implement or change a Trakk screen or component so it matches the approved dark terminal design pixel for pixel. Use for any work in resources/js pages or components, or resources/css.
---

# Build Trakk UI that matches the design

## 1. Load the reference

- Read `docs/DESIGN.md` fully (tokens, typography, layout, component specs).
- Open the matching prototype in `docs/design/prototype/` (`dashboard.html` or `goal.html`) and find the section you are building. Read its exact inline styles: sizes, gaps, paddings, colors, borders.
- Look at the matching reference image in `docs/design/reference/` (desktop and mobile).

## 2. Map to tokens

- Translate every value to a token utility (`bg-surface`, `border-line`, `text-muted`, `font-mono`, `text-2xs`, `rounded-panel`).
- If a value has no token, it is either a layout size (use an arbitrary value like `basis-[440px]`) or a missing token (stop and ask before adding one to `@theme`).

## 3. Build

- Reuse components from `resources/js/components/ui` and `components/trakk` before creating new ones. New components follow the spec table in DESIGN.md.
- Keep components presentational. Numbers arrive computed from the backend.
- Seed realistic data with `php artisan migrate:fresh --seed` (DemoSeeder mirrors the prototype).
- Wire interactions with Inertia (optimistic check, kanban move, filters, toggles), keyboard accessible, `aria-*` as in DESIGN.md.
- Add the motion from DESIGN.md (live dot pulse, cursor blink, fade-in for new rows, width transitions) and respect `prefers-reduced-motion`.

## 4. Verify

- Run `composer run dev`, open the page at 1440px and 390px wide and compare with the reference PNGs side by side: spacing, font sizes and weights, colors, borders, alignment, truncation.
- Check hover, focus-visible and empty states.
- `npm run types` and `npm run lint` must pass.
- List any deliberate deviation from the reference in your summary and why.
