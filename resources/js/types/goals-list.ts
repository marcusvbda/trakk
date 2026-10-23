import type { GoalColor, GoalStatus, GoalType, Priority } from './trakk';

export type GoalListItem = {
    id: number;
    slug: string;
    title: string;
    type: GoalType;
    priority: Priority;
    color: GoalColor;
    startLabel: string;
    dueLabel: string;
    percent: number;
};

export type GoalListGroup = {
    status: GoalStatus;
    goals: GoalListItem[];
};

export type GoalsListProps = {
    groups: GoalListGroup[];
};
