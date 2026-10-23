import { cn } from '@/lib/utils';

type TrakkToggleProps = {
    pressed: boolean;
    label: string;
    onToggle: () => void;
};

export function TrakkToggle({ pressed, label, onToggle }: TrakkToggleProps) {
    return (
        <button
            type="button"
            aria-pressed={pressed}
            aria-label={label}
            onClick={onToggle}
            className="inline-flex size-11 flex-none items-center justify-center"
        >
            <span
                className={cn(
                    'inline-flex h-3.5 w-[26px] items-center rounded-[7px] border px-0.5',
                    pressed ? 'border-accent bg-accent/12' : 'border-dim',
                )}
            >
                <span
                    className={cn(
                        'size-2 rounded-full transition-[margin,background-color] duration-200',
                        pressed ? 'ml-3 bg-accent' : 'ml-0 bg-faint',
                    )}
                />
            </span>
        </button>
    );
}
