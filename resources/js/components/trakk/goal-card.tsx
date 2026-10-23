import { Link } from '@inertiajs/react';
import { PaceBadge } from '@/components/trakk/pace-badge';
import { WeekDots } from '@/components/trakk/week-dots';
import { Chip } from '@/components/ui/chip';
import { ProgressBar } from '@/components/ui/progress-bar';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import { show } from '@/routes/goals';
import type { GoalSummary, Priority } from '@/types';

const priorityTone = {
    high: 'warning',
    medium: 'secondary',
    low: 'muted',
} as const satisfies Record<Priority, string>;

export function GoalCard({ goal }: { goal: GoalSummary }) {
    const color = goalColorClass[goal.color];

    return (
        <article className="relative flex min-w-0 animate-fade-in flex-col gap-[9px] overflow-hidden rounded-panel border border-line bg-surface px-3.5 pt-[13px] pb-3 transition-colors duration-200 hover:border-line-hover">
            <div className={cn('absolute inset-x-0 top-0 h-px', color.bg)} />
            <div className="flex items-center gap-2">
                <span className={cn('size-[7px] rounded-[2px]', color.bg)} />
                <span className="font-mono text-2xs tracking-[0.12em] text-muted uppercase">
                    {goal.type}
                </span>
                <Chip tone={priorityTone[goal.priority]} className="ml-auto">
                    {goal.priority}
                </Chip>
            </div>
            <h3 className="text-lg leading-[1.3] font-normal text-fg">
                <Link
                    href={show({ goal: goal.slug })}
                    className="after:absolute after:inset-0"
                >
                    {goal.title}
                </Link>
            </h3>
            <div className="font-mono text-xs text-muted">
                {goal.startLabel} → {goal.dueLabel} · {goal.daysLeft}d left ·{' '}
                {goal.frequencyLabel}
            </div>
            <div className="flex items-baseline justify-between gap-2">
                <div className="flex items-baseline gap-1.5">
                    <span className="font-mono text-[22px] leading-none font-light text-fg">
                        {goal.percent}
                        <span className="text-[11px] text-faint">%</span>
                    </span>
                    <span className="font-mono text-xs text-muted">
                        expected {goal.expectedPercent}%
                    </span>
                </div>
                <PaceBadge pace={goal.pace} className="text-xs" />
            </div>
            <ProgressBar
                percent={goal.percent}
                expectedPercent={goal.expectedPercent}
                fillClassName={color.bg}
            />
            <div className="flex min-h-5 items-center justify-between gap-2 pt-0.5">
                {goal.week && <WeekDots week={goal.week} color={goal.color} />}
                {goal.progressLabel && (
                    <span className="font-mono text-xs text-muted">
                        {goal.progressLabel}
                    </span>
                )}
                {goal.hasBoard && (
                    <Link
                        href={show({ goal: goal.slug })}
                        className="relative font-mono text-sm text-accent-soft hover:text-accent-hover"
                    >
                        open board →
                    </Link>
                )}
            </div>
        </article>
    );
}
