import type { Pace, PaceStatus } from '@/types';

const paceLabel: Record<PaceStatus, string> = {
    behind: 'behind',
    watch: 'watch',
    on_pace: 'on pace',
    ahead: 'ahead',
};

export function formatPace({ status, delta }: Pace): string {
    if (delta === 0) {
        return paceLabel[status];
    }

    const signed = delta > 0 ? `+${delta}` : String(delta);

    return `${paceLabel[status]} ${signed}%`;
}

export function formatLate(days: number): string {
    return `−${days}d`;
}

export function formatPoints(points: number): string {
    return `+${points}`;
}

export function formatChecklist(done: number, total: number): string {
    return `${done}/${total} items`;
}

export function formatClock(date: Date): string {
    return [date.getHours(), date.getMinutes(), date.getSeconds()]
        .map((part) => String(part).padStart(2, '0'))
        .join(':');
}

export function formatMinutes(date: Date): string {
    return formatClock(date).slice(0, 5);
}
