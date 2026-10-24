import { EmptyState } from '@/components/trakk/empty-state';
import { WeekDots } from '@/components/trakk/week-dots';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { ProgressBar } from '@/components/ui/progress-bar';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import { percentOf } from '@/lib/format';
import { goalColorClass } from '@/lib/goal-color';
import type { GoalColor, GoalProgress, TaskStatus } from '@/types';

const split: Array<{
    status: TaskStatus;
    label: string;
    fillClassName: string;
}> = [
    { status: 'todo', label: 'to do', fillClassName: 'bg-dim' },
    { status: 'doing', label: 'doing', fillClassName: 'bg-warning' },
    { status: 'done', label: 'done', fillClassName: 'bg-accent' },
];

type GoalProgressPanelProps = {
    color: GoalColor;
    goalSlug: string;
    progress: GoalProgress;
};

export function GoalProgressPanel({
    color,
    goalSlug,
    progress,
}: GoalProgressPanelProps) {
    const sheets = useSheets();
    const { percent, expectedPercent, doneCount, totalCount, counts, week } =
        progress;

    return (
        <Panel
            aria-labelledby="h-progress"
            className="flex flex-[1_1_270px] flex-col gap-3.5"
        >
            <PanelHeader id="h-progress" title="Progress" className="mb-0" />
            <div className="flex items-baseline gap-1.5">
                <span className="font-mono text-[44px] leading-none font-light text-fg-strong">
                    {percent}
                </span>
                <span className="font-mono text-lg text-faint">%</span>
                <span className="ml-auto font-mono text-sm text-muted">
                    {doneCount}/{totalCount}
                    {progress.unit === 'days' && ' days'}
                </span>
            </div>
            <ProgressBar
                size="md"
                percent={percent}
                expectedPercent={expectedPercent}
                fillClassName={goalColorClass[color].bg}
            />
            <div className="flex justify-between font-mono text-xs text-muted">
                <span>actual {percent}%</span>
                <span>expected {expectedPercent}%</span>
            </div>
            <div className="mt-auto flex flex-col gap-2">
                {week && <WeekDots week={week} color={color} />}
                {counts && totalCount === 0 && (
                    <EmptyState
                        size="compact"
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
                        add the first task
                    </EmptyState>
                )}
                {counts &&
                    totalCount > 0 &&
                    split.map((row) => (
                        <div
                            key={row.status}
                            className="grid grid-cols-[64px_minmax(0,1fr)_24px] items-center gap-2"
                        >
                            <span className="font-mono text-xs text-muted">
                                {row.label}
                            </span>
                            <ProgressBar
                                size="sm"
                                className="overflow-hidden"
                                percent={percentOf(
                                    counts[row.status],
                                    totalCount,
                                )}
                                fillClassName={row.fillClassName}
                            />
                            <span className="text-right font-mono text-sm text-fg">
                                {counts[row.status]}
                            </span>
                        </div>
                    ))}
            </div>
        </Panel>
    );
}
