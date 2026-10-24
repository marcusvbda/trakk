import { formatLate, formatPoints } from '@/lib/format';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import { TrakkCheckbox } from '@/components/ui/trakk-checkbox';
import type { TodayAction } from '@/types';

export function ActionRow({
    action,
    onToggle,
}: {
    action: TodayAction;
    onToggle: () => void;
}) {
    return (
        <button
            type="button"
            aria-pressed={action.isDone}
            onClick={onToggle}
            className="group flex min-h-11 w-full items-center gap-2.5 rounded-control px-2 text-left transition-colors duration-150 hover:bg-hover"
        >
            <TrakkCheckbox checked={action.isDone} />
            <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                <span
                    className={cn(
                        'truncate text-base',
                        action.isDone ? 'text-faint line-through' : 'text-body',
                    )}
                >
                    {action.title}
                </span>
                <span className="flex items-center gap-1.5 font-mono text-xs text-muted">
                    <span
                        className={cn(
                            'size-[5px] rounded-full',
                            goalColorClass[action.goalColor].bg,
                        )}
                    />
                    {action.goalName}
                    {action.isDueToday && !action.isDone && (
                        <span className="tracking-[0.08em] text-warning">
                            · DUE TODAY
                        </span>
                    )}
                    {action.daysLate > 0 && !action.isDone && (
                        <span className="tracking-[0.08em] text-danger">
                            · LATE {formatLate(action.daysLate)}
                        </span>
                    )}
                </span>
            </span>
            <span className="w-11 flex-none text-right font-mono text-xs text-faint">
                {action.timeLabel}
            </span>
            <span
                className={cn(
                    'w-[26px] flex-none text-right font-mono text-[11px]',
                    action.isDone ? 'text-accent' : 'text-muted',
                )}
            >
                {formatPoints(action.points)}
            </span>
        </button>
    );
}
