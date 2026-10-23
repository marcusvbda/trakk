import { useState } from 'react';
import { EmptyState } from '@/components/trakk/empty-state';
import { KanbanCard } from '@/components/trakk/kanban-card';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import { cn } from '@/lib/utils';
import type { KanbanColumnData, TaskStatus } from '@/types';

type ColumnMeta = {
    label: string;
    dotClassName: string;
    emptyText: string;
};

const columnMeta: Record<TaskStatus, ColumnMeta> = {
    todo: {
        label: 'to do',
        dotClassName: 'text-muted',
        emptyText: 'no tasks',
    },
    doing: {
        label: 'doing',
        dotClassName: 'text-warning',
        emptyText: 'nothing in progress',
    },
    done: {
        label: 'done',
        dotClassName: 'text-accent',
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
    const [dragged, setDragged] = useState<{
        id: number;
        from: TaskStatus;
    } | null>(null);
    const [overStatus, setOverStatus] = useState<TaskStatus | null>(null);

    function endDrag() {
        setDragged(null);
        setOverStatus(null);
    }

    function dropOn(to: TaskStatus) {
        if (dragged && dragged.from !== to) {
            onMove(dragged.id, dragged.from, to);
        }

        endDrag();
    }

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
                    const isDropTarget =
                        dragged !== null &&
                        dragged.from !== column.status &&
                        overStatus === column.status;

                    return (
                        <div
                            key={column.status}
                            onDragOver={(event) => {
                                if (!dragged) {
                                    return;
                                }

                                event.preventDefault();
                                setOverStatus(column.status);
                            }}
                            onDragLeave={(event) => {
                                if (
                                    !event.currentTarget.contains(
                                        event.relatedTarget as Node | null,
                                    )
                                ) {
                                    setOverStatus(null);
                                }
                            }}
                            onDrop={(event) => {
                                event.preventDefault();
                                dropOn(column.status);
                            }}
                            className={cn(
                                'flex flex-col gap-1.5 rounded-[5px] border bg-sunken p-2 transition-colors duration-200',
                                isDropTarget
                                    ? 'border-accent'
                                    : 'border-divider',
                            )}
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
                                    isDragging={dragged?.id === card.id}
                                    onDragStart={() =>
                                        setDragged({
                                            id: card.id,
                                            from: column.status,
                                        })
                                    }
                                    onDragEnd={endDrag}
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
