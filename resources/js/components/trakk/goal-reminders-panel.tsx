import { EmptyState } from '@/components/trakk/empty-state';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import type { GoalReminder } from '@/types';

export function GoalRemindersPanel({
    reminders,
    goalSlug,
}: {
    reminders: GoalReminder[];
    goalSlug: string;
}) {
    const sheets = useSheets();
    const channels = [
        ...new Set(reminders.flatMap((reminder) => reminder.channels)),
    ];

    return (
        <Panel
            variant="list"
            aria-labelledby="h-reminders"
            className="flex flex-[1_1_342px] flex-col pb-2"
        >
            <PanelHeader inset id="h-reminders" title="Reminders for this goal">
                <div className="flex items-center gap-2">
                    <span className="font-mono text-xs text-muted">
                        {channels.join(' · ')}
                    </span>
                    <TrakkButton
                        size="icon"
                        aria-label="Add reminder for this goal"
                        onClick={() =>
                            sheets.open({ kind: 'reminder', goalSlug })
                        }
                    >
                        +
                    </TrakkButton>
                </div>
            </PanelHeader>
            {reminders.length === 0 ? (
                <EmptyState
                    action={
                        <TrakkButton
                            size="sm"
                            onClick={() =>
                                sheets.open({ kind: 'reminder', goalSlug })
                            }
                        >
                            + reminder
                        </TrakkButton>
                    }
                >
                    no reminders for this goal
                </EmptyState>
            ) : (
                reminders.map((reminder) => (
                    <button
                        key={reminder.id}
                        type="button"
                        aria-label={`Edit reminder "${reminder.label}"`}
                        onClick={() =>
                            sheets.open({
                                kind: 'reminder',
                                goalSlug,
                                reminder: reminder.form,
                            })
                        }
                        className="flex min-h-11 items-center gap-2.5 rounded-control px-1.5 text-left transition-colors duration-150 hover:bg-hover"
                    >
                        <span className="w-[78px] flex-none font-mono text-[11px] text-fg">
                            {reminder.whenLabel}
                        </span>
                        <span className="min-w-0 flex-1 text-base text-body">
                            {reminder.label}
                        </span>
                        <span className="flex-none font-mono text-xs text-muted">
                            {reminder.detailLabel}
                        </span>
                    </button>
                ))
            )}
        </Panel>
    );
}
