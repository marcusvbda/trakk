import { Head } from '@inertiajs/react';
import { GoalFacts } from '@/components/trakk/goal-facts';
import { GoalHeader } from '@/components/trakk/goal-header';
import { GoalProgressPanel } from '@/components/trakk/goal-progress-panel';
import { KanbanBoard } from '@/components/trakk/kanban-board';
import { LiveDot } from '@/components/trakk/live-dot';
import { LogFeed } from '@/components/trakk/log-feed';
import { PaceBadge } from '@/components/trakk/pace-badge';
import { PaceChart } from '@/components/trakk/pace-chart';
import { Chip } from '@/components/ui/chip';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { useGoalBoardState } from '@/hooks/use-goal-board-state';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { GoalDetailProps, Priority } from '@/types';

const priorityTone = {
    high: 'warning',
    medium: 'secondary',
    low: 'muted',
} as const satisfies Record<Priority, string>;

export default function GoalShow(props: GoalDetailProps) {
    const { goal, progress, paceChart, reminders } = props;
    const state = useGoalBoardState(props);

    return (
        <>
            <Head title={goal.title} />

            <GoalHeader slug={goal.slug} />

            <section aria-labelledby="h-title" className="flex flex-col gap-3">
                <div className="flex flex-wrap items-center gap-x-3.5 gap-y-2.5">
                    <span
                        className={cn(
                            'size-3 rounded-[3px]',
                            goalColorClass[goal.color].bg,
                        )}
                    />
                    <h1
                        id="h-title"
                        className="text-[24px] font-light tracking-[-0.01em] text-fg-strong"
                    >
                        {goal.title}
                    </h1>
                    <Chip size="title" tone="outline">
                        {goal.type}
                    </Chip>
                    <Chip size="title" tone={priorityTone[goal.priority]}>
                        {goal.priority} priority
                    </Chip>
                    <PaceBadge pace={goal.pace} className="text-sm" />
                </div>
                <GoalFacts goal={goal} overdueCount={state.overdueCount} />
            </section>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <Panel
                    aria-labelledby="h-objective"
                    className="flex flex-[1_1_330px] flex-col gap-3"
                >
                    <PanelHeader
                        id="h-objective"
                        title="Objective"
                        className="mb-0"
                    />
                    <p className="text-md leading-[1.6] text-body">
                        {goal.objective.text}
                    </p>
                    <div className="flex flex-col gap-1.5 rounded-control border border-dashed border-line-strong p-2.5">
                        <span className="font-mono text-2xs tracking-[0.12em] text-accent uppercase">
                            done when
                        </span>
                        <span className="text-base leading-[1.5] text-body">
                            {goal.objective.doneWhen}
                        </span>
                    </div>
                    <div className="mt-auto font-mono text-sm leading-[1.6] text-muted">
                        area · {goal.objective.area}
                        <br />
                        linked to · {goal.objective.linkedTo}
                    </div>
                </Panel>

                <PaceChart
                    chart={paceChart}
                    color={goal.color}
                    percent={state.percent}
                />

                <GoalProgressPanel
                    color={goal.color}
                    percent={state.percent}
                    expectedPercent={progress.expectedPercent}
                    doneCount={state.doneCount}
                    totalCount={progress.totalCount}
                    counts={state.counts}
                />
            </div>

            <Panel aria-labelledby="h-board">
                <PanelHeader
                    id="h-board"
                    title="Board"
                    count={`${progress.totalCount} tasks`}
                >
                    <span className="font-mono text-xs text-muted">
                        → advance · ↺ reopen
                    </span>
                </PanelHeader>
                <KanbanBoard
                    columns={state.columns}
                    variant="full"
                    onMove={state.moveTask}
                />
            </Panel>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <Panel
                    variant="list"
                    aria-labelledby="h-reminders"
                    className="flex-[1_1_342px] pb-2"
                >
                    <PanelHeader
                        inset
                        id="h-reminders"
                        title="Reminders for this goal"
                    >
                        <span className="font-mono text-xs text-muted">
                            telegram
                        </span>
                    </PanelHeader>
                    {reminders.map((reminder) => (
                        <div
                            key={reminder.id}
                            className="flex min-h-11 items-center gap-2.5 rounded-control px-1.5 transition-colors duration-150 hover:bg-hover"
                        >
                            <span className="w-[78px] flex-none font-mono text-[11px] text-fg">
                                {reminder.whenLabel}
                            </span>
                            <span className="min-w-0 flex-1 text-base text-body">
                                {reminder.label}
                            </span>
                            <span className="flex-none font-mono text-xs text-muted">
                                {reminder.detailLabel}
                            </span>
                        </div>
                    ))}
                </Panel>

                <Panel
                    variant="sunken"
                    aria-labelledby="h-history"
                    className="flex-[1_1_390px]"
                >
                    <PanelHeader
                        id="h-history"
                        title="History"
                        className="mb-2.5"
                    >
                        <span className="inline-flex items-center gap-1.5 font-mono text-xs text-muted">
                            <LiveDot size={5} />
                            live
                        </span>
                    </PanelHeader>
                    <LogFeed
                        entries={state.history}
                        timeColumn="grid-cols-[64px_14px_minmax(0,1fr)]"
                    />
                </Panel>
            </div>
        </>
    );
}
