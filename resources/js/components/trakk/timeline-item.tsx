import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { UpcomingItem } from '@/types';

export function TimelineItem({ item }: { item: UpcomingItem }) {
    return (
        <div className="grid min-h-11 grid-cols-[50px_10px_minmax(0,1fr)] gap-2">
            <div className="pt-px font-mono text-xs tracking-[0.06em] text-muted">
                {item.dayLabel}
            </div>
            <div className="relative">
                <span
                    className={cn(
                        'absolute top-1 left-px size-[7px] rounded-full border bg-surface',
                        item.goalColor
                            ? goalColorClass[item.goalColor].border
                            : 'border-muted',
                    )}
                />
                <span className="absolute top-[13px] bottom-0 left-1 w-px bg-line" />
            </div>
            <div className="min-w-0">
                <div className="truncate text-base text-body">{item.title}</div>
                <div className="font-mono text-xs text-muted">
                    {item.goalName}
                </div>
            </div>
        </div>
    );
}
