import type { ComponentProps } from 'react';
import { FieldError } from '@/components/trakk/field-error';
import { cn } from '@/lib/utils';

type TextareaFieldProps = Omit<ComponentProps<'textarea'>, 'id'> & {
    id: string;
    label: string;
    error?: string;
};

export function TextareaField({
    id,
    label,
    error,
    className,
    ...props
}: TextareaFieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <label
                htmlFor={id}
                className="font-mono text-2xs tracking-[0.12em] text-muted uppercase"
            >
                {label}
            </label>
            <textarea
                id={id}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className={cn(
                    'min-h-[88px] resize-y rounded-button border bg-field px-3 py-2.5 text-base leading-[1.5] text-fg transition-colors outline-none focus:border-accent',
                    error
                        ? 'border-danger-line'
                        : 'border-line hover:border-line-hover',
                    className,
                )}
                {...props}
            />
            <FieldError id={`${id}-error`} message={error} />
        </div>
    );
}
