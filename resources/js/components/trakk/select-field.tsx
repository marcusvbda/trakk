import type { ComponentProps } from 'react';
import { FieldError } from '@/components/trakk/field-error';
import { cn } from '@/lib/utils';

type SelectFieldProps = Omit<ComponentProps<'select'>, 'id'> & {
    id: string;
    label: string;
    options: Array<{ value: string; label: string }>;
    error?: string;
};

export function SelectField({
    id,
    label,
    options,
    error,
    className,
    ...props
}: SelectFieldProps) {
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
                    'flex h-11 items-center gap-2 rounded-button border bg-field px-3 transition-colors focus-within:border-accent',
                    error
                        ? 'border-danger-line'
                        : 'border-line hover:border-line-hover',
                )}
            >
                <span
                    aria-hidden="true"
                    className="font-mono text-[12px] text-accent"
                >
                    ›
                </span>
                <select
                    id={id}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={cn(
                        'h-10 min-w-0 flex-1 bg-transparent font-mono text-[12px] text-fg outline-none',
                        className,
                    )}
                    {...props}
                >
                    {options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </div>
            <FieldError id={`${id}-error`} message={error} />
        </div>
    );
}
