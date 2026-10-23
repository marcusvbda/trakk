# Trakk — Design System

The approved design is a dense, dark, terminal-flavoured dashboard: thin 1px lines, small type, light font weights, many compact cards, almost no imagery, and small motions that make it feel alive. **Implementations must match it exactly.** When this document and the prototype disagree, the prototype wins; fix this document. The one standing exception is the page width: the prototypes cap the page at 1440px, the app does not (see Layout).

## Sources of truth

| What | Where | Use it for |
| --- | --- | --- |
| Interactive prototypes | `docs/design/prototype/dashboard.html`, `goal.html`, `login.html` | Exact values. Open in a browser, inspect any element, copy sizes, colors, spacing. Clicks work (check actions, move cards, filter goals, toggle reminders, sign in states). |
| Reference screenshots | `docs/design/reference/*.png` (desktop 1440px and mobile 390px, @2x) | Visual comparison after implementing a screen. |
| Tokens | `## Tokens` below, ready to paste into `resources/css/app.css` | Every color, font, size, radius. |

The screenshots were rendered with a JetBrains Mono fallback; the prototypes load the real fonts. Trust the prototypes for typography.

The prototypes are reference material only. Never copy their markup or runtime into the app. Rebuild with React components and Tailwind utilities.

## Principles

1. **Density over whitespace.** Panels sit 10–14px apart, text is 10–13px. Show more, explain less.
2. **Lines, not fills.** Separation comes from 1px borders on near-black surfaces. No shadows, no gradients, no glassmorphism.
3. **Mono for data, sans for language.** Numbers, labels, dates, metadata and status use JetBrains Mono. Titles and human sentences use Inter Light.
4. **Color carries meaning.** Neutral greys everywhere; color only for the accent (done/positive), the status colors (behind, watch, info) and each goal's own color.
5. **Quiet motion.** Pulsing live dots, a blinking cursor, bars that grow, rows that fade in. Nothing bounces or slides.
6. **Dark only.** There is no light theme.

## Tokens

Paste into `resources/css/app.css` (Tailwind v4, CSS-first config). Use the generated utilities (`bg-surface`, `border-line`, `text-muted`, `font-mono`, `text-2xs`, `rounded-panel`). Never hardcode a hex value in a component.

```css
@theme {
  --font-sans: "Inter Variable", "Inter", ui-sans-serif, system-ui, sans-serif; /* fontsource registers the variable font as "Inter Variable" */
  --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, monospace;

  /* surfaces */
  --color-canvas: #0A0C0D;      /* page background */
  --color-surface: #0F1214;     /* panels, cards */
  --color-sunken: #0C0F10;      /* log panel, kanban columns */
  --color-raised: #12161A;      /* kanban cards */
  --color-field: #0E1113;       /* inputs, command bar */
  --color-hover: #13181B;       /* row hover */
  --color-selected: #161B1E;    /* active tab / chip */

  /* lines */
  --color-divider: #1A1F22;     /* header rules inside panels, track backgrounds */
  --color-line: #1F2529;        /* panel and card borders */
  --color-line-subtle: #22282C; /* version chip, low-priority chip */
  --color-line-strong: #2A3135; /* buttons, dashed boxes, toggles off */
  --color-line-hover: #2E373C;  /* card border on hover */
  --color-dim: #3A4348;         /* unchecked box, neutral bars, marker lines */

  /* text */
  --color-fg-strong: #EEF2F3;   /* page title, big numbers on detail page */
  --color-fg: #E6ECEE;          /* KPI values, card titles, emphasis */
  --color-body: #D5DBDE;        /* default text, list item titles */
  --color-log: #C3CBCF;         /* log lines */
  --color-fg-2: #B7C0C4;        /* panel headings, secondary buttons */
  --color-muted: #8B969D;       /* labels, metadata */
  --color-faint: #7F8A91;       /* timestamps, suffixes, hints (still passes 4.5:1) */

  /* accent and status */
  --color-accent: #4ADE9A;      /* done, positive, live, primary action */
  --color-accent-soft: #9FE7C4; /* links */
  --color-accent-hover: #C8F5DF;
  --color-danger: #FF6B6B;      /* overdue, behind */
  --color-warning: #F2B03D;     /* watch, due today, high priority */
  --color-info: #6CB4FF;        /* neutral events, info */
  --color-danger-line: #4A2626; /* border of an overdue kanban card */
  --color-warning-line: #4A3B1C;/* border of the high-priority chip */

  /* charts */
  --color-bar-good: #2B6E50;    /* day >= 70% of target */
  --color-bar-low: #3A4248;     /* day below 70% */
  --color-heat-0: #161B1E;
  --color-heat-1: #173A2C;
  --color-heat-2: #1F6145;
  --color-heat-3: #2F9163;
  --color-heat-4: #4ADE9A;

  /* goal palette (goals.color must be one of these) */
  --color-goal-amber: #F2B03D;
  --color-goal-blue: #6CB4FF;
  --color-goal-cyan: #4FD1D9;
  --color-goal-pink: #F28CB1;
  --color-goal-violet: #B49BFF;
  --color-goal-sand: #D9D4C7;
  --color-goal-green: #4ADE9A;

  /* type scale */
  --text-3xs: 9px;
  --text-2xs: 9.5px;
  --text-xs: 10px;
  --text-sm: 10.5px;
  --text-base: 12.5px;   /* app default body size */
  --text-md: 13px;
  --text-lg: 14px;
  /* the prototypes use the font's own line height, not Tailwind's per-size default */
  --text-3xs--line-height: normal;
  --text-2xs--line-height: normal;
  --text-xs--line-height: normal;
  --text-sm--line-height: normal;
  --text-base--line-height: normal;
  --text-md--line-height: normal;
  --text-lg--line-height: normal;

  /* radius */
  --radius-chip: 3px;
  --radius-control: 4px;
  --radius-button: 5px;
  --radius-panel: 6px;

  /* motion */
  --animate-pulse-dot: pulse-dot 2.2s ease-in-out infinite;
  --animate-blink: blink 1.1s steps(1) infinite;
  --animate-fade-in: fade-in 0.45s ease-out;

  @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.25; } }
  @keyframes blink { 50% { opacity: 0; } }
  @keyframes fade-in { from { opacity: 0; transform: translateY(-3px); } to { opacity: 1; transform: none; } }
}

@layer base {
  html { color-scheme: dark; }
  body {
    @apply bg-canvas text-body font-sans font-light text-base;
    line-height: normal; /* the prototypes render without antialiased smoothing */
  }
  ::placeholder { color: var(--color-faint); }
  :focus-visible { outline: 1px solid var(--color-accent); outline-offset: 2px; }
}

@utility scanlines {
  background-image: repeating-linear-gradient(0deg, rgb(255 255 255 / 0.014) 0 1px, transparent 1px 3px);
}
```

Fonts are self-hosted (no CDN at runtime): `@fontsource-variable/inter` and `@fontsource/jetbrains-mono` (weights 300, 400, 500), imported in `resources/js/app.tsx`.

## Typography

| Role | Font | Size | Weight | Extra | Example |
| --- | --- | --- | --- | --- | --- |
| Page title | sans | 24px | 300 | tracking -0.01em, `fg-strong` | Digital nomad visa · Spain |
| Card title | sans | 14px (`lg`) | 400 | `fg` | Nomad visa · Spain |
| Body / list item | sans | 12.5px (`base`) | 300 | `body` | Book sworn translation |
| Paragraph | sans | 13px (`md`) | 300 | line-height 1.6 | Objective text |
| Panel heading | mono | 10.5px (`sm`) | 500 | uppercase, tracking 0.1em, `fg-2`; count after `·` in weight 400 `faint` | TODAY · 4/8 ACTIONS |
| Micro label | mono | 9.5–10px | 400 | uppercase, tracking 0.1–0.12em, `muted` | POINTS TODAY, HABIT |
| Metadata | mono | 10–10.5px | 400 | `muted` | 01 jul → 15 jan · 84d left |
| KPI value | mono | 26px | 300 | line-height 1, suffix 11.5px `faint` | 9/14 |
| Goal percent | mono | 22px | 300 | `%` at 11px `faint` | 45% |
| Detail percent | mono | 44px | 300 | | 45% |
| Log line | mono | 11px | 400 | line-height 1.75, `log` | 13:10 ✓ log meals · diet +2 |

Lowercase is the default voice for mono metadata ("due 24 oct", "on pace +2%"). Uppercase only for panel headings, micro labels and chips.

## Layout

- Page: `bg-canvas`, optional `scanlines` overlay, content `px-5`, vertical gap 14px between rows, 28px bottom padding. **The page container has no max-width: content always fills the full viewport width (owner decision). Never add `max-w-*` to the page container or `main`, even though the prototypes cap it at 1440px; this is a deliberate deviation from them.**
- Rows are flex containers with `flex-wrap` and `gap-3.5` (14px). Each panel has `flex: <grow> 1 <basis>` and `min-w-0`, so the layout stacks on mobile without breakpoints.
- Grids: KPI strip `grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-2.5`; goal cards `grid-cols-[repeat(auto-fill,minmax(min(100%,380px),1fr))] gap-2.5`.
- Panel: `bg-surface border border-line rounded-panel`, padding `12px 14px` (lists use `12px 10px 10px` so row hover reaches the edge). The heading row has `pb-2.5 mb-1 border-b border-divider`.
- Every clickable row and control is at least 44px tall.

### Dashboard (`/`), top to bottom

| Row | Content | Flex |
| --- | --- | --- |
| Top bar | Logo mark + `~/trakk` + version chip · date, live clock, year progress · command bar (flex 1, `⌘K` hint) · `mcp connected` pulse · `+ goal` · `check-in` (accent outline) | wraps |
| KPI strip | points today, streak, this week, goals on pace, overdue, on time · 30d | grid |
| Row 1 | Today (1.5, basis 440) · Overdue (1, 300) · Next 7 days (1, 300) | flex |
| Goals | Heading with counts + filter tabs (all / habit / project / off pace), then goal cards grid | grid |
| Row 2 | Points · 30 days (1.4, 420) · Consistency · 12 weeks (1, 300) · Reminders (1, 300) | flex |
| Row 3 | Focus project kanban (2, 560) · Log (1, 320) | flex |
| Footer | status line: sync, db, MCP tools, telegram bot · next reminder and points left today | wraps |

### Goal detail (`/goals/{goal}`)

Breadcrumb bar (`← dashboard / goals / nomad-visa`, synced dot, `edit`, `+ task`) · title row (color square, title, type chip, priority chip, pace status) · facts grid (`dl`, 1px gaps over `divider`) · row: Objective (1) · Pace chart (1.6) · Progress (0.8) · full-width Board (3 columns, horizontal scroll under 640px) · row: Reminders for this goal · History.

### Sign in (`/login`)

The only auth screen in the MVP (no registration, no password reset, no email verification). References: `login-desktop.png`, `login-error.png`, `login-mobile.png`.

- Page: full-height column on `canvas` with `scanlines`. Top bar (`border-b divider`): 16px logo mark, `~/trakk`, `auth` chip; right side `● system online` (pulse) and live clock. Footer bar (`border-t divider`): `● secure session · postgres · mcp ready` and `trakk`.
- Center column `max-w-[380px]`, gap 14px:
  1. Boot log, mono 11px, grid `66px 14px 1fr`: time (`faint`), symbol (`✓` accent, `›` info), text (`log`). Decorative: `aria-hidden`.
  2. Auth panel: `surface`, `border-line`, `rounded-panel`, padding `28px 28px 22px`. Corner brackets: four 12px L-shapes on the panel's corners, top-left and bottom-right in `accent`, the other two in `dim`.
  3. Panel header (`pb-5 mb-5 border-b divider`): 40px logo mark (outer diamond stroke 0.6 in the 18-unit viewBox, inner diamond filled `accent`), `Trakk` in sans 28px weight 300 tracking -0.02em `fg-strong`, tagline `goals · pace · focus` mono 10.5px tracking 0.06em `muted`.
  4. Form, gap 14px. Each field: micro label (`EMAIL`, `PASSWORD`) above a 44px `field` box with a `›` accent prompt, mono 12px input, border `line` → `line-hover` on hover → `accent` on focus-within → `danger-line` when invalid. Password box ends with a 56px `SHOW`/`HIDE` toggle separated by a `divider` line (`aria-pressed`).
  5. Error: `role="alert"`, 1px `danger-line` box, mono 10.5px `danger`, `× invalid credentials`. Server errors from Fortify are shown with this component (map the default message to `invalid credentials`; throttling to `too many attempts · retry in Ns`).
  6. Submit: full width, 44px, 1px `accent` border, mono 11.5px `accent` text `sign in →`; hover background `accent` at 8% opacity; while processing `authenticating_` with blinking underscore and `aria-busy`.
  7. Panel footer (`mt-[18px] pt-3 border-t divider`): `single-user instance` · `access is logged`, mono 10px `faint`.
  8. Below panel, centered: `v0.1 · tls · Europe/Dublin`.
- Logo mark SVG (use everywhere, sizes 16 / 18 / 40):

```html
<svg viewBox="0 0 18 18" aria-hidden="true">
  <path d="M9 1 L17 9 L9 17 L1 9 Z" fill="none" stroke="currentColor" stroke-width="1" />
  <path d="M9 5.5 L12.5 9 L9 12.5 L5.5 9 Z" fill="currentColor" />
</svg>
```

  Render it with `text-accent`. At 40px use `stroke-width="0.6"` so the line stays hairline.

## Components

Build these in `resources/js/components/trakk/`. Each one is small, typed and dumb (props in, callbacks out). Primitives (`Panel`, `PanelHeader`, `Chip`, `Button`, `Toggle`, `Checkbox`, `ProgressBar`) live in `resources/js/components/ui/`.

| Component | Spec |
| --- | --- |
| `Panel` / `PanelHeader` | Panel shell above. Header: mono heading left, optional meta or action right. |
| `KpiCard` | Micro label, value + suffix, 2px track (`divider`) with fill (optional), one mono sub-line colored by meaning. |
| `ActionRow` | Checkbox 14px (`dim` border, filled `accent` with dark check when done), title (strike-through + `faint` when done), meta line with the goal color dot (5px) + goal name + optional `· DUE TODAY` in `warning`, right-aligned time (44px column) and `+N` points (`accent` when done). Whole row is one `<button aria-pressed>`. |
| `OverdueRow` | Goal color dot 6px, title, `goal · due 20 oct`, right `−3d` in `danger`. |
| `TimelineItem` | Day label column 50px (`SAT 24`), hollow 7px ring in the goal color + 1px vertical line, title + goal. |
| `GoalCard` | 1px top line in the goal color, type label with a 7px color square, priority chip (high: `warning` text and `warning-line` border; medium: `fg-2`/`line-strong`; low: `muted`/`line-subtle`), title, dates meta, percent + `expected N%`, `PaceBadge`, `ProgressBar` with expected marker, footer: `WeekDots` (habit) or `N/M tasks` (project), optional `open board →` link. |
| `ProgressBar` | 3px track `divider`, fill in goal color, 1px × 9px marker in `body` at the expected percent. Width animates over 0.45s. |
| `PaceBadge` | `● behind -13%` (`danger`), `● watch -3%` (`warning`), `● on pace +2%` / `● ahead +6%` (`accent`). Thresholds in `docs/PRODUCT.md`. |
| `WeekDots` | 7 squares 9px, radius 2px: done = goal color; past miss = `#1B2023` fill; future = transparent with `line-strong` border. Then `4/5 this week`. |
| `PointsChart` | 30 flex bars, gap 3px, height 84px scaled to 20 pts, dashed `dim` target line labelled `target 14`. Bar colors: ≥ target `accent`, ≥ 70% `bar-good`, below `bar-low`. Stats row: average, best day, days ≥ target. |
| `ConsistencyHeatmap` | 12 week columns × 7 day rows, 13px cells, 3px gaps, `heat-0..4`, future cells transparent with `line-subtle` border, mon/wed/fri/sun labels, legend less → more. |
| `ReminderRow` | Time 13px mono 300, label + `freq · channel`, `Toggle` (26×14 track, 8px knob, accent when on) inside a 44px button. |
| `KanbanBoard` / `KanbanColumn` / `KanbanCard` | Columns `sunken` with `divider` border; header dot (to do `muted`, doing `warning`, done `accent`) + count. Card `raised`, title 12px, meta `N/M items · due 24 oct` (`danger` when overdue, plus `danger-line` border), optional tag (`critical`, `milestone`) in `warning`, 2px checklist progress, 28px square move button (`→` advance, `↺` reopen). Done cards are struck through and `faint`. |
| `LogFeed` | Mono grid `78px 14px 1fr auto`: time, symbol (`✓` accent, `→` info, `!` warning, `◆` info, `×` danger), text, points. New entries fade in. Ends with `›` and a blinking accent `_`. `aria-live="polite"`. |
| `CommandBar` | 34px tall `field` input with `›` prompt in accent and a `⌘K` key hint. Opens with ⌘K. |
| `LiveDot` | 5–6px accent circle with `animate-pulse-dot`. |
| `LogoMark` | The SVG above, `currentColor`, size prop (16 / 18 / 40). |
| `AuthLayout` | Top bar, centered 380px column, footer bar (sign-in screen spec). Replaces the starter kit's auth layouts. |
| `TextField` | Micro label + 44px `field` box with `›` prompt; states hover / focus / invalid; optional trailing action (password `SHOW`). |

Icons: inline stroke SVG or `lucide-react` at 14px with `strokeWidth={1.25}`. Never emoji. The symbols `✓ → ! ◆ × ↺ ● ■ ›` are text glyphs in mono, as in the prototype.

## Interaction and motion

- Checking an action: points, KPIs, chart bar for today, heatmap cell and log update immediately (optimistic), then the server confirms.
- Moving a kanban card: card moves column, progress and pace recompute, log gets a line.
- Row hover: background `hover` over 0.15s. Card hover: border `line-hover` over 0.2s.
- New list items: `animate-fade-in` once. Bars and progress fills: `transition-[width,height] duration-[450ms]`.
- Live clock ticks every second in the top bar only.
- `prefers-reduced-motion: reduce` disables all animation and transitions.

## Accessibility

- Real `<button>`, `<a>`, `<input>` + `<label>`; `aria-pressed` on toggles and checkable rows; `aria-label` on icon-only buttons (`Move "Sworn translation" to done`).
- Charts get `role="img"` and an `aria-label` that states the numbers.
- Text colors above pass 4.5:1 on `surface`; do not introduce greys darker than `faint` for text.
- Status is never color alone: it always has text (`behind -13%`, `overdue 22 oct`).

## Mobile

Same components; rows stack through `flex-wrap`. Kanban scrolls horizontally inside its panel. A dedicated mobile "Today" screen is a later milestone, not part of the MVP.

## Do / don't

- Do keep panels flat, 1px, 6px radius. Don't add shadows, gradients or blurred backgrounds.
- Do use mono for every number. Don't use font weights above 500.
- Do reuse the goal color for everything that belongs to that goal. Don't invent new hues.
- Don't use shadcn/ui default styling on Trakk screens. Starter-kit pages (auth, settings) may keep shadcn components, restyled with these tokens.
