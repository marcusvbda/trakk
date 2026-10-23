import { KanbanCard } from '@/components/trakk/kanban-card';
import { cn } from '@/lib/utils';
import type { KanbanColumnData, TaskStatus } from '@/types';

type ColumnMeta = {
    label: string;
    dotClassName: string;
    next: TaskStatus | null;
    nextLabel: string;
};

const columnMeta: Record<TaskStatus, ColumnMeta> = {
    todo: {
        label: 'to do',
        dotClassName: 'text-muted',
        next: 'doing',
        nextLabel: 'doing',
    },
    doing: {
        label: 'doing',
        dotClassName: 'text-warning',
        next: 'done',
        nextLabel: 'done',
    },
    done: {
        label: 'done',
        dotClassName: 'text-accent',
        next: null,
        nextLabel: '',
    },
};

type KanbanBoardProps = {
    columns: KanbanColumnData[];
    variant: 'compact' | 'full';
    onMove: (cardId: string, from: TaskStatus, to: TaskStatus) => void;
};

export function KanbanBoard({ columns, variant, onMove }: KanbanBoardProps) {
    const isFull = variant === 'full';

    return (
        <div className="overflow-x-auto">
            <div
                className={cn(
                    'grid grid-cols-3 gap-2.5',
                    isFull ? 'min-w-[640px] items-start' : 'min-w-[580px]',
                )}
            >
                {columns.map((column) => {
                    const meta = columnMeta[column.status];
                    const hiddenCount = column.hiddenCount ?? 0;
                    const next =
                        column.status === 'done' && isFull ? 'todo' : meta.next;
                    const nextLabel =
                        column.status === 'done' && isFull
                            ? 'to do'
                            : meta.nextLabel;

                    return (
                        <div
                            key={column.status}
                            className="flex flex-col gap-1.5 rounded-[5px] border border-divider bg-sunken p-2"
                        >
                            <div className="flex justify-between px-0.5 pt-0.5 pb-1 font-mono text-xs tracking-widest text-fg-2 uppercase">
                                <span>
                                    <span className={meta.dotClassName}>●</span>{' '}
                                    {meta.label}
                                </span>
                                <span className="text-faint">
                                    {column.cards.length + hiddenCount}
                                </span>
                            </div>
                            {column.cards.map((card) => (
                                <KanbanCard
                                    key={card.id}
                                    card={card}
                                    status={column.status}
                                    isFull={isFull}
                                    moveLabel={
                                        next
                                            ? column.status === 'done'
                                                ? `Reopen "${card.title}"`
                                                : `Move "${card.title}" to ${nextLabel}`
                                            : null
                                    }
                                    moveGlyph={
                                        column.status === 'done' ? '↺' : '→'
                                    }
                                    onMove={() =>
                                        next &&
                                        onMove(card.id, column.status, next)
                                    }
                                />
                            ))}
                            {hiddenCount > 0 && (
                                <div className="px-0.5 pt-0.5 font-mono text-xs text-faint">
                                    + {hiddenCount} tasks on board
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
