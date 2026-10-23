import type {
    DayState,
    GoalColor,
    GoalType,
    KanbanColumnData,
    LogEntry,
    Pace,
    Priority,
    ReminderForm,
    Tone,
} from './trakk';

export type TopBarData = {
    dateLabel: string;
    yearProgress: number;
    mcpTools: number;
    isMcpLive: boolean;
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
    id: number;
    title: string;
    goalName: string;
    goalColor: GoalColor;
    points: number;
    timeLabel: string;
    isDueToday: boolean;
    isDone: boolean;
};

export type OverdueItem = {
    id: number;
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
    id: number;
    slug: string;
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
    id: number;
    timeLabel: string;
    label: string;
    frequencyLabel: string;
    channelsLabel: string;
    isActive: boolean;
    form: ReminderForm;
};

export type FocusProject = {
    goalSlug: string;
    title: string;
    color: GoalColor;
    doneCount: number;
    totalCount: number;
    percent: number;
    columns: KanbanColumnData[];
};

export type StatusLine = {
    mcpTools: number;
    nextReminderAt: string | null;
    isTelegramLinked: boolean;
    isTelegramActive: boolean;
};

export type FirstRun = {
    isTelegramLinked: boolean;
};

export type EmptyHints = {
    nextActionDay: string | null;
    nextDeadline: { title: string; dateLabel: string } | null;
};

export type DashboardProps = {
    dailyTarget: number;
    pointsToday: number;
    kpis: Kpi[];
    actions: TodayAction[];
    overdue: OverdueItem[];
    suggestion: string | null;
    upcoming: UpcomingItem[];
    goalsSummary: { active: number; onPace: number };
    goals: GoalSummary[];
    pointsChart: PointsChartData;
    consistency: ConsistencyData;
    reminders: ReminderItem[];
    focusProject: FocusProject | null;
    log: LogEntry[];
    status: StatusLine;
    firstRun: FirstRun | null;
    hints: EmptyHints;
};
