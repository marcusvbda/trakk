import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type PanelProps = ComponentProps<'section'> & {
    variant?: 'block' | 'list' | 'sunken';
};

const variants = {
    block: 'bg-surface px-3.5 py-3',
    list: 'bg-surface px-2.5 pt-3 pb-2.5',
    sunken: 'bg-sunken px-3.5 py-3',
} as const;

export function Panel({ variant = 'block', className, ...props }: PanelProps) {
    return (
        <section
            className={cn(
                'min-w-0 rounded-panel border border-line',
                variants[variant],
                className,
            )}
            {...props}
        />
    );
}
