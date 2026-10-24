import { router } from '@inertiajs/react';
import { EmptyState } from '@/components/trakk/empty-state';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkCheckbox } from '@/components/ui/trakk-checkbox';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import {
    destroy as undoCompletion,
    store as completeTask,
} from '@/routes/tasks/completions';
import type { GoalColor, HabitAction, HabitDay } from '@/types';

const weekdays = [
    { letter: 'M', name: 'monday' },
    { letter: 'T', name: 'tuesday' },
    { letter: 'W', name: 'wednesday' },
    { letter: 'T', name: 'thursday' },
    { letter: 'F', name: 'friday' },
    { letter: 'S', name: 'saturday' },
    { letter: 'S', name: 'sunday' },
];

const chipBase =
    'inline-flex size-[18px] items-center justify-center rounded-chip border font-mono text-2xs';

function toggleSlot(action: HabitAction, day: HabitDay): void {
    const options = { query: { on: day.date } };
    const route = day.done
        ? undoCompletion(action.id, options)
        : completeTask(action.id, options);

    router.visit(route.url, {
        method: route.method,
        preserveScroll: true,
        only: ['actions', 'progress', 'paceChart', 'goal', 'history', 'weeks'],
    });
}

function scheduledNames(action: HabitAction): string[] {
    return action.weekDays.flatMap((day, index) =>
        day.scheduled ? [weekdays[index].name] : [],
    );
}

function WeekdayChips({
    action,
    color,
}: {
    action: HabitAction;
    color: GoalColor;
}) {
    return (
        <span
            role="img"
            aria-label={`repeats on ${scheduledNames(action).join(', ')}`}
            className="flex gap-[3px]"
        >
            {action.weekDays.map((day, index) => (
                <span
                    key={weekdays[index].name}
                    className={cn(
                        chipBase,
                        day.scheduled
                            ? cn(
                                  goalColorClass[color].bg,
                                  goalColorClass[color].border,
                                  'text-canvas',
                              )
                            : 'border-line-strong text-faint',
                    )}
                >
                    {weekdays[index].letter}
                </span>
            ))}
        </span>
    );
}

function SlotChecklist({ action }: { action: HabitAction }) {
    return (
        <span role="group" aria-label="done this week" className="flex gap-3">
            {action.weekDays.map((day, index) => {
                if (!day.scheduled) {
                    return null;
                }

                const label = weekdays[index].name.slice(0, 3);

                if (!day.canToggle) {
                    return (
                        <span
                            key={day.date}
                            title="not yet"
                            className="flex min-h-8 items-center gap-1.5 font-mono text-xs text-faint"
                        >
                            <TrakkCheckbox checked={false} />
                            {label}
                        </span>
                    );
                }

                return (
                    <button
                        key={day.date}
                        type="button"
                        role="checkbox"
                        aria-checked={day.done}
                        aria-label={`${action.title}, ${weekdays[index].name} done`}
                        onClick={() => toggleSlot(action, day)}
                        className="group flex min-h-8 items-center gap-1.5 font-mono text-xs text-muted"
                    >
                        <TrakkCheckbox checked={day.done} />
                        {label}
                    </button>
                );
            })}
        </span>
    );
}

export function HabitActionsPanel({
    actions,
    color,
    goalSlug,
}: {
    actions: HabitAction[];
    color: GoalColor;
    goalSlug: string;
}) {
    const sheets = useSheets();

    return (
        <Panel aria-labelledby="h-actions">
            <PanelHeader
                id="h-actions"
                title="Actions"
                count={`${actions.length} to repeat`}
            >
                <span className="font-mono text-xs text-muted">
                    weekdays · points · hits this week
                </span>
            </PanelHeader>
            {actions.length === 0 ? (
                <EmptyState
                    hint="add what you will repeat each week"
                    action={
                        <TrakkButton
                            size="sm"
                            onClick={() =>
                                sheets.open({ kind: 'task', goalSlug })
                            }
                        >
                            + task
                        </TrakkButton>
                    }
                >
                    no actions yet
                </EmptyState>
            ) : (
                <div className="flex flex-col">
                    {actions.map((action) => (
                        <div
                            key={action.id}
                            className="flex min-h-11 animate-fade-in flex-wrap items-center gap-x-3 gap-y-1 rounded-control px-1.5 py-1 transition-colors duration-150 hover:bg-hover"
                        >
                            <button
                                type="button"
                                aria-label={`Edit action "${action.title}"`}
                                onClick={() =>
                                    sheets.open({
                                        kind: 'task',
                                        task: action.task,
                                    })
                                }
                                className="min-w-0 flex-[1_1_200px] text-left"
                            >
                                <span className="block truncate text-base text-body">
                                    {action.title}
                                </span>
                            </button>
                            <WeekdayChips action={action} color={color} />
                            <SlotChecklist action={action} />
                            <span className="w-[44px] flex-none font-mono text-xs text-muted">
                                {action.timeLabel}
                            </span>
                            <span className="w-[26px] flex-none font-mono text-xs text-accent">
                                +{action.points}
                            </span>
                            <span className="w-[96px] flex-none text-right font-mono text-xs whitespace-nowrap text-muted">
                                {action.weekLabel}
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </Panel>
    );
}
