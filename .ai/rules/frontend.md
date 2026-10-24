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
- No screen uses shadcn/ui default styling. Every authenticated page, settings included, is a Trakk screen on `TrakkLayout` built from `components/ui` and `components/trakk` (owner decision). Reach account pages from the `UserMenu`, never only by URL.

## Structure

- Every authenticated Trakk page gets the top bar (logo, `~/trakk`, version, date, live clock, year progress) from `TrakkLayout`; pages never render their own copy. The logo and `~/trakk` are always one link to the dashboard (owner decision). Page controls go into it with `<TopBarSlot>`. The account dropdown with log out (`UserMenu`) is part of it; never add another logout control. The data is the shared Inertia prop `topBar`, not a per-page prop.
- App name and version are never hard-coded in components or copy. They come from `config('app.name')` (`APP_NAME`, default `Trakk`) and `config('trakk.version')` (`APP_VERSION`), shared as the Inertia props `name` and `version`; read them with `usePage().props`. The brand mark is the `LogoMark` diamond in the accent green, and `public/favicon.svg`, `favicon.ico` and `apple-touch-icon.png` reproduce it. Owner decision.
- No blinking cursor, caret or underscore anywhere (no `animate-blink`, no `_` after logs or loading text). A blink only exists in a real, typeable input, drawn by the browser. Owner decision.
- Mobile never zooms, manually or automatically (owner decision). Never remove `maximum-scale=1, user-scalable=no` from the viewport meta (`app.blade.php`, `mcp/authorize.blade.php`) or the `pointer: coarse` rule in `app.css` that sets `input, textarea, select` to 16px: iOS Safari zooms into any focused field below 16px, so a smaller font on a field is only allowed on pointer devices.
- Sign-in always sends `remember: true` (login form state) and `SESSION_LIFETIME` defaults to 43200 minutes, so the owner stays signed in until logging out or losing cookies (owner decision). Never remove the remember field from `auth/login.tsx` or lower the session default.
- Habit goals show two progresses in the one Progress card (total and weekly score); never add a card for the weekly score. The weekday chips only show the schedule; each scheduled day has its own checkbox beside them, stored on that slot's date even when ticked later in the week, future slots locked. Never make the chips themselves toggles, and never use a single checkbox per action (owner decision).
- Everything clickable has `cursor: pointer`. The base layer in `app.css` covers `button`, `a[href]`, `[role=button]`, `[role=menuitem]`, `summary` and `select`; a clickable element outside those needs `role="button"` or `cursor-pointer`. Never remove that base rule. Owner decision.
- Create and edit sheets (goal, task, reminder) are owned by `SheetHost` in `TrakkLayout`. A page opens one with `useSheets().open(...)` and never mounts its own copy. Never ship a button that does nothing: build its sheet or leave the button out.
- Pages in `resources/js/pages/` (lowercase, kebab-case files, matching `Inertia::render` names).
- Domain components in `resources/js/components/trakk/`, primitives in `resources/js/components/ui/`, one component per file, kebab-case file names, PascalCase exports.
- Types for every page prop in `resources/js/types/`, mirroring the Laravel Resources.

## Toasts

- Operation feedback is a toast: the server flashes it (`Toast::success`) and `use-flash-toast.ts` shows it through the one `Toaster` in `components/ui/sonner.tsx`. Pages never call `toast()` for a server operation. A failed request (`httpException`, `networkError`) toasts an error by itself. Owner decision.
- Toasts use the Trakk tokens: `surface` background, `line` border, 6px radius, no shadow, mono 11px, a glyph in mono (`✓` accent, `×` danger, `!` warning, `›` info).

## Components

- Owner rule: no component is ever blank. Every list, panel, chart, kanban column, sheet section and the command palette renders `EmptyState` (or its empty chart/grid structure with a caption) when it has no data, with the copy from `docs/specs/real-features.md` → Empty states. Never hide a panel for lack of data.
- Function components, typed props, no `any`. Small and presentational: data in through props, events out through callbacks.
- No business calculations in React. Pace, points, streaks, KPIs arrive computed. Formatting helpers live in `resources/js/lib/format.ts`.
- Kanban cards move by native HTML5 drag and drop between columns, never with per-card arrow buttons (owner decision). No drag-and-drop package. The task sheet's status field is the keyboard and touch path.
- Lists use stable keys from ids.
- Accessible by default: real `<button>`, `<a>`, `<input>` + `<label>`; `aria-pressed` on toggles and checkable rows; `aria-label` on icon-only buttons; `role="img"` + `aria-label` on charts.

## Data and mutations

- Data comes from Inertia page props. No `fetch`/axios calls for page data, no client cache libraries.
- Mutations with Inertia (`router.post/patch/delete` or `<Form>`/`useForm`), `preserveScroll: true`, partial reloads (`only: [...]`), and optimistic updates for check actions and kanban moves.
- URLs only through Wayfinder route functions.

## Style

- Prettier and ESLint from the starter kit decide formatting. `npm run types` must pass.
- No comments that restate JSX. Extract a component instead of commenting a block.
