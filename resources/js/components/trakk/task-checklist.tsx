import { router, useForm } from '@inertiajs/react';
import { EmptyState } from '@/components/trakk/empty-state';
import { FieldError } from '@/components/trakk/field-error';
import { TrakkCheckbox } from '@/components/ui/trakk-checkbox';
import { cn } from '@/lib/utils';
import { destroy, update } from '@/routes/checklist';
import { store } from '@/routes/tasks/checklist';
import type { TaskSheet } from '@/types';

const refreshedProps = ['board', 'actions', 'focusProject'];

export function TaskChecklist({ task }: { task: TaskSheet | undefined }) {
    const form = useForm({ title: '' });

    if (!task) {
        return (
            <section aria-label="Checklist" className="flex flex-col gap-2">
                <EmptyState size="compact">
                    save the task, then add its checklist
                </EmptyState>
            </section>
        );
    }

    function toggle(id: number, isDone: boolean) {
        router.patch(
            update.url(id),
            { is_done: !isDone },
            { preserveScroll: true, only: refreshedProps },
        );
    }

    function remove(id: number) {
        router.delete(destroy.url(id), {
            preserveScroll: true,
            only: refreshedProps,
        });
    }

    function add() {
        if (!task || form.data.title.trim() === '') {
            return;
        }

        form.post(store.url(task.id), {
            preserveScroll: true,
            only: refreshedProps,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <section aria-label="Checklist" className="flex flex-col gap-1.5">
            <h3 className="font-mono text-2xs tracking-[0.12em] text-muted uppercase">
                checklist · {task.checklist.filter((i) => i.isDone).length}/
                {task.checklist.length}
            </h3>
            {task.checklist.length === 0 && (
                <EmptyState size="compact">add the first item</EmptyState>
            )}
            {task.checklist.map((item) => (
                <div
                    key={item.id}
                    className="group flex min-h-11 items-center gap-1 rounded-control px-1 transition-colors duration-150 hover:bg-hover"
                >
                    <button
                        type="button"
                        aria-pressed={item.isDone}
                        onClick={() => toggle(item.id, item.isDone)}
                        className="flex min-h-11 min-w-0 flex-1 items-center gap-2.5 text-left"
                    >
                        <TrakkCheckbox checked={item.isDone} />
                        <span
                            className={cn(
                                'min-w-0 flex-1 truncate text-base',
                                item.isDone
                                    ? 'text-faint line-through'
                                    : 'text-body',
                            )}
                        >
                            {item.title}
                        </span>
                    </button>
                    <button
                        type="button"
                        aria-label={`Delete "${item.title}"`}
                        onClick={() => remove(item.id)}
                        className="size-11 flex-none font-mono text-md text-faint transition-opacity hover:text-danger [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-focus-within:opacity-100 [@media(hover:hover)]:group-hover:opacity-100"
                    >
                        ×
                    </button>
                </div>
            ))}
            <div className="flex h-11 items-center gap-2 rounded-button border border-line bg-field pl-3 transition-colors focus-within:border-accent hover:border-line-hover">
                <span
                    aria-hidden="true"
                    className="font-mono text-[12px] text-accent"
                >
                    ›
                </span>
                <input
                    aria-label="Add checklist item"
                    placeholder="add item"
                    value={form.data.title}
                    onChange={(event) =>
                        form.setData('title', event.target.value)
                    }
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            add();
                        }
                    }}
                    className="h-10 min-w-0 flex-1 bg-transparent font-mono text-[12px] text-fg outline-none"
                />
            </div>
            <FieldError id="checklist-error" message={form.errors.title} />
        </section>
    );
}
