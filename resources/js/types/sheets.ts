import type { TodayAction } from './dashboard';
import type { GoalColor, GoalStatus, GoalType } from './trakk';

export type GoalOption = {
    id: number;
    title: string;
    slug: string;
    type: GoalType;
    color: GoalColor;
    status: GoalStatus;
};

export type SheetOptions = {
    today: string;
    defaultDueDate: string;
    goals: GoalOption[];
};

export type CommandIndex = {
    goals: GoalOption[];
    tasks: TodayAction[];
};
