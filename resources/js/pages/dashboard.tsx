import { Head, Link } from '@inertiajs/react';
import { ActionRow } from '@/components/trakk/action-row';
import { ConsistencyHeatmap } from '@/components/trakk/consistency-heatmap';
import { GoalCard } from '@/components/trakk/goal-card';
import { GoalFilterTabs } from '@/components/trakk/goal-filter-tabs';
import { KanbanBoard } from '@/components/trakk/kanban-board';
import { KpiCard } from '@/components/trakk/kpi-card';
import { LiveDot } from '@/components/trakk/live-dot';
import { LogFeed } from '@/components/trakk/log-feed';
import { OverdueRow } from '@/components/trakk/overdue-row';
import { PointsChart } from '@/components/trakk/points-chart';
import { ReminderRow } from '@/components/trakk/reminder-row';
import { StatusFooter } from '@/components/trakk/status-footer';
import { TimelineItem } from '@/components/trakk/timeline-item';
import { TopBar } from '@/components/trakk/top-bar';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { ProgressBar } from '@/components/ui/progress-bar';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useDashboardState } from '@/hooks/use-dashboard-state';
import { percentOf } from '@/lib/format';
import { goalColorClass } from '@/lib/goal-color';
import { show } from '@/routes/goals';
import type { DashboardProps } from '@/types';

export default function Dashboard(props: DashboardProps) {
    const state = useDashboardState(props);
    const { focusProject } = props;
    const todayPercent = Math.min(
        100,
        Math.round(percentOf(state.pointsToday, props.dailyTarget)),
    );

    return (
        <>
            <Head title="Dashboard" />

            <h1 className="sr-only">Dashboard</h1>

            <TopBar data={props.topBar} />

            <section
                aria-label="Indicators"
                className="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-2.5"
            >
                {state.kpis.map((kpi) => (
                    <KpiCard key={kpi.id} kpi={kpi} />
                ))}
            </section>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <Panel
                    variant="list"
                    aria-labelledby="h-today"
                    className="flex-[1.5_1_462px]"
                >
                    <PanelHeader
                        inset
                        id="h-today"
                        title="Today"
                        count={`${state.doneCount}/${state.actions.length} actions`}
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
                                    {state.pointsToday}
                                </span>{' '}
                                / {props.dailyTarget} pts
                            </span>
                        </div>
                    </PanelHeader>
                    <div className="flex flex-col">
                        {state.actions.map((action) => (
                            <ActionRow
                                key={action.id}
                                action={action}
                                onToggle={() => state.toggleAction(action.id)}
                            />
                        ))}
                    </div>
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
                        countClassName="text-danger"
                    >
                        <TrakkButton size="sm">replan</TrakkButton>
                    </PanelHeader>
                    {props.overdue.map((item) => (
                        <OverdueRow key={item.id} item={item} />
                    ))}
                    <div className="mt-auto rounded-control border border-dashed border-line-strong p-2.5 font-mono text-sm leading-[1.55] text-fg-2">
                        <span className="text-accent">◆ suggestion</span> ·{' '}
                        {props.suggestion}
                    </div>
                </Panel>

                <Panel
                    aria-labelledby="h-next"
                    className="flex-[1_1_330px] pb-2.5"
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
                    {props.upcoming.map((item) => (
                        <TimelineItem key={item.id} item={item} />
                    ))}
                </Panel>
            </div>

            <section
                aria-labelledby="h-goals"
                className="flex flex-col gap-2.5"
            >
                <div className="flex flex-wrap items-center justify-between gap-2.5">
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
                    <GoalFilterTabs
                        active={state.filter}
                        counts={state.goalFilterCounts}
                        onChange={state.setFilter}
                    />
                </div>
                <div className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,380px),1fr))] gap-2.5">
                    {state.visibleGoals.map((goal) => (
                        <GoalCard key={goal.id} goal={goal} />
                    ))}
                </div>
                {state.visibleGoals.length === 0 && (
                    <div className="rounded-panel border border-dashed border-line-strong p-6 text-center font-mono text-[11px] text-muted">
                        no goals match this filter
                    </div>
                )}
            </section>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <PointsChart chart={props.pointsChart} />
                <ConsistencyHeatmap data={props.consistency} />
                <Panel
                    variant="list"
                    aria-labelledby="h-reminders"
                    className="flex-[1_1_322px] pb-2"
                >
                    <PanelHeader
                        inset
                        id="h-reminders"
                        title="Reminders"
                        count={`${state.activeReminders} active`}
                    >
                        <span className="font-mono text-xs text-muted">
                            via telegram
                        </span>
                    </PanelHeader>
                    {state.reminders.map((reminder) => (
                        <ReminderRow
                            key={reminder.id}
                            reminder={reminder}
                            onToggle={() => state.toggleReminder(reminder.id)}
                        />
                    ))}
                </Panel>
            </div>

            <div className="flex flex-wrap items-stretch gap-3.5">
                <Panel aria-labelledby="h-kanban" className="flex-[2_1_590px]">
                    <PanelHeader
                        id="h-kanban"
                        title={
                            <>
                                <span
                                    className={
                                        goalColorClass[focusProject.color].text
                                    }
                                >
                                    ■
                                </span>{' '}
                                Focus project · {focusProject.title}
                            </>
                        }
                    >
                        <div className="flex items-center gap-3 font-mono text-sm text-muted">
                            <span>
                                <span className="text-fg">
                                    {state.focusDoneCount}
                                </span>
                                /{focusProject.totalCount} ·{' '}
                                {state.focusPercent}%
                            </span>
                            <Link
                                href={show({ goal: focusProject.goalId })}
                                className="text-accent-soft hover:text-accent-hover"
                            >
                                open board →
                            </Link>
                        </div>
                    </PanelHeader>
                    <KanbanBoard
                        columns={state.columns}
                        variant="compact"
                        onMove={state.moveCard}
                    />
                </Panel>

                <Panel
                    variant="sunken"
                    aria-labelledby="h-log"
                    className="flex-[1_1_350px]"
                >
                    <PanelHeader id="h-log" title="Log" className="mb-2.5">
                        <span className="inline-flex items-center gap-1.5 font-mono text-xs text-muted">
                            <LiveDot size={5} />
                            live
                        </span>
                    </PanelHeader>
                    <LogFeed
                        entries={state.log}
                        timeColumn="grid-cols-[78px_14px_minmax(0,1fr)_auto]"
                        showTrailing
                    />
                </Panel>
            </div>

            <StatusFooter
                status={props.status}
                pointsLeft={Math.max(0, props.dailyTarget - state.pointsToday)}
            />
        </>
    );
}
