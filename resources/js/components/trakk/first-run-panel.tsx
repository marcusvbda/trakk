import { Link } from '@inertiajs/react';
import { useSheets } from '@/hooks/use-sheets';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkButton } from '@/components/ui/trakk-button';
import { edit } from '@/routes/profile';
import type { FirstRun } from '@/types';

export function FirstRunPanel({ firstRun }: { firstRun: FirstRun }) {
    const sheets = useSheets();

    return (
        <Panel aria-labelledby="h-first-run">
            <PanelHeader id="h-first-run" title="› trakk init">
                <span className="font-mono text-xs text-muted">
                    3 steps to your first day
                </span>
            </PanelHeader>
            <ol className="flex flex-col gap-1">
                <Step number={1} isDone={false} label="create your first goal">
                    <TrakkButton onClick={() => sheets.open({ kind: 'goal' })}>
                        + goal
                    </TrakkButton>
                </Step>
                <Step number={2} isDone={false} label="add tasks to it">
                    <TrakkButton onClick={() => sheets.open({ kind: 'task' })}>
                        + task
                    </TrakkButton>
                </Step>
                <Step
                    number={3}
                    isDone={firstRun.isTelegramLinked}
                    label="link telegram for reminders"
                >
                    {!firstRun.isTelegramLinked && (
                        <Link
                            href={edit()}
                            className="inline-flex h-[34px] items-center rounded-button border border-line-strong px-3 font-mono text-[11px] whitespace-nowrap text-fg-2 transition-colors hover:border-muted hover:text-fg"
                        >
                            settings
                        </Link>
                    )}
                </Step>
            </ol>
        </Panel>
    );
}

function Step({
    number,
    isDone,
    label,
    children,
}: {
    number: number;
    isDone: boolean;
    label: string;
    children?: React.ReactNode;
}) {
    return (
        <li className="flex min-h-11 items-center gap-2.5 px-1">
            <span
                className={
                    isDone
                        ? 'w-4 font-mono text-[11px] text-accent'
                        : 'w-4 font-mono text-[11px] text-muted'
                }
            >
                {isDone ? '✓' : number}
            </span>
            <span className="flex-1 text-base text-body">{label}</span>
            {children}
        </li>
    );
}
