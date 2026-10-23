import type { GoalColor } from '@/types';

export const goalColorClass: Record<GoalColor, { bg: string; border: string }> =
    {
        amber: { bg: 'bg-goal-amber', border: 'border-goal-amber' },
        blue: { bg: 'bg-goal-blue', border: 'border-goal-blue' },
        cyan: { bg: 'bg-goal-cyan', border: 'border-goal-cyan' },
        pink: { bg: 'bg-goal-pink', border: 'border-goal-pink' },
        violet: { bg: 'bg-goal-violet', border: 'border-goal-violet' },
        sand: { bg: 'bg-goal-sand', border: 'border-goal-sand' },
        green: { bg: 'bg-goal-green', border: 'border-goal-green' },
    };
