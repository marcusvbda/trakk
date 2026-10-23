import { FieldGroup } from '@/components/trakk/field-group';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { GoalColor } from '@/types';

const colors = Object.keys(goalColorClass) as GoalColor[];

type ColorFieldProps = {
    value: GoalColor;
    onChange: (color: GoalColor) => void;
    error?: string;
};

export function ColorField({ value, onChange, error }: ColorFieldProps) {
    return (
        <FieldGroup id="color" label="color" error={error}>
            <div className="flex flex-wrap gap-2.5">
                {colors.map((color) => (
                    <button
                        key={color}
                        type="button"
                        aria-label={color}
                        aria-pressed={value === color}
                        onClick={() => onChange(color)}
                        className={cn(
                            'size-[22px] rounded-[3px] outline-offset-2',
                            goalColorClass[color].bg,
                            value === color && 'outline outline-1 outline-fg',
                        )}
                    />
                ))}
            </div>
        </FieldGroup>
    );
}
