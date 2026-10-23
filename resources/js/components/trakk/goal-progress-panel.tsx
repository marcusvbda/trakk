import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { ProgressBar } from '@/components/ui/progress-bar';
import { goalColorClass } from '@/lib/goal-color';
import type { GoalColor, TaskStatus } from '@/types';

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
    percent: number;
    expectedPercent: number;
    doneCount: number;
    totalCount: number;
    counts: Record<TaskStatus, number>;
};

export function GoalProgressPanel({
    color,
    percent,
    expectedPercent,
    doneCount,
    totalCount,
    counts,
}: GoalProgressPanelProps) {
    return (
        <Panel
            aria-labelledby="h-progress"
            className="flex flex-[0.8_1_270px] flex-col gap-3.5"
        >
            <PanelHeader id="h-progress" title="Progress" className="mb-0" />
            <div className="flex items-baseline gap-1.5">
                <span className="font-mono text-[44px] leading-none font-light text-fg-strong">
                    {percent}
                </span>
                <span className="font-mono text-lg text-faint">%</span>
                <span className="ml-auto font-mono text-sm text-muted">
                    {doneCount}/{totalCount}
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
                {split.map((row) => (
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
                            percent={(counts[row.status] / totalCount) * 100}
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
