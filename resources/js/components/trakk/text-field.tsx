import type { ComponentProps, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type TextFieldProps = Omit<ComponentProps<'input'>, 'id'> & {
    id: string;
    label: string;
    invalid?: boolean;
    action?: ReactNode;
};

export function TextField({
    id,
    label,
    invalid = false,
    action,
    className,
    ...props
}: TextFieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <label
                htmlFor={id}
                className="font-mono text-2xs tracking-[0.12em] text-muted uppercase"
            >
                {label}
            </label>
            <div
                className={cn(
                    'flex h-11 items-center gap-2 rounded-button border bg-field pl-3 transition-colors focus-within:border-accent',
                    invalid
                        ? 'border-danger-line'
                        : 'border-line hover:border-line-hover',
                    !action && 'pr-3',
                )}
            >
                <span
                    aria-hidden="true"
                    className="font-mono text-[12px] text-accent"
                >
                    ›
                </span>
                <input
                    id={id}
                    aria-invalid={invalid || undefined}
                    className={cn(
                        'h-10 min-w-0 flex-1 bg-transparent font-mono text-[12px] text-fg outline-none',
                        className,
                    )}
                    {...props}
                />
                {action}
            </div>
        </div>
    );
}
