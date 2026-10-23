import { useState } from 'react';
import { formatMinutes, percentOf } from '@/lib/format';
import { moveCardBetweenColumns, taskStatusLabel } from '@/lib/kanban';
import type { GoalDetailProps, LogEntry, TaskStatus } from '@/types';

const maxHistoryEntries = 8;

function historyKind(to: TaskStatus): LogEntry['kind'] {
    if (to === 'done') {
        return 'done';
    }

    return to === 'todo' ? 'reopened' : 'sent';
}

// Mock-phase interactions: counts and percent move to the server in M4.
export function useGoalBoardState(props: GoalDetailProps) {
    const [columns, setColumns] = useState(props.board.columns);
    const [history, setHistory] = useState(props.history);

    function moveTask(cardId: string, from: TaskStatus, to: TaskStatus) {
        const card = columns
            .find((column) => column.status === from)
            ?.cards.find((candidate) => candidate.id === cardId);

        if (!card) {
            return;
        }

        setColumns((current) =>
            moveCardBetweenColumns(current, cardId, from, to),
        );
        setHistory((entries) =>
            [
                {
                    id: `local-${crypto.randomUUID()}`,
                    timeLabel: `today ${formatMinutes(new Date())}`,
                    kind: historyKind(to),
                    text: `${card.title.toLowerCase()} → ${taskStatusLabel[to]}`,
                    trailing: null,
                },
                ...entries,
            ].slice(0, maxHistoryEntries),
        );
    }

    const countOf = (status: TaskStatus) =>
        columns.find((column) => column.status === status)?.cards.length ?? 0;
    const doneCount = countOf('done');
    const overdueCount = columns
        .filter((column) => column.status !== 'done')
        .flatMap((column) => column.cards)
        .filter((card) => card.isOverdue).length;

    return {
        columns,
        history,
        moveTask,
        counts: {
            todo: countOf('todo'),
            doing: countOf('doing'),
            done: doneCount,
        },
        doneCount,
        percent: Math.round(percentOf(doneCount, props.progress.totalCount)),
        overdueCount,
    };
}
