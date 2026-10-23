import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { GoalFilter } from '@/components/trakk/goal-filter-tabs';
import type { DashboardSheet } from '@/lib/dashboard-sheet';
import {
    dashboardSheetEvent,
    requestedDashboardSheet,
} from '@/lib/dashboard-sheet';
import { formatMinutes, percentOf } from '@/lib/format';
import { moveCardBetweenColumns, taskStatusLabel } from '@/lib/kanban';
import { dashboard } from '@/routes';
import { update as setReminderActive } from '@/routes/reminders/active';
import {
    destroy as undoCompletion,
    store as completeTask,
} from '@/routes/tasks/completions';
import { update as moveTask } from '@/routes/tasks/status';
import type {
    DashboardProps,
    GoalSummary,
    LogEntry,
    ReminderItem,
    TaskStatus,
    TodayAction,
} from '@/types';

const maxLogEntries = 8;

const refreshAfterTaskChange = [
    'kpis',
    'actions',
    'pointsToday',
    'pointsChart',
    'consistency',
    'goalsSummary',
    'goals',
    'focusProject',
    'log',
    'status',
    'overdue',
    'suggestion',
    'upcoming',
    'hints',
];

const refreshAfterReminderToggle = ['reminders', 'log', 'status'];

function matchesFilter(goal: GoalSummary, filter: GoalFilter): boolean {
    switch (filter) {
        case 'habit':
        case 'project':
            return goal.type === filter;
        case 'off':
            return (
                goal.pace.status === 'behind' || goal.pace.status === 'watch'
            );
        default:
            return true;
    }
}

function withLogLine(
    log: LogEntry[],
    entry: Omit<LogEntry, 'id' | 'timeLabel'>,
): LogEntry[] {
    const created: LogEntry = {
        ...entry,
        id: -Date.now(),
        timeLabel: formatMinutes(new Date()),
    };

    return [created, ...log].slice(0, maxLogEntries);
}

export function useDashboardState(props: DashboardProps) {
    const [filter, setFilter] = useState<GoalFilter>('all');
    const { url } = usePage();
    const [requested] = useState(() => requestedDashboardSheet(url));
    const [sheet, setSheet] = useState<DashboardSheet | null>(requested);

    useEffect(() => {
        if (requested) {
            window.history.replaceState(
                window.history.state,
                '',
                dashboard.url(),
            );
        }

        function open(event: Event) {
            setSheet((event as CustomEvent<DashboardSheet>).detail);
        }

        window.addEventListener(dashboardSheetEvent, open);

        return () => window.removeEventListener(dashboardSheetEvent, open);
    }, [requested]);

    function toggleAction(action: TodayAction) {
        const wasDone = action.isDone;
        const pointsToday =
            props.pointsToday + (wasDone ? -action.points : action.points);
        const options = { preserveScroll: true, only: refreshAfterTaskChange };

        const visit = router.optimistic<DashboardProps>((current) => ({
            actions: current.actions.map((candidate) =>
                candidate.id === action.id
                    ? { ...candidate, isDone: !wasDone }
                    : candidate,
            ),
            pointsToday,
            kpis: current.kpis.map((kpi) =>
                kpi.id === 'points-today'
                    ? {
                          ...kpi,
                          value: String(pointsToday),
                          progress: Math.min(
                              100,
                              Math.round(
                                  percentOf(pointsToday, current.dailyTarget),
                              ),
                          ),
                      }
                    : kpi,
            ),
            log: withLogLine(current.log, {
                kind: wasDone ? 'undone' : 'done',
                text: wasDone
                    ? `${action.title.toLowerCase()} unchecked · ${action.goalName}`
                    : `${action.title.toLowerCase()} · ${action.goalName}`,
                trailing: wasDone ? null : `+${action.points}`,
            }),
        }));

        if (wasDone) {
            visit.delete(undoCompletion.url(action.id), options);

            return;
        }

        visit.post(completeTask.url(action.id), {}, options);
    }

    function toggleReminder(reminder: ReminderItem) {
        const isActive = !reminder.isActive;

        router
            .optimistic<DashboardProps>((current) => ({
                reminders: current.reminders.map((candidate) =>
                    candidate.id === reminder.id
                        ? { ...candidate, isActive }
                        : candidate,
                ),
                log: withLogLine(current.log, {
                    kind: isActive ? 'sent' : 'undone',
                    text: `${reminder.label.toLowerCase()} ${isActive ? 'enabled' : 'paused'}`,
                    trailing: null,
                }),
            }))
            .patch(
                setReminderActive.url(reminder.id),
                { is_active: isActive },
                { preserveScroll: true, only: refreshAfterReminderToggle },
            );
    }

    function moveCard(cardId: number, from: TaskStatus, to: TaskStatus) {
        const card = props.focusProject?.columns
            .find((column) => column.status === from)
            ?.cards.find((candidate) => candidate.id === cardId);

        if (!card || !props.focusProject) {
            return;
        }

        const doneCount =
            props.focusProject.doneCount +
            (to === 'done' ? 1 : 0) -
            (from === 'done' ? 1 : 0);

        router
            .optimistic<DashboardProps>((current) =>
                current.focusProject
                    ? {
                          focusProject: {
                              ...current.focusProject,
                              doneCount,
                              percent: Math.round(
                                  percentOf(
                                      doneCount,
                                      current.focusProject.totalCount,
                                  ),
                              ),
                              columns: moveCardBetweenColumns(
                                  current.focusProject.columns,
                                  cardId,
                                  from,
                                  to,
                              ),
                          },
                          log: withLogLine(current.log, {
                              kind: to === 'done' ? 'done' : 'sent',
                              text: `${card.title.toLowerCase()} → ${taskStatusLabel[to]}`,
                              trailing: null,
                          }),
                      }
                    : {},
            )
            .patch(
                moveTask.url(cardId),
                { status: to },
                { preserveScroll: true, only: refreshAfterTaskChange },
            );
    }

    const visibleGoals = props.goals.filter((goal) =>
        matchesFilter(goal, filter),
    );
    const goalFilterCounts: Record<GoalFilter, number> = {
        all: props.goals.length,
        habit: props.goals.filter((goal) => matchesFilter(goal, 'habit'))
            .length,
        project: props.goals.filter((goal) => matchesFilter(goal, 'project'))
            .length,
        off: props.goals.filter((goal) => matchesFilter(goal, 'off')).length,
    };

    return {
        filter,
        setFilter,
        sheet,
        setSheet,
        visibleGoals,
        goalFilterCounts,
        doneCount: props.actions.filter((action) => action.isDone).length,
        activeReminders: props.reminders.filter((reminder) => reminder.isActive)
            .length,
        toggleAction,
        toggleReminder,
        moveCard,
    };
}
