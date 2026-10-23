import { Head, Link } from '@inertiajs/react';
import { ActionRow } from '@/components/trakk/action-row';
import { CheckInSheet } from '@/components/trakk/check-in-sheet';
import { CommandBar } from '@/components/trakk/command-bar';
import { ConsistencyHeatmap } from '@/components/trakk/consistency-heatmap';
import { EmptyState } from '@/components/trakk/empty-state';
import { FirstRunPanel } from '@/components/trakk/first-run-panel';
import { GoalCard } from '@/components/trakk/goal-card';
import { GoalFilterTabs } from '@/components/trakk/goal-filter-tabs';
import type { GoalFilter } from '@/components/trakk/goal-filter-tabs';
import { KanbanBoard } from '@/components/trakk/kanban-board';
import { KpiCard } from '@/components/trakk/kpi-card';
import { LiveDot } from '@/components/trakk/live-dot';
import { LogFeed } from '@/components/trakk/log-feed';
import { McpStatus } from '@/components/trakk/mcp-status';
import { OverdueRow } from '@/components/trakk/overdue-row';
import { PointsChart } from '@/components/trakk/points-chart';
import { ReminderRow } from '@/components/trakk/reminder-row';
import { ReplanSheet } from '@/components/trakk/replan-sheet';
import { StatusFooter } from '@/components/trakk/status-footer';
import { TimelineItem } from '@/components/trakk/timeline-item';
import { TopBarSlot } from '@/components/trakk/top-bar-slot';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { ProgressBar } from '@/components/ui/progress-bar';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useDashboardState } from '@/hooks/use-dashboard-state';
import { useSheets } from '@/hooks/use-sheets';
import { percentOf } from '@/lib/format';
import { goalColorClass } from '@/lib/goal-color';
import { index as goalsIndex, show } from '@/routes/goals';
import { edit } from '@/routes/profile';
import type { DashboardProps } from '@/types';

const emptyGoalsCopy: Record<
    GoalFilter,
    { glyph: 'neutral' | 'positive'; text: string }
> = {
    all: { glyph: 'neutral', text: 'no active goals' },
    habit: { glyph: 'neutral', text: 'no habit goals' },
    project: { glyph: 'neutral', text: 'no project goals' },
    off: { glyph: 'positive', text: 'every goal is on pace' },
};

export default function Dashboard(props: DashboardProps) {
    const state = useDashboardState(props);
    const sheets = useSheets();
    const { focusProject, hints } = props;
    const todayPercent = Math.min(
        100,
        Math.round(percentOf(props.pointsToday, props.dailyTarget)),
    );
    const emptyGoals =
        emptyGoalsCopy[props.goals.length === 0 ? 'all' : state.filter];

    return (
        <>
            <Head title="Dashboard" />

            <h1 className="sr-only">Dashboard</h1>

            <TopBarSlot>
                <CommandBar />
                <div className="ml-auto flex items-center gap-2">
                    <McpStatus />
                    <TrakkButton onClick={() => sheets.open({ kind: 'goal' })}>
                        + goal
                    </TrakkButton>
                    <TrakkButton
                        variant="accent"
                        onClick={() => state.setSheet('check-in')}
                    >
                        check-in
                    </TrakkButton>
                </div>
            </TopBarSlot>

            {props.firstRun && <FirstRunPanel firstRun={props.firstRun} />}

            <section
                aria-label="Indicators"
                className="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-2.5"
            >
                {props.kpis.map((kpi) => (
                    <KpiCard key={kpi.id} kpi={kpi} />
                ))}
            </section>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <Panel
                    variant="list"
                    aria-labelledby="h-today"
                    className="flex flex-[1.5_1_462px] flex-col"
                >
                    <PanelHeader
                        inset
                        id="h-today"
                        title="Today"
                        count={`${state.doneCount}/${props.actions.length} actions`}
                    >
                        <div className="flex items-center gap-2 font-mono text-sm text-muted">
                            <ProgressBar
                                size="sm"
                                percent={todayPercent}
                                fillClassName="bg-accent"
                                className="w-[90px]"
                            />
                            <span>
                                <span className="text-fg">
                                    {props.pointsToday}
                                </span>{' '}
                                / {props.dailyTarget} pts
                            </span>
                        </div>
                    </PanelHeader>
                    {props.actions.length === 0 ? (
                        <EmptyState
                            hint={
                                hints.nextActionDay ??
                                'add a task with weekdays or a due date'
                            }
                            action={
                                <TrakkButton
                                    size="sm"
                                    onClick={() =>
                                        sheets.open({ kind: 'task' })
                                    }
                                >
                                    + task
                                </TrakkButton>
                            }
                        >
                            no actions scheduled today
                        </EmptyState>
                    ) : (
                        <div className="flex flex-col">
                            {props.actions.map((action) => (
                                <ActionRow
                                    key={action.id}
                                    action={action}
                                    onToggle={() => state.toggleAction(action)}
                                />
                            ))}
                        </div>
                    )}
                </Panel>

                <Panel
                    variant="list"
                    aria-labelledby="h-overdue"
                    className="flex flex-[1_1_322px] flex-col"
                >
                    <PanelHeader
                        inset
                        id="h-overdue"
                        title="Overdue"
                        count={props.overdue.length}
                        countClassName={
                            props.overdue.length > 0 ? 'text-danger' : undefined
                        }
                    >
                        <TrakkButton
                            size="sm"
                            onClick={() => state.setSheet('replan')}
                        >
                            replan
                        </TrakkButton>
                    </PanelHeader>
                    {props.overdue.length === 0 ? (
                        <EmptyState
                            glyph="positive"
                            hint={
                                hints.nextDeadline
                                    ? `next deadline: ${hints.nextDeadline.title} · ${hints.nextDeadline.dateLabel}`
                                    : 'no deadlines set'
                            }
                        >
                            nothing overdue
                        </EmptyState>
                    ) : (
                        props.overdue.map((item) => (
                            <OverdueRow key={item.id} item={item} />
                        ))
                    )}
                    {props.suggestion && (
                        <div className="mt-auto rounded-control border border-dashed border-line-strong p-2.5 font-mono text-sm leading-[1.55] text-fg-2">
                            <span className="text-accent">◆ suggestion</span> ·{' '}
                            {props.suggestion}
                        </div>
                    )}
                </Panel>

                <Panel
                    aria-labelledby="h-next"
                    className="flex flex-[1_1_330px] flex-col pb-2.5"
                >
                    <PanelHeader
                        id="h-next"
                        title="Next 7 days"
                        className="mb-2.5"
                    >
                        <span className="font-mono text-xs text-faint">
                            {props.upcoming.length} deadlines
                        </span>
                    </PanelHeader>
                    {props.upcoming.length === 0 ? (
                        <EmptyState
                            hint={
                                hints.nextDeadline
                                    ? `next: ${hints.nextDeadline.title} · ${hints.nextDeadline.dateLabel}`
                                    : undefined
                            }
                            action={
                                hints.nextDeadline ? undefined : (
                                    <TrakkButton
                                        size="sm"
                                        onClick={() =>
                                            sheets.open({ kind: 'task' })
                                        }
                                    >
                                        + task
                                    </TrakkButton>
                                )
                            }
                        >
                            nothing due in the next 7 days
                        </EmptyState>
                    ) : (
                        props.upcoming.map((item) => (
                            <TimelineItem key={item.id} item={item} />
                        ))
                    )}
                </Panel>
            </div>

            <section
                aria-labelledby="h-goals"
                className="flex flex-col gap-2.5"
            >
                <div className="flex flex-wrap items-center justify-between gap-2.5">
                    <div className="flex flex-wrap items-baseline gap-x-3.5 gap-y-1">
                        <h2
                            id="h-goals"
                            className="font-mono text-sm font-medium tracking-widest text-fg-2 uppercase"
                        >
                            Goals{' '}
                            <span className="font-normal text-faint">
                                · {props.goalsSummary.active} active ·{' '}
                                {props.goalsSummary.onPace} on pace
                            </span>
                        </h2>
                        <Link
                            href={goalsIndex()}
                            className="font-mono text-xs text-accent-soft hover:text-accent-hover"
                        >
                            goals →
                        </Link>
                    </div>
                    <GoalFilterTabs
                        active={state.filter}
                        counts={state.goalFilterCounts}
                        onChange={state.setFilter}
                    />
                </div>
                {state.visibleGoals.length === 0 ? (
                    <EmptyState
                        glyph={emptyGoals.glyph}
                        hint={
                            props.goals.length === 0
                                ? 'goals you create show their pace here'
                                : undefined
                        }
                        action={
                            props.goals.length === 0 ? (
                                <TrakkButton
                                    size="sm"
                                    onClick={() =>
                                        sheets.open({ kind: 'goal' })
                                    }
                                >
                                    + goal
                                </TrakkButton>
                            ) : undefined
                        }
                    >
                        {emptyGoals.text}
                    </EmptyState>
                ) : (
                    <div className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,380px),1fr))] gap-2.5">
                        {state.visibleGoals.map((goal) => (
                            <GoalCard key={goal.id} goal={goal} />
                        ))}
                    </div>
                )}
            </section>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <PointsChart chart={props.pointsChart} />
                <ConsistencyHeatmap data={props.consistency} />
                <Panel
                    variant="list"
                    aria-labelledby="h-reminders"
                    className="flex flex-[1_1_322px] flex-col pb-2"
                >
                    <PanelHeader
                        inset
                        id="h-reminders"
                        title="Reminders"
                        count={`${state.activeReminders} active`}
                    >
                        <TrakkButton
                            size="icon"
                            aria-label="Add reminder"
                            onClick={() => sheets.open({ kind: 'reminder' })}
                        >
                            +
                        </TrakkButton>
                    </PanelHeader>
                    {props.reminders.length === 0 ? (
                        <EmptyState
                            hint={
                                props.status.isTelegramLinked ? undefined : (
                                    <>
                                        telegram not linked ·{' '}
                                        <Link
                                            href={edit()}
                                            className="text-accent-soft hover:text-accent-hover"
                                        >
                                            settings
                                        </Link>
                                    </>
                                )
                            }
                            action={
                                <TrakkButton
                                    size="sm"
                                    onClick={() =>
                                        sheets.open({ kind: 'reminder' })
                                    }
                                >
                                    + reminder
                                </TrakkButton>
                            }
                        >
                            no reminders
                        </EmptyState>
                    ) : (
                        props.reminders.map((reminder) => (
                            <ReminderRow
                                key={reminder.id}
                                reminder={reminder}
                                onToggle={() => state.toggleReminder(reminder)}
                                onOpen={() =>
                                    sheets.open({
                                        kind: 'reminder',
                                        reminder: reminder.form,
                                    })
                                }
                            />
                        ))
                    )}
                </Panel>
            </div>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <Panel
                    aria-labelledby="h-kanban"
                    className="flex flex-[2_1_590px] flex-col"
                >
                    <PanelHeader
                        id="h-kanban"
                        title={
                            focusProject ? (
                                <>
                                    <span
                                        className={
                                            goalColorClass[focusProject.color]
                                                .text
                                        }
                                    >
                                        ■
                                    </span>{' '}
                                    Focus project · {focusProject.title}
                                </>
                            ) : (
                                'Focus project'
                            )
                        }
                    >
                        {focusProject && (
                            <div className="flex items-center gap-3 font-mono text-sm text-muted">
                                <span>
                                    <span className="text-fg">
                                        {focusProject.doneCount}
                                    </span>
                                    /{focusProject.totalCount} ·{' '}
                                    {focusProject.percent}%
                                </span>
                                <Link
                                    href={show({ goal: focusProject.goalSlug })}
                                    className="text-accent-soft hover:text-accent-hover"
                                >
                                    open board →
                                </Link>
                            </div>
                        )}
                    </PanelHeader>
                    {focusProject ? (
                        <KanbanBoard
                            columns={focusProject.columns}
                            goalSlug={focusProject.goalSlug}
                            variant="compact"
                            onMove={state.moveCard}
                        />
                    ) : (
                        <EmptyState
                            hint="the most behind project shows its board here"
                            action={
                                <TrakkButton
                                    size="sm"
                                    onClick={() =>
                                        sheets.open({ kind: 'goal' })
                                    }
                                >
                                    + goal
                                </TrakkButton>
                            }
                        >
                            no active project
                        </EmptyState>
                    )}
                </Panel>

                <Panel
                    variant="sunken"
                    aria-labelledby="h-log"
                    className="flex flex-[1_1_350px] flex-col"
                >
                    <PanelHeader id="h-log" title="Log" className="mb-2.5">
                        <span className="inline-flex items-center gap-1.5 font-mono text-xs text-muted">
                            <LiveDot size={5} />
                            live
                        </span>
                    </PanelHeader>
                    {props.log.length === 0 ? (
                        <EmptyState>nothing logged yet</EmptyState>
                    ) : (
                        <LogFeed
                            entries={props.log}
                            timeColumn="grid-cols-[78px_14px_minmax(0,1fr)_auto]"
                            showTrailing
                        />
                    )}
                </Panel>
            </div>

            <StatusFooter
                status={props.status}
                pointsLeft={Math.max(0, props.dailyTarget - props.pointsToday)}
            />

            <ReplanSheet
                open={state.sheet === 'replan'}
                overdue={props.overdue}
                onClose={() => state.setSheet(null)}
            />
            <CheckInSheet
                open={state.sheet === 'check-in'}
                actions={props.actions}
                pointsToday={props.pointsToday}
                dailyTarget={props.dailyTarget}
                onToggle={state.toggleAction}
                onClose={() => state.setSheet(null)}
            />
        </>
    );
}
