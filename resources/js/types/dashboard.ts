import type {
    DayState,
    GoalColor,
    GoalType,
    KanbanColumnData,
    LogEntry,
    Pace,
    Priority,
    Tone,
} from './trakk';

export type TopBarData = {
    dateLabel: string;
    yearProgress: number;
};

export type Kpi = {
    id: string;
    label: string;
    value: string;
    suffix: string;
    valueTone: Tone;
    progress: number | null;
    progressTone: Tone;
    sub: string;
    subTone: Tone;
};

export type TodayAction = {
    id: string;
    title: string;
    goalName: string;
    goalColor: GoalColor;
    points: number;
    timeLabel: string;
    isDueToday: boolean;
    isDone: boolean;
};

export type OverdueItem = {
    id: string;
    title: string;
    goalName: string;
    goalColor: GoalColor;
    dueLabel: string;
    daysLate: number;
};

export type UpcomingItem = {
    id: string;
    dayLabel: string;
    title: string;
    goalName: string;
    goalColor: GoalColor | null;
};

export type WeekProgress = {
    days: DayState[];
    doneCount: number;
    targetCount: number;
};

export type GoalSummary = {
    id: string;
    title: string;
    type: GoalType;
    priority: Priority;
    color: GoalColor;
    startLabel: string;
    dueLabel: string;
    daysLeft: number;
    frequencyLabel: string;
    percent: number;
    expectedPercent: number;
    pace: Pace;
    week: WeekProgress | null;
    progressLabel: string | null;
    hasBoard: boolean;
};

export type PointsTier = 'target' | 'good' | 'low';

export type PointsDay = {
    points: number;
    tier: PointsTier;
    label: string;
};

export type PointsChartData = {
    target: number;
    scaleMax: number;
    days: PointsDay[];
    startLabel: string;
    middleLabel: string;
    average: number;
    bestDay: number;
    daysAtTarget: number;
};

export type HeatLevel = 0 | 1 | 2 | 3 | 4;

export type ConsistencyData = {
    weeks: Array<Array<HeatLevel | null>>;
    goodDays: number;
    totalDays: number;
    goodPercent: number;
};

export type ReminderItem = {
    id: string;
    timeLabel: string;
    label: string;
    frequencyLabel: string;
    channelsLabel: string;
    isActive: boolean;
};

export type FocusProject = {
    goalId: string;
    title: string;
    color: GoalColor;
    doneCount: number;
    totalCount: number;
    percent: number;
    columns: KanbanColumnData[];
};

export type StatusLine = {
    mcpTools: number;
    nextReminderAt: string;
};

export type DashboardProps = {
    topBar: TopBarData;
    dailyTarget: number;
    kpis: Kpi[];
    actions: TodayAction[];
    overdue: OverdueItem[];
    suggestion: string;
    upcoming: UpcomingItem[];
    goalsSummary: { active: number; onPace: number };
    goals: GoalSummary[];
    pointsChart: PointsChartData;
    consistency: ConsistencyData;
    reminders: ReminderItem[];
    focusProject: FocusProject;
    log: LogEntry[];
    status: StatusLine;
};
