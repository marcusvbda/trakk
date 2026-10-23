import { useForm } from '@inertiajs/react';
import { EmptyState } from '@/components/trakk/empty-state';
import { TrakkSheet } from '@/components/trakk/sheet';
import { TrakkButton } from '@/components/ui/trakk-button';
import { TrakkCheckbox } from '@/components/ui/trakk-checkbox';
import { formatLate } from '@/lib/format';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import { store } from '@/routes/replan';
import type { OverdueItem } from '@/types';

type ReplanTarget = 'today' | 'tomorrow' | 'next_monday' | 'date';

const targets: Array<{ id: ReplanTarget; label: string }> = [
    { id: 'today', label: 'today' },
    { id: 'tomorrow', label: 'tomorrow' },
    { id: 'next_monday', label: 'next monday' },
    { id: 'date', label: 'pick date' },
];

const refreshedProps = [
    'kpis',
    'actions',
    'pointsToday',
    'overdue',
    'suggestion',
    'upcoming',
    'goals',
    'goalsSummary',
    'focusProject',
    'log',
    'hints',
];

type ReplanSheetProps = {
    open: boolean;
    overdue: OverdueItem[];
    onClose: () => void;
};

export function ReplanSheet({ open, overdue, onClose }: ReplanSheetProps) {
    return (
        <TrakkSheet
            open={open}
            onOpenChange={(next) => !next && onClose()}
            title="Replan overdue"
            description="Pick the overdue tasks to move and the day to move them to."
        >
            {open && <ReplanForm overdue={overdue} onClose={onClose} />}
        </TrakkSheet>
    );
}

function ReplanForm({
    overdue,
    onClose,
}: {
    overdue: OverdueItem[];
    onClose: () => void;
}) {
    const form = useForm<{
        task_ids: number[];
        target: ReplanTarget;
        date: string;
    }>({
        task_ids: overdue.map((item) => item.id),
        target: 'today',
        date: '',
    });
    const selected = form.data.task_ids.length;

    function toggle(id: number) {
        form.setData(
            'task_ids',
            form.data.task_ids.includes(id)
                ? form.data.task_ids.filter((taskId) => taskId !== id)
                : [...form.data.task_ids, id],
        );
    }

    function submit() {
        form.post(store().url, {
            preserveScroll: true,
            only: refreshedProps,
            onSuccess: onClose,
        });
    }

    if (overdue.length === 0) {
        return <EmptyState glyph="positive">nothing to replan</EmptyState>;
    }

    return (
        <>
            <div className="flex flex-col">
                {overdue.map((item) => (
                    <button
                        key={item.id}
                        type="button"
                        aria-pressed={form.data.task_ids.includes(item.id)}
                        onClick={() => toggle(item.id)}
                        className="group flex min-h-11 items-center gap-2.5 rounded-control px-2 text-left transition-colors duration-150 hover:bg-hover"
                    >
                        <TrakkCheckbox
                            checked={form.data.task_ids.includes(item.id)}
                        />
                        <span
                            className={cn(
                                'size-1.5 flex-none rounded-full',
                                goalColorClass[item.goalColor].bg,
                            )}
                        />
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-base text-body">
                                {item.title}
                            </span>
                            <span className="block font-mono text-xs text-muted">
                                {item.goalName} · due {item.dueLabel}
                            </span>
                        </span>
                        <span className="flex-none font-mono text-[11px] text-danger">
                            {formatLate(item.daysLate)}
                        </span>
                    </button>
                ))}
            </div>

            <fieldset className="flex flex-col gap-2">
                <legend className="mb-2 font-mono text-2xs tracking-widest text-muted uppercase">
                    move to
                </legend>
                <div className="flex flex-wrap gap-1.5">
                    {targets.map((target) => (
                        <button
                            key={target.id}
                            type="button"
                            aria-pressed={form.data.target === target.id}
                            onClick={() => form.setData('target', target.id)}
                            className={cn(
                                'h-8 rounded-control border px-2.5 font-mono text-xs',
                                form.data.target === target.id
                                    ? 'border-line-strong bg-selected text-fg'
                                    : 'border-line text-muted',
                            )}
                        >
                            {target.label}
                        </button>
                    ))}
                </div>
                {form.data.target === 'date' && (
                    <input
                        type="date"
                        aria-label="Date to move the tasks to"
                        value={form.data.date}
                        onChange={(event) =>
                            form.setData('date', event.target.value)
                        }
                        className="h-11 rounded-control border border-line bg-field px-2.5 font-mono text-[12px] text-fg [color-scheme:dark] outline-none focus:border-accent"
                    />
                )}
                {(form.errors.date || form.errors.task_ids) && (
                    <p role="alert" className="font-mono text-sm text-danger">
                        {form.errors.date ?? form.errors.task_ids}
                    </p>
                )}
            </fieldset>

            <div className="mt-auto flex justify-end gap-2 border-t border-divider pt-4">
                <TrakkButton onClick={onClose}>cancel</TrakkButton>
                <TrakkButton
                    variant="accent"
                    disabled={selected === 0 || form.processing}
                    aria-busy={form.processing}
                    onClick={submit}
                >
                    move {selected} {selected === 1 ? 'task' : 'tasks'}
                </TrakkButton>
            </div>
        </>
    );
}
