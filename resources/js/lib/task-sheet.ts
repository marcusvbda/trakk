import type {
    FocusProject,
    HabitAction,
    KanbanColumnData,
    TaskSheet,
} from '@/types';

export type PageWithTasks = {
    board?: { columns: KanbanColumnData[] } | null;
    actions?: HabitAction[] | null;
    focusProject?: FocusProject | null;
};

/** The sheet's task as the page now has it, so checklist changes show up while the sheet is open. */
export function findTaskSheet(
    page: PageWithTasks,
    taskId: number,
): TaskSheet | null {
    const columns = [
        ...(page.board?.columns ?? []),
        ...(page.focusProject?.columns ?? []),
    ];
    const fromColumns = columns
        .flatMap((column) => column.cards)
        .find((card) => card.task.id === taskId)?.task;

    return (
        fromColumns ??
        page.actions?.find((action) => action.task.id === taskId)?.task ??
        null
    );
}
