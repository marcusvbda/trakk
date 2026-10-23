import { formatPace } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Pace, PaceStatus } from '@/types';

const statusText: Record<PaceStatus, string> = {
    behind: 'text-danger',
    watch: 'text-warning',
    on_pace: 'text-accent',
    ahead: 'text-accent',
};

export function PaceBadge({
    pace,
    className,
}: {
    pace: Pace;
    className?: string;
}) {
    return (
        <span className={cn('font-mono', statusText[pace.status], className)}>
            ● {formatPace(pace)}
        </span>
    );
}
