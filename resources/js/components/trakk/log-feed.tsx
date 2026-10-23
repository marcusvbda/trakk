import { cn } from '@/lib/utils';
import type { LogEntry, LogKind } from '@/types';

const symbols: Record<LogKind, { glyph: string; className: string }> = {
    done: { glyph: '✓', className: 'text-accent' },
    undone: { glyph: '×', className: 'text-danger' },
    sent: { glyph: '→', className: 'text-info' },
    checkin: { glyph: '◆', className: 'text-info' },
    warning: { glyph: '!', className: 'text-warning' },
    reopened: { glyph: '↺', className: 'text-info' },
};

const trailingClass: Partial<Record<LogKind, string>> = {
    done: 'text-accent',
    undone: 'text-danger',
};

type LogFeedProps = {
    entries: LogEntry[];
    timeColumn: string;
    showTrailing?: boolean;
};

export function LogFeed({
    entries,
    timeColumn,
    showTrailing = false,
}: LogFeedProps) {
    return (
        <div
            aria-live="polite"
            className="flex flex-col gap-px font-mono text-[11px] leading-[1.75]"
        >
            {entries.map((entry) => (
                <div
                    key={entry.id}
                    className={cn(
                        'grid animate-fade-in items-baseline gap-1.5',
                        timeColumn,
                    )}
                >
                    <span className="whitespace-nowrap text-faint">
                        {entry.timeLabel}
                    </span>
                    <span
                        aria-hidden="true"
                        className={symbols[entry.kind].className}
                    >
                        {symbols[entry.kind].glyph}
                    </span>
                    <span className="truncate text-log">{entry.text}</span>
                    {showTrailing && (
                        <span
                            className={cn(
                                'text-muted',
                                trailingClass[entry.kind],
                            )}
                        >
                            {entry.trailing}
                        </span>
                    )}
                </div>
            ))}
        </div>
    );
}
