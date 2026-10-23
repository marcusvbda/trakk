import type { StatusLine } from '@/types';

export function StatusFooter({
    status,
    pointsLeft,
}: {
    status: StatusLine;
    pointsLeft: number;
}) {
    const nextReminder = status.nextReminderAt
        ? `next reminder ${status.nextReminderAt}`
        : 'no reminders set';

    return (
        <footer className="flex flex-wrap justify-between gap-x-4 gap-y-2 border-t border-divider pt-2.5 font-mono text-xs text-faint">
            <span>
                <span className="text-accent">●</span> sync ok · postgres · mcp{' '}
                {status.mcpTools} tools ·{' '}
                {status.isTelegramActive
                    ? 'telegram bot active'
                    : 'telegram off · link in settings'}
            </span>
            <span>
                {pointsLeft > 0
                    ? `${nextReminder} · ${pointsLeft} pts to daily target`
                    : `daily target hit · ${nextReminder}`}
            </span>
        </footer>
    );
}
