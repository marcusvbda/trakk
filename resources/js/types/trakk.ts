export type GoalColor =
    | 'amber'
    | 'blue'
    | 'cyan'
    | 'pink'
    | 'violet'
    | 'sand'
    | 'green';

export type GoalType = 'habit' | 'project';

export type GoalStatus = 'active' | 'paused' | 'done' | 'abandoned';

export type Priority = 'high' | 'medium' | 'low';

export type PaceStatus = 'behind' | 'watch' | 'on_pace' | 'ahead';

export type Pace = {
    status: PaceStatus;
    delta: number;
};

export type Tone =
    | 'default'
    | 'accent'
    | 'danger'
    | 'warning'
    | 'info'
    | 'muted'
    | 'dim';

export type TaskStatus = 'todo' | 'doing' | 'done';

export type ReminderKind =
    | 'daily_brief'
    | 'evening_checkin'
    | 'due_tomorrow'
    | 'weekly_review'
    | 'off_pace'
    | 'pace_summary'
    | 'deadline_alert'
    | 'custom';

export type ReminderChannel = 'telegram' | 'email';

export type ReminderForm = {
    id: number;
    kind: ReminderKind;
    label: string;
    time: string;
    daysOfWeek: number[] | null;
    fireOn: string | null;
    goalId: number | null;
    channels: ReminderChannel[];
    isActive: boolean;
};

export type DayState = 'done' | 'missed' | 'upcoming';

export type LogKind =
    | 'done'
    | 'undone'
    | 'sent'
    | 'checkin'
    | 'warning'
    | 'reopened';

export type LogEntry = {
    id: number;
    timeLabel: string;
    kind: LogKind;
    text: string;
    trailing: string | null;
};

export type ChecklistItem = {
    id: number;
    title: string;
    isDone: boolean;
};

export type TaskSheet = {
    id: number;
    goalId: number;
    title: string;
    points: number;
    status: TaskStatus | null;
    dueDate: string | null;
    scheduledTime: string | null;
    daysOfWeek: number[] | null;
    tag: string | null;
    notes: string | null;
    checklist: ChecklistItem[];
};

export type KanbanCardData = {
    id: number;
    title: string;
    dueLabel: string | null;
    checklistDone: number;
    checklistTotal: number;
    isOverdue: boolean;
    tag: string | null;
    task: TaskSheet;
};

export type KanbanColumnData = {
    status: TaskStatus;
    hiddenCount?: number;
    cards: KanbanCardData[];
};
