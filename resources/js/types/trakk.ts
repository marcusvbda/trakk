export type GoalColor =
    | 'amber'
    | 'blue'
    | 'cyan'
    | 'pink'
    | 'violet'
    | 'sand'
    | 'green';

export type GoalType = 'habit' | 'project';

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

export type DayState = 'done' | 'missed' | 'upcoming';

export type LogKind =
    | 'done'
    | 'undone'
    | 'sent'
    | 'checkin'
    | 'warning'
    | 'reopened';

export type LogEntry = {
    id: string;
    timeLabel: string;
    kind: LogKind;
    text: string;
    trailing: string | null;
};

export type KanbanCardData = {
    id: string;
    title: string;
    dueLabel: string;
    checklistDone: number;
    checklistTotal: number;
    isOverdue: boolean;
    tag?: string | null;
};

export type KanbanColumnData = {
    status: TaskStatus;
    hiddenCount?: number;
    cards: KanbanCardData[];
};
