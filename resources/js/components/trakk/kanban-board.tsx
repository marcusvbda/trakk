import { EmptyState } from '@/components/trakk/empty-state';
import { KanbanCard } from '@/components/trakk/kanban-card';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import { cn } from '@/lib/utils';
import type { KanbanColumnData, TaskStatus } from '@/types';

type ColumnMeta = {
    label: string;
    dotClassName: string;
    next: TaskStatus | null;
    nextLabel: string;
    emptyText: string;
};

const columnMeta: Record<TaskStatus, ColumnMeta> = {
    todo: {
        label: 'to do',
        dotClassName: 'text-muted',
        next: 'doing',
        nextLabel: 'doing',
        emptyText: 'no tasks',
    },
    doing: {
        label: 'doing',
        dotClassName: 'text-warning',
        next: 'done',
        nextLabel: 'done',
        emptyText: 'nothing in progress',
    },
    done: {
        label: 'done',
        dotClassName: 'text-accent',
        next: null,
        nextLabel: '',
        emptyText: 'nothing done yet',
    },
};

type KanbanBoardProps = {
    columns: KanbanColumnData[];
    goalSlug: string;
    variant: 'compact' | 'full';
    onMove: (cardId: number, from: TaskStatus, to: TaskStatus) => void;
};

export function KanbanBoard({
    columns,
    goalSlug,
    variant,
    onMove,
}: KanbanBoardProps) {
    const sheets = useSheets();
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
                            {column.cards.length === 0 && hiddenCount === 0 && (
                                <EmptyState
                                    size="compact"
                                    action={
                                        column.status === 'todo' ? (
                                            <TrakkButton
                                                size="sm"
                                                onClick={() =>
                                                    sheets.open({
                                                        kind: 'task',
                                                        goalSlug,
                                                    })
                                                }
                                            >
                                                + task
                                            </TrakkButton>
                                        ) : undefined
                                    }
                                >
                                    {meta.emptyText}
                                </EmptyState>
                            )}
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
                                    onOpen={() =>
                                        sheets.open({
                                            kind: 'task',
                                            task: card.task,
                                        })
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
