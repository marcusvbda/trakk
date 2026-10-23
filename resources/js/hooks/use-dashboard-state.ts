import { useState } from 'react';
import type { GoalFilter } from '@/components/trakk/goal-filter-tabs';
import { formatMinutes, percentOf } from '@/lib/format';
import { moveCardBetweenColumns, taskStatusLabel } from '@/lib/kanban';
import type {
    DashboardProps,
    GoalSummary,
    Kpi,
    LogEntry,
    TaskStatus,
} from '@/types';

const maxLogEntries = 8;

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

// Mock-phase interactions: the sums below move to the server in M3/M4.
function withPointsToday(kpis: Kpi[], points: number, target: number): Kpi[] {
    const isTargetHit = points >= target;
    const percent = Math.min(100, Math.round(percentOf(points, target)));

    return kpis.map((kpi) =>
        kpi.id === 'points-today'
            ? {
                  ...kpi,
                  value: String(points),
                  valueTone: isTargetHit ? 'accent' : 'default',
                  progress: percent,
                  sub: isTargetHit
                      ? 'daily target hit'
                      : `${percent}% of daily target`,
                  subTone: isTargetHit ? 'accent' : 'muted',
              }
            : kpi,
    );
}

export function useDashboardState(props: DashboardProps) {
    const [actions, setActions] = useState(props.actions);
    const [log, setLog] = useState(props.log);
    const [reminders, setReminders] = useState(props.reminders);
    const [columns, setColumns] = useState(props.focusProject.columns);
    const [filter, setFilter] = useState<GoalFilter>('all');

    function pushLog(entry: Omit<LogEntry, 'id' | 'timeLabel'>) {
        const created: LogEntry = {
            ...entry,
            id: `local-${crypto.randomUUID()}`,
            timeLabel: formatMinutes(new Date()),
        };

        setLog((entries) => [created, ...entries].slice(0, maxLogEntries));
    }

    function toggleAction(actionId: string) {
        const action = actions.find((candidate) => candidate.id === actionId);

        if (!action) {
            return;
        }

        const wasDone = action.isDone;

        setActions((current) =>
            current.map((candidate) =>
                candidate.id === actionId
                    ? { ...candidate, isDone: !wasDone }
                    : candidate,
            ),
        );
        pushLog({
            kind: wasDone ? 'undone' : 'done',
            text: `${wasDone ? 'undone · ' : ''}${action.title.toLowerCase()} · ${action.goalName}`,
            trailing: `${wasDone ? '−' : '+'}${action.points}`,
        });
    }

    function toggleReminder(reminderId: string) {
        const reminder = reminders.find(
            (candidate) => candidate.id === reminderId,
        );

        if (!reminder) {
            return;
        }

        setReminders((current) =>
            current.map((candidate) =>
                candidate.id === reminderId
                    ? { ...candidate, isActive: !candidate.isActive }
                    : candidate,
            ),
        );
        pushLog({
            kind: reminder.isActive ? 'undone' : 'sent',
            text: `reminder ${reminder.isActive ? 'paused' : 'enabled'} · ${reminder.label.toLowerCase()}`,
            trailing: null,
        });
    }

    function moveCard(cardId: string, from: TaskStatus, to: TaskStatus) {
        const card = columns
            .find((column) => column.status === from)
            ?.cards.find((candidate) => candidate.id === cardId);

        if (!card) {
            return;
        }

        setColumns((current) =>
            moveCardBetweenColumns(current, cardId, from, to),
        );
        pushLog({
            kind: to === 'done' ? 'done' : 'sent',
            text: `${card.title.toLowerCase()} → ${taskStatusLabel[to]}`,
            trailing: null,
        });
    }

    const pointsToday = actions.reduce(
        (sum, action) => sum + (action.isDone ? action.points : 0),
        0,
    );
    const doneCount = actions.filter((action) => action.isDone).length;
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
    const doneColumn = columns.find((column) => column.status === 'done');
    const focusDoneCount =
        (doneColumn?.cards.length ?? 0) + (doneColumn?.hiddenCount ?? 0);

    return {
        actions,
        doneCount,
        pointsToday,
        kpis: withPointsToday(props.kpis, pointsToday, props.dailyTarget),
        log,
        reminders,
        activeReminders: reminders.filter((reminder) => reminder.isActive)
            .length,
        columns,
        focusDoneCount,
        focusPercent: Math.round(
            (focusDoneCount / props.focusProject.totalCount) * 100,
        ),
        filter,
        setFilter,
        visibleGoals,
        goalFilterCounts,
        toggleAction,
        toggleReminder,
        moveCard,
    };
}
