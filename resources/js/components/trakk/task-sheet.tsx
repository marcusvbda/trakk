import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { EmptyState } from '@/components/trakk/empty-state';
import { InlineDelete } from '@/components/trakk/inline-delete';
import { SegmentedField } from '@/components/trakk/segmented-field';
import { SelectField } from '@/components/trakk/select-field';
import { TrakkSheet } from '@/components/trakk/sheet';
import { TaskChecklist } from '@/components/trakk/task-checklist';
import { TextField } from '@/components/trakk/text-field';
import { TextareaField } from '@/components/trakk/textarea-field';
import { WeekdayField } from '@/components/trakk/weekday-field';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import type { PageWithTasks } from '@/lib/task-sheet';
import { findTaskSheet } from '@/lib/task-sheet';
import { cn } from '@/lib/utils';
import { destroy, update } from '@/routes/tasks';
import { store } from '@/routes/goals/tasks';
import type { TaskSheet as TaskSheetData, TaskStatus } from '@/types';

type TaskFormValues = {
    title: string;
    points: number;
    status: TaskStatus;
    due_date: string;
    scheduled_time: string;
    days_of_week: number[];
    tag: string;
    notes: string;
};

const points = [1, 2, 3, 4, 5].map((value) => ({
    value,
    label: String(value),
}));

const createStatuses: Array<{ value: TaskStatus; label: string }> = [
    { value: 'todo', label: 'to do' },
    { value: 'doing', label: 'doing' },
];

const editStatuses: Array<{ value: TaskStatus; label: string }> = [
    ...createStatuses,
    { value: 'done', label: 'done' },
];

const quickTags = ['critical', 'milestone'];

const everyDay = [1, 2, 3, 4, 5, 6, 7];

type TaskSheetProps = {
    open: boolean;
    goalSlug?: string;
    task?: TaskSheetData;
    onClose: () => void;
};

export function TaskSheet({ open, goalSlug, task, onClose }: TaskSheetProps) {
    const page = usePage();
    const { sheetOptions } = page.props;
    const sheets = useSheets();
    const goals = sheetOptions?.goals ?? [];
    const currentTask =
        (task && findTaskSheet(page.props as PageWithTasks, task.id)) ?? task;
    const selectableGoals = goals.filter(
        (goal) =>
            goal.id === task?.goalId ||
            goal.status === 'active' ||
            goal.status === 'paused',
    );
    const [goalId, setGoalId] = useState<number | undefined>(
        task?.goalId ??
            goals.find((goal) => goal.slug === goalSlug)?.id ??
            selectableGoals.find((goal) => goal.status === 'active')?.id,
    );
    const goal = goals.find((candidate) => candidate.id === goalId);
    const repeats =
        goal?.type === 'habit' && (!task || task.daysOfWeek !== null);
    const form = useForm<TaskFormValues>({
        title: task?.title ?? '',
        points: task?.points ?? 1,
        status: task?.status ?? 'todo',
        due_date: task?.dueDate ?? '',
        scheduled_time: task?.scheduledTime ?? '',
        days_of_week: task?.daysOfWeek ?? everyDay.slice(0, 5),
        tag: task?.tag ?? '',
        notes: task?.notes ?? '',
    });
    const [isDeleting, setIsDeleting] = useState(false);

    function submit() {
        if (!goal) {
            return;
        }

        form.transform((data) => ({
            ...data,
            status: repeats ? null : data.status,
            due_date: repeats ? '' : data.due_date,
            tag: repeats ? '' : data.tag,
            scheduled_time: repeats ? data.scheduled_time : '',
            days_of_week: repeats ? data.days_of_week : null,
        }));

        const options = { preserveScroll: true, onSuccess: onClose };

        if (task) {
            form.patch(update.url(task.id), options);

            return;
        }

        form.post(store.url(goal.slug), options);
    }

    function remove() {
        if (!task) {
            return;
        }

        setIsDeleting(true);
        router.delete(destroy.url(task.id), {
            preserveScroll: true,
            onSuccess: onClose,
            onFinish: () => setIsDeleting(false),
        });
    }

    return (
        <TrakkSheet
            open={open}
            onOpenChange={(next) => !next && onClose()}
            title={task ? 'Edit task' : 'New task'}
            description="Set what to do, what it is worth and when it is due."
            footer={
                goal && (
                    <>
                        <TrakkButton onClick={onClose}>cancel</TrakkButton>
                        <TrakkButton
                            variant="accent"
                            disabled={form.processing}
                            aria-busy={form.processing}
                            onClick={submit}
                        >
                            {task ? 'save' : 'add task'}
                        </TrakkButton>
                    </>
                )
            }
        >
            {!goal ? (
                <EmptyState
                    glyph="gap"
                    hint="a task belongs to a goal"
                    action={
                        <TrakkButton
                            size="sm"
                            onClick={() => {
                                onClose();
                                sheets.open({ kind: 'goal' });
                            }}
                        >
                            + goal
                        </TrakkButton>
                    }
                >
                    create a goal before adding tasks
                </EmptyState>
            ) : (
                <>
                    <SelectField
                        id="task-goal"
                        label="goal"
                        value={String(goalId)}
                        disabled={task !== undefined}
                        options={selectableGoals.map((option) => ({
                            value: String(option.id),
                            label: option.title,
                        }))}
                        onChange={(event) =>
                            setGoalId(Number(event.target.value))
                        }
                    />
                    <TextField
                        id="task-title"
                        label="title"
                        value={form.data.title}
                        error={form.errors.title}
                        onChange={(event) =>
                            form.setData('title', event.target.value)
                        }
                    />
                    <SegmentedField
                        id="task-points"
                        label="points"
                        value={form.data.points}
                        options={points}
                        error={form.errors.points}
                        onChange={(value) => form.setData('points', value)}
                    />
                    {repeats ? (
                        <>
                            <WeekdayField
                                id="task-days"
                                label="repeats on"
                                value={form.data.days_of_week}
                                error={form.errors.days_of_week}
                                onChange={(days) =>
                                    form.setData('days_of_week', days)
                                }
                            />
                            <TextField
                                id="task-time"
                                label="time (optional)"
                                type="time"
                                value={form.data.scheduled_time}
                                error={form.errors.scheduled_time}
                                onChange={(event) =>
                                    form.setData(
                                        'scheduled_time',
                                        event.target.value,
                                    )
                                }
                            />
                        </>
                    ) : (
                        <>
                            <TextField
                                id="task-due"
                                label="due date"
                                type="date"
                                value={form.data.due_date}
                                error={form.errors.due_date}
                                onChange={(event) =>
                                    form.setData('due_date', event.target.value)
                                }
                            />
                            <div className="flex flex-col gap-2">
                                <TextField
                                    id="task-tag"
                                    label="tag"
                                    value={form.data.tag}
                                    error={form.errors.tag}
                                    onChange={(event) =>
                                        form.setData('tag', event.target.value)
                                    }
                                />
                                <div className="flex gap-1.5">
                                    {quickTags.map((tag) => (
                                        <button
                                            key={tag}
                                            type="button"
                                            aria-pressed={form.data.tag === tag}
                                            onClick={() =>
                                                form.setData(
                                                    'tag',
                                                    form.data.tag === tag
                                                        ? ''
                                                        : tag,
                                                )
                                            }
                                            className={cn(
                                                'h-7 rounded-control border px-2 font-mono text-xs',
                                                form.data.tag === tag
                                                    ? 'border-line-strong bg-selected text-warning'
                                                    : 'border-line text-muted',
                                            )}
                                        >
                                            {tag}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <SegmentedField
                                id="task-status"
                                label="status"
                                value={form.data.status}
                                options={task ? editStatuses : createStatuses}
                                error={form.errors.status}
                                onChange={(status) =>
                                    form.setData('status', status)
                                }
                            />
                        </>
                    )}
                    <TextareaField
                        id="task-notes"
                        label="notes"
                        value={form.data.notes}
                        error={form.errors.notes}
                        onChange={(event) =>
                            form.setData('notes', event.target.value)
                        }
                    />
                    {!repeats && <TaskChecklist task={currentTask} />}
                    {task && (
                        <InlineDelete
                            id="task-delete"
                            label="delete task"
                            processing={isDeleting}
                            onConfirm={remove}
                        />
                    )}
                </>
            )}
        </TrakkSheet>
    );
}
