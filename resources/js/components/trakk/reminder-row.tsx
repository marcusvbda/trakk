import { TrakkToggle } from '@/components/ui/trakk-toggle';
import { cn } from '@/lib/utils';
import type { ReminderItem } from '@/types';

export function ReminderRow({
    reminder,
    onToggle,
    onOpen,
}: {
    reminder: ReminderItem;
    onToggle: () => void;
    onOpen: () => void;
}) {
    return (
        <div className="flex min-h-11 items-center gap-2.5 rounded-control px-1.5 transition-colors duration-150 hover:bg-hover">
            <button
                type="button"
                aria-label={`Edit reminder "${reminder.label}"`}
                onClick={onOpen}
                className="flex min-h-11 min-w-0 flex-1 items-center gap-2.5 text-left"
            >
                <span
                    className={cn(
                        'w-[42px] flex-none font-mono text-md font-light',
                        reminder.isActive ? 'text-fg' : 'text-faint',
                    )}
                >
                    {reminder.timeLabel}
                </span>
                <div className="min-w-0 flex-1">
                    <div
                        className={cn(
                            'truncate text-base',
                            reminder.isActive ? 'text-body' : 'text-muted',
                        )}
                    >
                        {reminder.label}
                    </div>
                    <div className="font-mono text-xs text-muted">
                        {reminder.frequencyLabel} · {reminder.channelsLabel}
                    </div>
                </div>
            </button>
            <TrakkToggle
                pressed={reminder.isActive}
                label={`${reminder.isActive ? 'Pause' : 'Enable'} reminder: ${reminder.label}`}
                onToggle={onToggle}
            />
        </div>
    );
}
