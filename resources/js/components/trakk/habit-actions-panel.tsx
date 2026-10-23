import { EmptyState } from '@/components/trakk/empty-state';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { GoalColor, HabitAction } from '@/types';

const weekdays = [
    { letter: 'M', name: 'monday' },
    { letter: 'T', name: 'tuesday' },
    { letter: 'W', name: 'wednesday' },
    { letter: 'T', name: 'thursday' },
    { letter: 'F', name: 'friday' },
    { letter: 'S', name: 'saturday' },
    { letter: 'S', name: 'sunday' },
];

function WeekdayChips({ days, color }: { days: number[]; color: GoalColor }) {
    const label = weekdays
        .filter((_, index) => days.includes(index + 1))
        .map((weekday) => weekday.name)
        .join(', ');

    return (
        <span
            role="img"
            aria-label={`repeats on ${label}`}
            className="flex gap-[3px]"
        >
            {weekdays.map((weekday, index) => (
                <span
                    key={weekday.name}
                    className={cn(
                        'inline-flex size-[18px] items-center justify-center rounded-chip border font-mono text-2xs',
                        days.includes(index + 1)
                            ? cn(
                                  goalColorClass[color].bg,
                                  goalColorClass[color].border,
                                  'text-canvas',
                              )
                            : 'border-line-strong text-faint',
                    )}
                >
                    {weekday.letter}
                </span>
            ))}
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
                        <button
                            key={action.id}
                            type="button"
                            aria-label={`Edit action "${action.title}"`}
                            onClick={() =>
                                sheets.open({ kind: 'task', task: action.task })
                            }
                            className="flex min-h-11 animate-fade-in flex-wrap items-center gap-x-3 gap-y-1 rounded-control px-1.5 py-1 text-left transition-colors duration-150 hover:bg-hover"
                        >
                            <span className="min-w-0 flex-[1_1_200px] truncate text-base text-body">
                                {action.title}
                            </span>
                            <WeekdayChips days={action.days} color={color} />
                            <span className="w-[44px] flex-none font-mono text-xs text-muted">
                                {action.timeLabel}
                            </span>
                            <span className="w-[26px] flex-none font-mono text-xs text-accent">
                                +{action.points}
                            </span>
                            <span className="w-[96px] flex-none text-right font-mono text-xs whitespace-nowrap text-muted">
                                {action.weekLabel}
                            </span>
                        </button>
                    ))}
                </div>
            )}
        </Panel>
    );
}
