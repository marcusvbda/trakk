import type {
    GoalColor,
    GoalStatus,
    GoalType,
    KanbanColumnData,
    LogEntry,
    Pace,
    Priority,
    ReminderForm,
    TaskSheet,
    TaskStatus,
} from './trakk';
import type { WeekProgress } from './dashboard';

export type GoalLink = {
    title: string;
    slug: string;
};

export type GoalDetail = {
    id: number;
    slug: string;
    title: string;
    type: GoalType;
    priority: Priority;
    color: GoalColor;
    colorHex: string;
    pace: Pace;
    facts: {
        start: string;
        dueDate: string;
        remainingDays: number;
        frequency: string | null;
        overdueCount: number;
    };
    objective: {
        text: string | null;
        doneWhen: string | null;
        area: string | null;
        linkedTo: GoalLink | null;
    };
};

export type GoalForm = {
    slug: string;
    title: string;
    type: GoalType;
    color: GoalColor;
    priority: Priority;
    startDate: string;
    dueDate: string;
    targetPerWeek: number | null;
    area: string | null;
    relatedGoalId: number | null;
    description: string | null;
    doneCriteria: string | null;
    status: GoalStatus;
    hasTasks: boolean;
};

export type GoalProgress = {
    percent: number;
    expectedPercent: number;
    doneCount: number;
    totalCount: number;
    unit: 'tasks' | 'days';
    counts: Record<TaskStatus, number> | null;
    week: WeekProgress | null;
};

export type PaceChartPoint = {
    day: number;
    percent: number;
};

export type PaceChartData = {
    totalDays: number;
    elapsedDays: number;
    startLabel: string;
    dueLabel: string;
    ticks: Array<{ day: number; label: string }>;
    actual: PaceChartPoint[];
    expectedPercent: number;
    projectedPercent: number;
    currentPace: number;
    requiredPace: number;
    paceUnit: 'tasks/wk' | 'days/wk';
    caption: string | null;
};

export type HabitAction = {
    id: number;
    title: string;
    days: number[];
    timeLabel: string;
    points: number;
    weekLabel: string;
    task: TaskSheet;
};

export type GoalReminder = {
    id: number;
    whenLabel: string;
    label: string;
    detailLabel: string;
    channels: string[];
    form: ReminderForm;
};

export type GoalDetailProps = {
    goal: GoalDetail;
    form: GoalForm;
    progress: GoalProgress;
    paceChart: PaceChartData;
    board: { columns: KanbanColumnData[] } | null;
    actions: HabitAction[] | null;
    reminders: GoalReminder[];
    history: LogEntry[];
};
