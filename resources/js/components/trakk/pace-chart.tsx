import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { goalColorClass } from '@/lib/goal-color';
import { cn } from '@/lib/utils';
import type { GoalColor, PaceChartData } from '@/types';

const plot = { left: 40, right: 550, top: 12, bottom: 130 };

const legend = [
    { label: 'actual', lineClassName: 'border-solid border-goal-amber' },
    { label: 'expected', lineClassName: 'border-dashed border-muted' },
    { label: 'projection', lineClassName: 'border-dotted border-danger' },
];

const monoText = 'font-mono text-[9px]';

function Stat({
    label,
    value,
    unit,
    className,
}: {
    label: string;
    value: string;
    unit: string;
    className?: string;
}) {
    return (
        <div className="min-w-0">
            <div className="font-mono text-2xs tracking-widest text-muted uppercase">
                {label}
            </div>
            <div
                className={cn(
                    'font-mono text-[17px] font-light text-fg',
                    className,
                )}
            >
                {value}
                <span className="text-sm text-faint">{unit}</span>
            </div>
        </div>
    );
}

export function PaceChart({
    chart,
    color,
    percent,
}: {
    chart: PaceChartData;
    color: GoalColor;
    percent: number;
}) {
    const x = (day: number) =>
        plot.left + (day / chart.totalDays) * (plot.right - plot.left);
    const y = (value: number) =>
        plot.top + (1 - value / 100) * (plot.bottom - plot.top);
    const todayX = x(chart.elapsedDays);
    const projectedY = y(chart.projectedPercent);
    const points = chart.actual
        .map(
            (point) =>
                `${x(point.day).toFixed(1)},${y(point.percent).toFixed(1)}`,
        )
        .join(' ');
    const ariaLabel = `Actual progress ${percent}% against ${chart.expectedPercent}% expected today. At the current pace the goal reaches ${chart.projectedPercent}% by the due date.`;
    const goalColor = goalColorClass[color];

    return (
        <Panel aria-labelledby="h-pace" className="flex-[1.6_1_490px]">
            <PanelHeader
                id="h-pace"
                title="Pace · actual vs expected"
                className="mb-2"
            >
                <div className="flex gap-3 font-mono text-2xs text-muted">
                    {legend.map((item) => (
                        <span
                            key={item.label}
                            className="inline-flex items-center gap-[5px]"
                        >
                            <span
                                className={cn(
                                    'h-0 w-3 border-t',
                                    item.lineClassName,
                                    item.label === 'actual' && goalColor.border,
                                )}
                            />
                            {item.label}
                        </span>
                    ))}
                </div>
            </PanelHeader>
            <svg
                className="inline align-baseline"
                role="img"
                aria-label={ariaLabel}
                width="100%"
                height="170"
                viewBox="0 0 560 160"
                preserveAspectRatio="xMidYMid meet"
            >
                {[12, 41.5, 71, 100.5].map((line) => (
                    <line
                        key={line}
                        x1={plot.left}
                        x2={plot.right}
                        y1={line}
                        y2={line}
                        className="stroke-divider"
                    />
                ))}
                <line
                    x1={plot.left}
                    x2={plot.right}
                    y1={plot.bottom}
                    y2={plot.bottom}
                    className="stroke-line-strong"
                />
                {[
                    { value: '100', at: 15 },
                    { value: '50', at: 74 },
                    { value: '0', at: 133 },
                ].map((label) => (
                    <text
                        key={label.value}
                        x="32"
                        y={label.at}
                        textAnchor="end"
                        className={cn(monoText, 'fill-faint')}
                    >
                        {label.value}
                    </text>
                ))}
                <text
                    x={plot.left}
                    y="148"
                    className={cn(monoText, 'fill-faint')}
                >
                    {chart.startLabel}
                </text>
                {chart.ticks.map((tick) => (
                    <text
                        key={tick.label}
                        x={x(tick.day)}
                        y="148"
                        textAnchor="middle"
                        className={cn(monoText, 'fill-faint')}
                    >
                        {tick.label}
                    </text>
                ))}
                <text
                    x={plot.right}
                    y="148"
                    textAnchor="end"
                    className={cn(monoText, 'fill-faint')}
                >
                    {chart.dueLabel}
                </text>
                <line
                    x1={plot.left}
                    y1={plot.bottom}
                    x2={plot.right}
                    y2={plot.top}
                    strokeDasharray="3 3"
                    className="stroke-muted"
                />
                <line
                    x1={todayX}
                    x2={todayX}
                    y1="8"
                    y2="134"
                    opacity="0.6"
                    className="stroke-accent"
                />
                <text
                    x={todayX}
                    y="158"
                    textAnchor="middle"
                    className={cn(monoText, 'fill-accent')}
                >
                    today
                </text>
                <line
                    x1={todayX}
                    y1={y(percent)}
                    x2={plot.right}
                    y2={projectedY}
                    strokeDasharray="1 3"
                    className="stroke-danger"
                />
                <polyline
                    points={points}
                    fill="none"
                    strokeWidth="1.5"
                    className={goalColor.stroke}
                />
                <circle
                    cx={todayX}
                    cy={y(percent)}
                    r="3"
                    strokeWidth="1.5"
                    className={cn('fill-surface', goalColor.stroke)}
                />
                <circle
                    cx={todayX}
                    cy={y(chart.expectedPercent)}
                    r="2.5"
                    className="fill-muted"
                />
                <text
                    x="556"
                    y={projectedY - 5}
                    textAnchor="end"
                    className={cn(monoText, 'fill-danger')}
                >
                    {chart.projectedPercent}%
                </text>
            </svg>
            <div className="mt-1.5 grid grid-cols-[repeat(auto-fit,minmax(120px,1fr))] gap-2.5 border-t border-divider pt-2.5">
                <Stat
                    label="expected today"
                    value={String(chart.expectedPercent)}
                    unit="%"
                />
                <Stat
                    label="projected at due"
                    value={String(chart.projectedPercent)}
                    unit="%"
                    className={
                        chart.projectedPercent >= 100
                            ? 'text-accent'
                            : 'text-danger'
                    }
                />
                <Stat
                    label="current pace"
                    value={String(chart.currentPace)}
                    unit=" tasks/wk"
                />
                <Stat
                    label="required pace"
                    value={String(chart.requiredPace)}
                    unit=" tasks/wk"
                    className="text-warning"
                />
            </div>
        </Panel>
    );
}
