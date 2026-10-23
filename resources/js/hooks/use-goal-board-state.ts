import { router } from '@inertiajs/react';
import { formatMinutes } from '@/lib/format';
import { moveCardBetweenColumns, taskStatusLabel } from '@/lib/kanban';
import { update as setTaskStatus } from '@/routes/tasks/status';
import type { GoalDetailProps, LogEntry, TaskStatus } from '@/types';

const maxHistoryEntries = 20;

const refreshAfterTaskChange = [
    'goal',
    'progress',
    'paceChart',
    'board',
    'history',
];

function historyKind(to: TaskStatus): LogEntry['kind'] {
    if (to === 'done') {
        return 'done';
    }

    return to === 'todo' ? 'reopened' : 'sent';
}

export function useGoalBoardState(props: GoalDetailProps) {
    function moveTask(cardId: number, from: TaskStatus, to: TaskStatus) {
        const card = props.board?.columns
            .find((column) => column.status === from)
            ?.cards.find((candidate) => candidate.id === cardId);

        if (!card) {
            return;
        }

        router
            .optimistic<GoalDetailProps>((current) =>
                current.board
                    ? {
                          board: {
                              columns: moveCardBetweenColumns(
                                  current.board.columns,
                                  cardId,
                                  from,
                                  to,
                              ),
                          },
                          history: [
                              {
                                  id: -Date.now(),
                                  timeLabel: formatMinutes(new Date()),
                                  kind: historyKind(to),
                                  text: `${card.title.toLowerCase()} → ${taskStatusLabel[to]}`,
                                  trailing: null,
                              },
                              ...current.history,
                          ].slice(0, maxHistoryEntries),
                      }
                    : {},
            )
            .patch(
                setTaskStatus.url(cardId),
                { status: to },
                { preserveScroll: true, only: refreshAfterTaskChange },
            );
    }

    return { moveTask };
}
