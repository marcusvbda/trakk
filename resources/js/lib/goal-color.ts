import type { GoalColor } from '@/types';

export const goalColorClass: Record<
    GoalColor,
    { bg: string; border: string; text: string; stroke: string }
> = {
    amber: {
        bg: 'bg-goal-amber',
        border: 'border-goal-amber',
        text: 'text-goal-amber',
        stroke: 'stroke-goal-amber',
    },
    blue: {
        bg: 'bg-goal-blue',
        border: 'border-goal-blue',
        text: 'text-goal-blue',
        stroke: 'stroke-goal-blue',
    },
    cyan: {
        bg: 'bg-goal-cyan',
        border: 'border-goal-cyan',
        text: 'text-goal-cyan',
        stroke: 'stroke-goal-cyan',
    },
    pink: {
        bg: 'bg-goal-pink',
        border: 'border-goal-pink',
        text: 'text-goal-pink',
        stroke: 'stroke-goal-pink',
    },
    violet: {
        bg: 'bg-goal-violet',
        border: 'border-goal-violet',
        text: 'text-goal-violet',
        stroke: 'stroke-goal-violet',
    },
    sand: {
        bg: 'bg-goal-sand',
        border: 'border-goal-sand',
        text: 'text-goal-sand',
        stroke: 'stroke-goal-sand',
    },
    green: {
        bg: 'bg-goal-green',
        border: 'border-goal-green',
        text: 'text-goal-green',
        stroke: 'stroke-goal-green',
    },
};
