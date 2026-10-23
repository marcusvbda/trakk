import { FieldGroup } from '@/components/trakk/field-group';
import { cn } from '@/lib/utils';

type SegmentedFieldProps<T extends string | number> = {
    id: string;
    label: string;
    value: T;
    options: Array<{ value: T; label: string }>;
    onChange: (value: T) => void;
    error?: string;
    hint?: string;
    disabled?: boolean;
};

export function SegmentedField<T extends string | number>({
    id,
    label,
    value,
    options,
    onChange,
    error,
    hint,
    disabled = false,
}: SegmentedFieldProps<T>) {
    return (
        <FieldGroup id={id} label={label} error={error} hint={hint}>
            <div className="flex flex-wrap gap-1.5">
                {options.map((option) => (
                    <button
                        key={option.value}
                        type="button"
                        disabled={disabled}
                        aria-pressed={value === option.value}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'h-8 rounded-control border px-2.5 font-mono text-xs disabled:opacity-60',
                            value === option.value
                                ? 'border-line-strong bg-selected text-fg'
                                : 'border-line text-muted',
                        )}
                    >
                        {option.label}
                    </button>
                ))}
            </div>
        </FieldGroup>
    );
}
