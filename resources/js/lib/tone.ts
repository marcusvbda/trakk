import type { Tone } from '@/types';

export const toneText: Record<Tone, string> = {
    default: 'text-fg',
    accent: 'text-accent',
    danger: 'text-danger',
    warning: 'text-warning',
    info: 'text-info',
    muted: 'text-muted',
    dim: 'text-dim',
};

export const toneBg: Record<Tone, string> = {
    default: 'bg-fg',
    accent: 'bg-accent',
    danger: 'bg-danger',
    warning: 'bg-warning',
    info: 'bg-info',
    muted: 'bg-muted',
    dim: 'bg-dim',
};
