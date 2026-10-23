import type { StatusLine } from '@/types';

export function StatusFooter({
    status,
    pointsLeft,
}: {
    status: StatusLine;
    pointsLeft: number;
}) {
    return (
        <footer className="flex flex-wrap justify-between gap-x-4 gap-y-2 border-t border-divider pt-2.5 font-mono text-xs text-faint">
            <span>
                <span className="text-accent">●</span> sync ok · postgres · mcp{' '}
                {status.mcpTools} tools · telegram bot active
            </span>
            <span>
                {pointsLeft > 0
                    ? `next reminder ${status.nextReminderAt} · ${pointsLeft} pts to daily target`
                    : `daily target hit · next reminder ${status.nextReminderAt}`}
            </span>
        </footer>
    );
}
