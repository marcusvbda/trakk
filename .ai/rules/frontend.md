---
paths:
  - "resources/js/**"
  - "resources/css/**"
---

# Frontend (Inertia 3 + React 19 + TypeScript + Tailwind 4)

## Design

- The design is fixed and must match `docs/DESIGN.md` and the prototypes in `docs/design/prototype/`. Read DESIGN.md before touching any Trakk screen.
- Colors, fonts, sizes, radii and animations come only from the `@theme` tokens in `resources/css/app.css`. No hex values in components, no default Tailwind palette (`gray-*`, `zinc-*`, `emerald-*`), no arbitrary colors.
- Arbitrary values are allowed only for exact sizes from DESIGN.md (e.g. `basis-[440px]`, `size-[13px]`).
- No shadows, gradients, blur, rounded corners above 6px, or font weights above 500.
- The page container (`TrakkLayout`, `main`) has no `max-w-*`: content fills the full viewport width. Never reintroduce `max-w-[1440px]` or similar, even though the prototypes have it (owner decision).
- Starter-kit pages (auth, settings) may keep shadcn/ui components, restyled to the tokens. Trakk screens use `components/ui` and `components/trakk`.

## Structure

- Every authenticated Trakk page gets the top bar (logo, `~/trakk`, version, date, live clock, year progress) from `TrakkLayout`; pages never render their own copy. Page controls go into it with `<TopBarSlot>`. The data is the shared Inertia prop `topBar`, not a per-page prop.
- Pages in `resources/js/pages/` (lowercase, kebab-case files, matching `Inertia::render` names).
- Domain components in `resources/js/components/trakk/`, primitives in `resources/js/components/ui/`, one component per file, kebab-case file names, PascalCase exports.
- Types for every page prop in `resources/js/types/`, mirroring the Laravel Resources.

## Components

- Function components, typed props, no `any`. Small and presentational: data in through props, events out through callbacks.
- No business calculations in React. Pace, points, streaks, KPIs arrive computed. Formatting helpers live in `resources/js/lib/format.ts`.
- Lists use stable keys from ids.
- Accessible by default: real `<button>`, `<a>`, `<input>` + `<label>`; `aria-pressed` on toggles and checkable rows; `aria-label` on icon-only buttons; `role="img"` + `aria-label` on charts.

## Data and mutations

- Data comes from Inertia page props. No `fetch`/axios calls for page data, no client cache libraries.
- Mutations with Inertia (`router.post/patch/delete` or `<Form>`/`useForm`), `preserveScroll: true`, partial reloads (`only: [...]`), and optimistic updates for check actions and kanban moves.
- URLs only through Wayfinder route functions.

## Style

- Prettier and ESLint from the starter kit decide formatting. `npm run types` must pass.
- No comments that restate JSX. Extract a component instead of commenting a block.
