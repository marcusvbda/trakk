import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ColorField } from '@/components/trakk/color-field';
import { InlineDelete } from '@/components/trakk/inline-delete';
import { SegmentedField } from '@/components/trakk/segmented-field';
import { SelectField } from '@/components/trakk/select-field';
import { TrakkSheet } from '@/components/trakk/sheet';
import { TextField } from '@/components/trakk/text-field';
import { TextareaField } from '@/components/trakk/textarea-field';
import { TrakkButton } from '@/components/ui/trakk-button';
import { destroy, store, update } from '@/routes/goals';
import { update as setStatus } from '@/routes/goals/status';
import type {
    GoalColor,
    GoalForm,
    GoalStatus,
    GoalType,
    Priority,
} from '@/types';

type GoalFormValues = {
    title: string;
    type: GoalType;
    color: GoalColor;
    priority: Priority;
    start_date: string;
    due_date: string;
    target_per_week: number | null;
    area: string;
    related_goal_id: number | null;
    description: string;
    done_criteria: string;
    slug: string;
};

const types: Array<{ value: GoalType; label: string }> = [
    { value: 'habit', label: 'habit' },
    { value: 'project', label: 'project' },
];

const priorities: Array<{ value: Priority; label: string }> = [
    { value: 'high', label: 'high' },
    { value: 'medium', label: 'medium' },
    { value: 'low', label: 'low' },
];

const targets = [1, 2, 3, 4, 5, 6, 7].map((value) => ({
    value,
    label: String(value),
}));

const statuses: Array<{ value: GoalStatus; label: string }> = [
    { value: 'active', label: 'active' },
    { value: 'paused', label: 'paused' },
    { value: 'done', label: 'done' },
    { value: 'abandoned', label: 'abandoned' },
];

const defaultTarget = 5;

type GoalSheetProps = {
    open: boolean;
    goal?: GoalForm;
    onClose: () => void;
};

export function GoalSheet({ open, goal, onClose }: GoalSheetProps) {
    const { sheetOptions } = usePage().props;
    const form = useForm<GoalFormValues>({
        title: goal?.title ?? '',
        type: goal?.type ?? 'project',
        color: goal?.color ?? 'blue',
        priority: goal?.priority ?? 'medium',
        start_date: goal?.startDate ?? sheetOptions?.today ?? '',
        due_date: goal?.dueDate ?? sheetOptions?.defaultDueDate ?? '',
        target_per_week: goal?.targetPerWeek ?? null,
        area: goal?.area ?? '',
        related_goal_id: goal?.relatedGoalId ?? null,
        description: goal?.description ?? '',
        done_criteria: goal?.doneCriteria ?? '',
        slug: goal?.slug ?? '',
    });
    const [status, setLocalStatus] = useState<GoalStatus>(
        goal?.status ?? 'active',
    );
    const [isDeleting, setIsDeleting] = useState(false);
    const relatedOptions = [
        { value: '', label: 'none' },
        ...(sheetOptions?.goals ?? [])
            .filter((option) => option.slug !== goal?.slug)
            .map((option) => ({
                value: String(option.id),
                label: option.title,
            })),
    ];

    function chooseType(type: GoalType) {
        form.setData((data) => ({
            ...data,
            type,
            target_per_week:
                type === 'habit'
                    ? (data.target_per_week ?? defaultTarget)
                    : null,
        }));
    }

    function changeStatus(next: GoalStatus) {
        if (!goal) {
            return;
        }

        setLocalStatus(next);
        router.patch(
            setStatus.url(goal.slug),
            { status: next },
            { preserveScroll: true, only: ['goal', 'form', 'sheetOptions'] },
        );
    }

    function submit() {
        const options = { preserveScroll: true, onSuccess: onClose };

        if (goal) {
            form.patch(update.url(goal.slug), options);

            return;
        }

        form.post(store.url(), options);
    }

    function remove() {
        if (!goal) {
            return;
        }

        setIsDeleting(true);
        router.delete(destroy.url(goal.slug), {
            onSuccess: onClose,
            onFinish: () => setIsDeleting(false),
        });
    }

    return (
        <TrakkSheet
            open={open}
            onOpenChange={(next) => !next && onClose()}
            title={goal ? 'Edit goal' : 'New goal'}
            description="Set what the goal is, when it runs and how it is measured."
            footer={
                <>
                    <TrakkButton onClick={onClose}>cancel</TrakkButton>
                    <TrakkButton
                        variant="accent"
                        disabled={form.processing}
                        aria-busy={form.processing}
                        onClick={submit}
                    >
                        {goal ? 'save' : 'create goal'}
                    </TrakkButton>
                </>
            }
        >
            <TextField
                id="goal-title"
                label="title"
                value={form.data.title}
                error={form.errors.title}
                onChange={(event) => form.setData('title', event.target.value)}
            />
            <SegmentedField
                id="goal-type"
                label="type"
                value={form.data.type}
                options={types}
                onChange={chooseType}
                error={form.errors.type}
                disabled={goal?.hasTasks}
                hint={
                    goal?.hasTasks
                        ? 'locked: the goal already has tasks'
                        : undefined
                }
            />
            <ColorField
                value={form.data.color}
                error={form.errors.color}
                onChange={(color) => form.setData('color', color)}
            />
            <SegmentedField
                id="goal-priority"
                label="priority"
                value={form.data.priority}
                options={priorities}
                error={form.errors.priority}
                onChange={(priority) => form.setData('priority', priority)}
            />
            <div className="grid grid-cols-2 gap-3.5">
                <TextField
                    id="goal-start"
                    label="start"
                    type="date"
                    value={form.data.start_date}
                    error={form.errors.start_date}
                    onChange={(event) =>
                        form.setData('start_date', event.target.value)
                    }
                />
                <TextField
                    id="goal-due"
                    label="due"
                    type="date"
                    value={form.data.due_date}
                    error={form.errors.due_date}
                    onChange={(event) =>
                        form.setData('due_date', event.target.value)
                    }
                />
            </div>
            {form.data.type === 'habit' && (
                <SegmentedField
                    id="goal-target"
                    label="days per week"
                    value={form.data.target_per_week ?? defaultTarget}
                    options={targets}
                    error={form.errors.target_per_week}
                    onChange={(value) => form.setData('target_per_week', value)}
                />
            )}
            <TextField
                id="goal-area"
                label="area"
                value={form.data.area}
                error={form.errors.area}
                onChange={(event) => form.setData('area', event.target.value)}
            />
            <SelectField
                id="goal-related"
                label="related goal"
                value={
                    form.data.related_goal_id === null
                        ? ''
                        : String(form.data.related_goal_id)
                }
                options={relatedOptions}
                error={form.errors.related_goal_id}
                onChange={(event) =>
                    form.setData(
                        'related_goal_id',
                        event.target.value === ''
                            ? null
                            : Number(event.target.value),
                    )
                }
            />
            <TextareaField
                id="goal-description"
                label="objective"
                value={form.data.description}
                error={form.errors.description}
                onChange={(event) =>
                    form.setData('description', event.target.value)
                }
            />
            <TextareaField
                id="goal-done-criteria"
                label="done when"
                value={form.data.done_criteria}
                error={form.errors.done_criteria}
                onChange={(event) =>
                    form.setData('done_criteria', event.target.value)
                }
            />
            {goal && (
                <>
                    <TextField
                        id="goal-slug"
                        label="slug"
                        value={form.data.slug}
                        error={form.errors.slug}
                        onChange={(event) =>
                            form.setData('slug', event.target.value)
                        }
                    />
                    <SegmentedField
                        id="goal-status"
                        label="status"
                        value={status}
                        options={statuses}
                        hint="applies at once, without saving"
                        onChange={changeStatus}
                    />
                    <InlineDelete
                        id="goal-delete"
                        label="delete goal"
                        confirmPhrase={goal.title}
                        processing={isDeleting}
                        onConfirm={remove}
                    />
                </>
            )}
        </TrakkSheet>
    );
}
