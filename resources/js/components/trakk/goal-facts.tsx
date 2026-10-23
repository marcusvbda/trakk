import type { ReactNode } from 'react';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { GoalDetail } from '@/types';

function Fact({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex min-w-0 flex-col gap-1 bg-surface px-3 py-2.5">
            <dt className="font-mono text-2xs tracking-[0.12em] text-muted uppercase">
                {label}
            </dt>
            <dd className="flex items-center gap-1.5 font-mono text-base text-fg">
                {children}
            </dd>
        </div>
    );
}

export function GoalFacts({ goal }: { goal: GoalDetail }) {
    return (
        <dl className="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-px overflow-hidden rounded-panel border border-divider bg-divider">
            <Fact label="start">{goal.facts.start}</Fact>
            <Fact label="due date">{goal.facts.dueDate}</Fact>
            <Fact label="remaining">{goal.facts.remainingDays} days</Fact>
            <Fact label="type">{goal.type}</Fact>
            <Fact label="priority">{goal.priority}</Fact>
            <Fact label="frequency">{goal.facts.frequency ?? '—'}</Fact>
            <Fact label="color">
                <span
                    className={cn(
                        'size-2.5 rounded-[2px]',
                        goalColorClass[goal.color].bg,
                    )}
                />
                {goal.colorHex}
            </Fact>
            <Fact label="overdue">{goal.facts.overdueCount}</Fact>
        </dl>
    );
}
