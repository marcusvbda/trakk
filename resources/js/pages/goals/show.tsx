import { Head } from '@inertiajs/react';
import { EmptyState } from '@/components/trakk/empty-state';
import { GoalFacts } from '@/components/trakk/goal-facts';
import { GoalHeader } from '@/components/trakk/goal-header';
import { GoalObjectivePanel } from '@/components/trakk/goal-objective-panel';
import { GoalProgressPanel } from '@/components/trakk/goal-progress-panel';
import { GoalRemindersPanel } from '@/components/trakk/goal-reminders-panel';
import { HabitActionsPanel } from '@/components/trakk/habit-actions-panel';
import { KanbanBoard } from '@/components/trakk/kanban-board';
import { LiveDot } from '@/components/trakk/live-dot';
import { LogFeed } from '@/components/trakk/log-feed';
import { PaceBadge } from '@/components/trakk/pace-badge';
import { PaceChart } from '@/components/trakk/pace-chart';
import { Chip } from '@/components/ui/chip';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useGoalBoardState } from '@/hooks/use-goal-board-state';
import { useSheets } from '@/hooks/use-sheets';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { GoalDetailProps, Priority } from '@/types';

const priorityTone = {
    high: 'warning',
    medium: 'secondary',
    low: 'muted',
} as const satisfies Record<Priority, string>;

export default function GoalShow(props: GoalDetailProps) {
    const {
        goal,
        form,
        progress,
        paceChart,
        board,
        actions,
        reminders,
        history,
        weeks,
    } = props;
    const state = useGoalBoardState(props);
    const sheets = useSheets();

    return (
        <>
            <Head title={goal.title} />

            <GoalHeader form={form} />

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
                <GoalFacts goal={goal} />
            </section>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <GoalObjectivePanel goal={goal} form={form} />
                <PaceChart
                    chart={paceChart}
                    color={goal.color}
                    percent={progress.percent}
                />
                <GoalProgressPanel
                    color={goal.color}
                    goalSlug={goal.slug}
                    progress={progress}
                />
            </div>

            {board && (
                <Panel aria-labelledby="h-board">
                    <PanelHeader
                        id="h-board"
                        title="Board"
                        count={`${progress.totalCount} tasks`}
                    >
                        <div className="flex items-center gap-2">
                            <span className="font-mono text-xs text-muted">
                                → advance · ↺ reopen
                            </span>
                            {progress.totalCount > 0 && (
                                <TrakkButton
                                    size="icon"
                                    aria-label="Add task to this goal"
                                    onClick={() =>
                                        sheets.open({
                                            kind: 'task',
                                            goalSlug: goal.slug,
                                        })
                                    }
                                >
                                    +
                                </TrakkButton>
                            )}
                        </div>
                    </PanelHeader>
                    {progress.totalCount === 0 ? (
                        <EmptyState
                            hint="break the goal into steps"
                            action={
                                <TrakkButton
                                    size="sm"
                                    onClick={() =>
                                        sheets.open({
                                            kind: 'task',
                                            goalSlug: goal.slug,
                                        })
                                    }
                                >
                                    + task
                                </TrakkButton>
                            }
                        >
                            no tasks yet
                        </EmptyState>
                    ) : (
                        <KanbanBoard
                            columns={board.columns}
                            goalSlug={goal.slug}
                            variant="full"
                            onMove={state.moveTask}
                        />
                    )}
                </Panel>
            )}

            {actions && (
                <HabitActionsPanel
                    actions={actions}
                    color={goal.color}
                    goalSlug={goal.slug}
                />
            )}

            <div className="flex flex-wrap items-stretch gap-3.5">
                <GoalRemindersPanel
                    reminders={reminders}
                    goalSlug={goal.slug}
                />

                <Panel
                    variant="sunken"
                    aria-labelledby="h-history"
                    className="flex flex-[1_1_390px] flex-col"
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
                    {weeks.length > 0 && (
                        <LogFeed
                            entries={weeks}
                            timeColumn="grid-cols-[64px_14px_minmax(0,1fr)]"
                        />
                    )}
                    {history.length === 0 ? (
                        <EmptyState>no history yet</EmptyState>
                    ) : (
                        <LogFeed
                            entries={history}
                            timeColumn="grid-cols-[64px_14px_minmax(0,1fr)]"
                        />
                    )}
                </Panel>
            </div>
        </>
    );
}
