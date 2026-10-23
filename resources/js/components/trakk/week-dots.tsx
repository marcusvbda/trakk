import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { DayState, GoalColor, WeekProgress } from '@/types';

const dayClass: Record<DayState, (color: GoalColor) => string> = {
    done: (color) =>
        `${goalColorClass[color].bg} ${goalColorClass[color].border}`,
    missed: () => 'border-divider bg-divider',
    upcoming: () => 'border-line-strong',
};

export function WeekDots({
    week,
    color,
}: {
    week: WeekProgress;
    color: GoalColor;
}) {
    return (
        <div
            role="img"
            aria-label={`${week.doneCount} of ${week.targetCount} days done this week`}
            className="flex items-center gap-1"
        >
            {week.days.map((day, index) => (
                <span
                    key={index}
                    className={cn(
                        'size-[9px] rounded-[2px] border',
                        dayClass[day](color),
                    )}
                />
            ))}
            <span className="ml-1.5 font-mono text-xs text-muted">
                {week.doneCount}/{week.targetCount} this week
            </span>
        </div>
    );
}
