import { TrakkButton } from '@/components/ui/trakk-button';
import { ProgressBar } from '@/components/ui/progress-bar';
import { formatChecklist, percentOf } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { KanbanCardData, TaskStatus } from '@/types';

type KanbanCardProps = {
    card: KanbanCardData;
    status: TaskStatus;
    moveLabel: string | null;
    moveGlyph: string;
    isFull: boolean;
    onMove: () => void;
};

function dueText(card: KanbanCardData, isDone: boolean): string {
    if (isDone) {
        return 'completed';
    }

    return `${card.isOverdue ? 'overdue' : 'due'} ${card.dueLabel}`;
}

export function KanbanCard({
    card,
    status,
    moveLabel,
    moveGlyph,
    isFull,
    onMove,
}: KanbanCardProps) {
    const isDone = status === 'done';
    const isLate = card.isOverdue && !isDone;
    const showsLate = isLate && isFull;
    const checklistDone = isDone ? card.checklistTotal : card.checklistDone;

    return (
        <div
            className={cn(
                'flex animate-fade-in items-start gap-2 rounded-control border bg-raised py-2 pr-2 pl-2.5 transition-colors duration-200',
                showsLate
                    ? 'border-danger-line'
                    : 'border-line hover:border-line-hover',
            )}
        >
            <div className="min-w-0 flex-1">
                <div
                    className={cn(
                        'text-[12px] leading-[1.35]',
                        isDone ? 'text-faint line-through' : 'text-body',
                    )}
                >
                    {card.title}
                </div>
                <div
                    className={cn(
                        'mt-[5px] flex gap-x-2 font-mono text-2xs text-muted',
                        isFull && 'flex-wrap gap-y-1',
                    )}
                >
                    <span>
                        {formatChecklist(checklistDone, card.checklistTotal)}
                    </span>
                    <span className={cn(isLate && 'text-danger')}>
                        {dueText(card, isDone)}
                    </span>
                    {card.tag && !isDone && (
                        <span className="text-warning">{card.tag}</span>
                    )}
                </div>
                {isFull && (
                    <ProgressBar
                        size="xs"
                        className="mt-1.5"
                        percent={percentOf(checklistDone, card.checklistTotal)}
                        fillClassName={
                            isDone
                                ? 'bg-dim'
                                : isLate
                                  ? 'bg-danger'
                                  : 'bg-warning'
                        }
                    />
                )}
            </div>
            {moveLabel && (
                <TrakkButton
                    size="icon"
                    aria-label={moveLabel}
                    onClick={onMove}
                >
                    {moveGlyph}
                </TrakkButton>
            )}
        </div>
    );
}
