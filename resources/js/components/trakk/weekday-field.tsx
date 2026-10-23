import { FieldGroup } from '@/components/trakk/field-group';
import { cn } from '@/lib/utils';

const weekdays = [
    { letter: 'M', name: 'monday' },
    { letter: 'T', name: 'tuesday' },
    { letter: 'W', name: 'wednesday' },
    { letter: 'T', name: 'thursday' },
    { letter: 'F', name: 'friday' },
    { letter: 'S', name: 'saturday' },
    { letter: 'S', name: 'sunday' },
];

type WeekdayFieldProps = {
    id: string;
    label: string;
    value: number[];
    onChange: (days: number[]) => void;
    error?: string;
};

export function WeekdayField({
    id,
    label,
    value,
    onChange,
    error,
}: WeekdayFieldProps) {
    function toggle(day: number) {
        onChange(
            value.includes(day)
                ? value.filter((selected) => selected !== day)
                : [...value, day],
        );
    }

    return (
        <FieldGroup id={id} label={label} error={error}>
            <div className="flex gap-1.5">
                {weekdays.map((weekday, index) => (
                    <button
                        key={weekday.name}
                        type="button"
                        aria-label={weekday.name}
                        aria-pressed={value.includes(index + 1)}
                        onClick={() => toggle(index + 1)}
                        className={cn(
                            'size-7 rounded-control border font-mono text-xs',
                            value.includes(index + 1)
                                ? 'border-line-strong bg-selected text-fg'
                                : 'border-line text-muted',
                        )}
                    >
                        {weekday.letter}
                    </button>
                ))}
            </div>
        </FieldGroup>
    );
}
