import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type TrakkButtonProps = ComponentProps<'button'> & {
    variant?: 'default' | 'accent';
    size?: 'md' | 'sm' | 'lg' | 'icon';
};

const variants = {
    default:
        'border-line-strong text-fg-2 transition-colors hover:border-muted hover:text-fg',
    accent: 'border-accent text-accent',
} as const;

const sizes = {
    md: 'h-[34px] rounded-button px-3 text-[11px]',
    sm: 'h-[26px] rounded-control px-2 text-xs',
    lg: 'h-11 w-full rounded-button text-[11.5px] tracking-[0.04em]',
    icon: 'size-7 rounded-control text-[12px]',
} as const;

export function TrakkButton({
    variant = 'default',
    size = 'md',
    type = 'button',
    className,
    ...props
}: TrakkButtonProps) {
    return (
        <button
            type={type}
            className={cn(
                'inline-flex items-center justify-center gap-2 border font-mono whitespace-nowrap',
                variants[variant],
                sizes[size],
                className,
            )}
            {...props}
        />
    );
}
