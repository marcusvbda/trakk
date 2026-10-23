import type {
    GoalColor,
    GoalType,
    KanbanColumnData,
    LogEntry,
    Pace,
    Priority,
} from './trakk';

export type GoalDetail = {
    id: string;
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
    };
    objective: {
        text: string;
        doneWhen: string;
        area: string;
        linkedTo: string;
    };
};

export type GoalProgress = {
    percent: number;
    expectedPercent: number;
    doneCount: number;
    totalCount: number;
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
};

export type GoalReminder = {
    id: string;
    whenLabel: string;
    label: string;
    detailLabel: string;
};

export type GoalDetailProps = {
    goal: GoalDetail;
    progress: GoalProgress;
    paceChart: PaceChartData;
    board: { columns: KanbanColumnData[] };
    reminders: GoalReminder[];
    history: LogEntry[];
};
