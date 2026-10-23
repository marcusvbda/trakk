import { cn } from '@/lib/utils';

export function TrakkCheckbox({ checked }: { checked: boolean }) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'inline-flex size-3.5 flex-none items-center justify-center rounded-[3px] border transition-colors',
                checked
                    ? 'border-accent bg-accent'
                    : 'border-dim group-hover:border-muted',
            )}
        >
            {checked && (
                <svg width="10" height="10" viewBox="0 0 10 10">
                    <path
                        d="M2 5.2 L4.2 7.3 L8 2.8"
                        fill="none"
                        stroke="var(--color-canvas)"
                        strokeWidth="1.6"
                    />
                </svg>
            )}
        </span>
    );
}
