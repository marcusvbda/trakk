import type { ReactNode } from 'react';
import { FieldError } from '@/components/trakk/field-error';

type FieldGroupProps = {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
};

export function FieldGroup({
    id,
    label,
    error,
    hint,
    children,
}: FieldGroupProps) {
    return (
        <fieldset
            aria-describedby={error ? `${id}-error` : undefined}
            className="flex min-w-0 flex-col gap-1.5"
        >
            <legend className="mb-1.5 font-mono text-2xs tracking-[0.12em] text-muted uppercase">
                {label}
            </legend>
            {children}
            {hint && !error && (
                <p className="font-mono text-xs text-faint">{hint}</p>
            )}
            <FieldError id={`${id}-error`} message={error} />
        </fieldset>
    );
}
