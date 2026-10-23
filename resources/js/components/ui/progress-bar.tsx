import { cn } from '@/lib/utils';

type ProgressBarProps = {
    percent: number;
    fillClassName: string;
    expectedPercent?: number;
    size?: 'xs' | 'sm' | 'md';
    className?: string;
};

const tracks = {
    xs: 'h-0.5 overflow-hidden rounded-[1px]',
    sm: 'h-[3px] rounded-[2px]',
    md: 'h-1 rounded-[2px]',
} as const;

const markers = {
    xs: '',
    sm: '-top-[3px] h-[9px]',
    md: '-top-1 h-3',
} as const;

export function ProgressBar({
    percent,
    fillClassName,
    expectedPercent,
    size = 'sm',
    className,
}: ProgressBarProps) {
    return (
        <div className={cn('relative bg-divider', tracks[size], className)}>
            <div
                className={cn(
                    'h-full rounded-[inherit] transition-[width] duration-[450ms] ease-out',
                    fillClassName,
                )}
                style={{ width: `${percent}%` }}
            />
            {expectedPercent !== undefined && (
                <div
                    title="expected today"
                    className={cn(
                        'absolute w-px bg-body',
                        markers[size],
                    )}
                    style={{ left: `${expectedPercent}%` }}
                />
            )}
        </div>
    );
}
