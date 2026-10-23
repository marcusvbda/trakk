import { Head, Link } from '@inertiajs/react';
import { EmptyState } from '@/components/trakk/empty-state';
import { TopBarSlot } from '@/components/trakk/top-bar-slot';
import { Chip } from '@/components/ui/chip';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { ProgressBar } from '@/components/ui/progress-bar';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show } from '@/routes/goals';
import type {
    GoalListGroup,
    GoalListItem,
    GoalsListProps,
    GoalStatus,
    Priority,
} from '@/types';

const priorityTone = {
    high: 'warning',
    medium: 'secondary',
    low: 'muted',
} as const satisfies Record<Priority, string>;

const statusTitle: Record<GoalStatus, string> = {
    active: 'Active',
    paused: 'Paused',
    done: 'Done',
    abandoned: 'Abandoned',
};

function GoalRow({ goal }: { goal: GoalListItem }) {
    return (
        <Link
            href={show(goal.slug)}
            className="flex min-h-11 flex-wrap items-center gap-x-3 gap-y-1 rounded-control px-1.5 py-1 transition-colors duration-150 hover:bg-hover"
        >
            <span
                className={cn(
                    'size-2.5 flex-none rounded-[2px]',
                    goalColorClass[goal.color].bg,
                )}
            />
            <span className="min-w-0 flex-[1_1_220px] truncate text-base text-body">
                {goal.title}
            </span>
            <span className="w-[62px] flex-none">
                <Chip>{goal.type}</Chip>
            </span>
            <span className="w-[66px] flex-none">
                <Chip tone={priorityTone[goal.priority]}>{goal.priority}</Chip>
            </span>
            <span className="w-[112px] flex-none font-mono text-xs text-muted">
                {goal.startLabel} → {goal.dueLabel}
            </span>
            <span className="flex w-[130px] flex-none items-center gap-2 font-mono text-xs text-muted">
                <ProgressBar
                    size="sm"
                    percent={goal.percent}
                    fillClassName={goalColorClass[goal.color].bg}
                    className="flex-1"
                />
                <span className="w-[34px] text-right text-fg">
                    {goal.percent}%
                </span>
            </span>
        </Link>
    );
}

function GoalGroup({ group }: { group: GoalListGroup }) {
    const id = `h-${group.status}`;

    return (
        <Panel variant="list" aria-labelledby={id} className="flex flex-col">
            <PanelHeader
                inset
                id={id}
                title={statusTitle[group.status]}
                count={group.goals.length}
            />
            {group.goals.length === 0 ? (
                <EmptyState size="compact">no {group.status} goals</EmptyState>
            ) : (
                group.goals.map((goal) => <GoalRow key={goal.id} goal={goal} />)
            )}
        </Panel>
    );
}

export default function GoalsIndex({ groups }: GoalsListProps) {
    const sheets = useSheets();

    return (
        <>
            <Head title="Goals" />

            <TopBarSlot>
                <div className="ml-auto flex items-center gap-2">
                    <TrakkButton
                        variant="accent"
                        onClick={() => sheets.open({ kind: 'goal' })}
                    >
                        + goal
                    </TrakkButton>
                </div>
            </TopBarSlot>

            <nav
                aria-label="Breadcrumb"
                className="flex items-center gap-2 font-mono text-[11px] text-faint"
            >
                <Link
                    href={dashboard()}
                    className="text-accent-soft hover:text-accent-hover"
                >
                    ← dashboard
                </Link>
                <span>/</span>
                <span className="text-fg">goals</span>
            </nav>

            <h1 className="text-[24px] font-light tracking-[-0.01em] text-fg-strong">
                Goals
            </h1>

            {groups.map((group) => (
                <GoalGroup key={group.status} group={group} />
            ))}
        </>
    );
}
