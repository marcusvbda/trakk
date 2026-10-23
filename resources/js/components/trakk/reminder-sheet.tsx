import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { FieldError } from '@/components/trakk/field-error';
import { FieldGroup } from '@/components/trakk/field-group';
import { InlineDelete } from '@/components/trakk/inline-delete';
import { SegmentedField } from '@/components/trakk/segmented-field';
import { SelectField } from '@/components/trakk/select-field';
import { TrakkSheet } from '@/components/trakk/sheet';
import { TextField } from '@/components/trakk/text-field';
import { WeekdayField } from '@/components/trakk/weekday-field';
import { TrakkButton } from '@/components/ui/trakk-button';
import { TrakkToggle } from '@/components/ui/trakk-toggle';
import { destroy, store, update } from '@/routes/reminders';
import type { ReminderChannel, ReminderForm, ReminderKind } from '@/types';

type Schedule = 'weekly' | 'once';

type ReminderFormValues = {
    kind: ReminderKind;
    label: string;
    time: string;
    goal_id: number | null;
    days_of_week: number[];
    fire_on: string;
    channels: ReminderChannel[];
    is_active: boolean;
};

const kinds: Array<{ value: ReminderKind; label: string }> = [
    { value: 'daily_brief', label: 'daily brief' },
    { value: 'evening_checkin', label: 'evening check-in' },
    { value: 'due_tomorrow', label: 'due tomorrow' },
    { value: 'weekly_review', label: 'weekly review' },
    { value: 'off_pace', label: 'off pace' },
    { value: 'pace_summary', label: 'pace summary' },
    { value: 'deadline_alert', label: 'deadline alert' },
    { value: 'custom', label: 'custom' },
];

const kindLabel = Object.fromEntries(
    kinds.map((kind) => [kind.value, kind.label]),
) as Record<ReminderKind, string>;

const goalKinds: ReminderKind[] = ['pace_summary', 'deadline_alert'];

const schedules: Array<{ value: Schedule; label: string }> = [
    { value: 'weekly', label: 'repeats weekly' },
    { value: 'once', label: 'once' },
];

const channels: Array<{ value: ReminderChannel; label: string }> = [
    { value: 'telegram', label: 'telegram' },
    { value: 'email', label: 'email' },
];

const everyDay = [1, 2, 3, 4, 5, 6, 7];

type ReminderSheetProps = {
    open: boolean;
    goalSlug?: string;
    reminder?: ReminderForm;
    onClose: () => void;
};

export function ReminderSheet({
    open,
    goalSlug,
    reminder,
    onClose,
}: ReminderSheetProps) {
    const { sheetOptions } = usePage().props;
    const goals = sheetOptions?.goals ?? [];
    const presetGoalId = goals.find((goal) => goal.slug === goalSlug)?.id;
    const form = useForm<ReminderFormValues>({
        kind: reminder?.kind ?? (presetGoalId ? 'pace_summary' : 'custom'),
        label: reminder?.label ?? (presetGoalId ? kindLabel.pace_summary : ''),
        time: reminder?.time ?? '09:00',
        goal_id: reminder?.goalId ?? presetGoalId ?? null,
        days_of_week: reminder?.daysOfWeek ?? everyDay,
        fire_on: reminder?.fireOn ?? '',
        channels: reminder?.channels ?? ['telegram'],
        is_active: reminder?.isActive ?? true,
    });
    const [schedule, setSchedule] = useState<Schedule>(
        reminder?.fireOn ? 'once' : 'weekly',
    );
    const [isDeleting, setIsDeleting] = useState(false);
    const needsGoal = goalKinds.includes(form.data.kind);

    function chooseKind(kind: ReminderKind) {
        form.setData((data) => ({
            ...data,
            kind,
            label:
                data.label === '' || data.label === kindLabel[data.kind]
                    ? kindLabel[kind]
                    : data.label,
        }));
    }

    function toggleChannel(channel: ReminderChannel) {
        form.setData(
            'channels',
            form.data.channels.includes(channel)
                ? form.data.channels.filter((selected) => selected !== channel)
                : [...form.data.channels, channel],
        );
    }

    function submit() {
        form.transform((data) => ({
            ...data,
            days_of_week: schedule === 'weekly' ? data.days_of_week : null,
            fire_on: schedule === 'once' ? data.fire_on : null,
        }));

        const options = { preserveScroll: true, onSuccess: onClose };

        if (reminder) {
            form.patch(update.url(reminder.id), options);

            return;
        }

        form.post(store.url(), options);
    }

    function remove() {
        if (!reminder) {
            return;
        }

        setIsDeleting(true);
        router.delete(destroy.url(reminder.id), {
            preserveScroll: true,
            onSuccess: onClose,
            onFinish: () => setIsDeleting(false),
        });
    }

    return (
        <TrakkSheet
            open={open}
            onOpenChange={(next) => !next && onClose()}
            title={reminder ? 'Edit reminder' : 'New reminder'}
            description="Choose what to be reminded of, when and where it is sent."
            footer={
                <>
                    <TrakkButton onClick={onClose}>cancel</TrakkButton>
                    <TrakkButton
                        variant="accent"
                        disabled={form.processing}
                        aria-busy={form.processing}
                        onClick={submit}
                    >
                        {reminder ? 'save' : 'add reminder'}
                    </TrakkButton>
                </>
            }
        >
            <TextField
                id="reminder-label"
                label="label"
                value={form.data.label}
                error={form.errors.label}
                onChange={(event) => form.setData('label', event.target.value)}
            />
            <SegmentedField
                id="reminder-kind"
                label="kind"
                value={form.data.kind}
                options={kinds}
                error={form.errors.kind}
                onChange={chooseKind}
            />
            <SelectField
                id="reminder-goal"
                label={needsGoal ? 'goal' : 'goal (optional)'}
                value={
                    form.data.goal_id === null ? '' : String(form.data.goal_id)
                }
                options={[
                    { value: '', label: 'none' },
                    ...goals.map((goal) => ({
                        value: String(goal.id),
                        label: goal.title,
                    })),
                ]}
                error={form.errors.goal_id}
                onChange={(event) =>
                    form.setData(
                        'goal_id',
                        event.target.value === ''
                            ? null
                            : Number(event.target.value),
                    )
                }
            />
            <TextField
                id="reminder-time"
                label="time"
                type="time"
                value={form.data.time}
                error={form.errors.time}
                onChange={(event) => form.setData('time', event.target.value)}
            />
            <SegmentedField
                id="reminder-schedule"
                label="schedule"
                value={schedule}
                options={schedules}
                onChange={setSchedule}
            />
            {schedule === 'weekly' ? (
                <WeekdayField
                    id="reminder-days"
                    label="repeats on"
                    value={form.data.days_of_week}
                    error={form.errors.days_of_week}
                    onChange={(days) => form.setData('days_of_week', days)}
                />
            ) : (
                <TextField
                    id="reminder-date"
                    label="date"
                    type="date"
                    value={form.data.fire_on}
                    error={form.errors.fire_on ?? form.errors.days_of_week}
                    onChange={(event) =>
                        form.setData('fire_on', event.target.value)
                    }
                />
            )}
            <FieldGroup id="reminder-channels" label="send through">
                {channels.map((channel) => (
                    <div
                        key={channel.value}
                        className="flex min-h-11 items-center justify-between"
                    >
                        <span className="font-mono text-xs text-body">
                            {channel.label}
                        </span>
                        <TrakkToggle
                            pressed={form.data.channels.includes(channel.value)}
                            label={`Send through ${channel.label}`}
                            onToggle={() => toggleChannel(channel.value)}
                        />
                    </div>
                ))}
                <FieldError
                    id="reminder-channels-error"
                    message={form.errors.channels}
                />
            </FieldGroup>
            <div className="flex min-h-11 items-center justify-between">
                <span className="font-mono text-xs text-body">active</span>
                <TrakkToggle
                    pressed={form.data.is_active}
                    label="Reminder active"
                    onToggle={() =>
                        form.setData('is_active', !form.data.is_active)
                    }
                />
            </div>
            {reminder && (
                <InlineDelete
                    id="reminder-delete"
                    label="delete reminder"
                    processing={isDeleting}
                    onConfirm={remove}
                />
            )}
        </TrakkSheet>
    );
}
