import { useCommandPalette } from '@/hooks/use-command-palette';

export function CommandBar() {
    const palette = useCommandPalette();

    return (
        <button
            type="button"
            aria-label="Search or run a command"
            onClick={palette.open}
            className="flex h-9 flex-[1_1_302px] items-center gap-2 rounded-button border border-line bg-field px-2.5 text-left transition-colors hover:border-line-hover"
        >
            <span className="font-mono text-[12px] text-accent">›</span>
            <span className="min-w-0 flex-1 truncate font-mono text-[11.5px] text-faint">
                search or run a command…
            </span>
            <span className="rounded-chip border border-line-strong px-[5px] py-px font-mono text-xs text-muted">
                ⌘K
            </span>
        </button>
    );
}
