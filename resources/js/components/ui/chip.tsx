import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type ChipProps = ComponentProps<'span'> & {
    tone?: 'warning' | 'secondary' | 'muted' | 'outline' | 'faint';
    size?: 'tag' | 'title' | 'plain';
};

const tones = {
    warning: 'border-warning-line text-warning',
    secondary: 'border-line-strong text-fg-2',
    muted: 'border-line-subtle text-muted',
    outline: 'border-line-strong text-muted',
    faint: 'border-line-subtle text-faint',
} as const;

const sizes = {
    tag: 'px-[5px] py-0.5 text-2xs tracking-widest uppercase',
    title: 'px-1.5 py-[3px] text-2xs tracking-[0.12em] uppercase',
    plain: 'px-[5px] py-0.5 text-xs',
} as const;

export function Chip({
    tone = 'outline',
    size = 'tag',
    className,
    ...props
}: ChipProps) {
    return (
        <span
            className={cn(
                'inline-block rounded-chip border font-mono',
                tones[tone],
                sizes[size],
                className,
            )}
            {...props}
        />
    );
}
