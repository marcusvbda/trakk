import type { KanbanColumnData, TaskStatus } from '@/types';

export const taskStatusLabel: Record<TaskStatus, string> = {
    todo: 'to do',
    doing: 'doing',
    done: 'done',
};

export function moveCardBetweenColumns(
    columns: KanbanColumnData[],
    cardId: number,
    from: TaskStatus,
    to: TaskStatus,
): KanbanColumnData[] {
    const card = columns
        .find((column) => column.status === from)
        ?.cards.find((candidate) => candidate.id === cardId);

    if (!card) {
        return columns;
    }

    return columns.map((column) => {
        if (column.status === from) {
            return {
                ...column,
                cards: column.cards.filter(
                    (candidate) => candidate.id !== cardId,
                ),
            };
        }

        return column.status === to
            ? { ...column, cards: [...column.cards, card] }
            : column;
    });
}
