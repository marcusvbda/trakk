import { formatLate } from '@/lib/format';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { OverdueItem } from '@/types';

export function OverdueRow({ item }: { item: OverdueItem }) {
    return (
        <div className="flex min-h-11 animate-fade-in items-center gap-2.5 rounded-control px-2 transition-colors duration-150 hover:bg-hover">
            <span
                className={cn(
                    'size-1.5 flex-none rounded-full',
                    goalColorClass[item.goalColor].bg,
                )}
            />
            <div className="min-w-0 flex-1">
                <div className="truncate text-base text-body">{item.title}</div>
                <div className="font-mono text-xs text-muted">
                    {item.goalName} · due {item.dueLabel}
                </div>
            </div>
            <span className="flex-none font-mono text-[11px] text-danger">
                {formatLate(item.daysLate)}
            </span>
        </div>
    );
}
