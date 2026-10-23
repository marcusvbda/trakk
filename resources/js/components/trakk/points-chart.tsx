import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { cn } from '@/lib/utils';
import type { PointsChartData, PointsTier } from '@/types';

const chartHeight = 84;

const tierClass: Record<PointsTier, string> = {
    target: 'bg-accent',
    good: 'bg-bar-good',
    low: 'bg-bar-low',
};

const legend: Array<{ tier: PointsTier; label: string }> = [
    { tier: 'target', label: '≥ target' },
    { tier: 'good', label: '≥ 70%' },
    { tier: 'low', label: 'below' },
];

function Stat({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <div className="font-mono text-2xs tracking-widest text-muted uppercase">
                {label}
            </div>
            <div className="font-mono text-[16px] text-fg">{children}</div>
        </div>
    );
}

export function PointsChart({ chart }: { chart: PointsChartData }) {
    const targetOffset = (chart.target / chart.scaleMax) * chartHeight;
    const hasPoints = chart.days.some((day) => day.points > 0);
    const ariaLabel = `Points per day over the last ${chart.days.length} days. Average ${chart.average}, best day ${chart.bestDay}, ${chart.daysAtTarget} days hit the target of ${chart.target}.`;

    return (
        <Panel aria-labelledby="h-points" className="flex-[1.4_1_450px]">
            <PanelHeader id="h-points" title="Points · 30 days">
                <div className="flex gap-2.5 font-mono text-2xs text-muted">
                    {legend.map((item) => (
                        <span
                            key={item.tier}
                            className="inline-flex items-center gap-1"
                        >
                            <span
                                className={cn(
                                    'size-[7px] rounded-[1px]',
                                    tierClass[item.tier],
                                )}
                            />
                            {item.label}
                        </span>
                    ))}
                </div>
            </PanelHeader>
            <div
                role="img"
                aria-label={ariaLabel}
                className="relative flex h-[84px] items-end gap-[3px]"
            >
                <div
                    className="absolute inset-x-0 border-t border-dashed border-dim"
                    style={{ bottom: targetOffset }}
                />
                <span
                    className="absolute right-0 bg-surface pl-1 font-mono text-2xs text-muted"
                    style={{ bottom: targetOffset + 3 }}
                >
                    target {chart.target}
                </span>
                {chart.days.map((day, index) => (
                    <div
                        key={index}
                        title={day.label}
                        className={cn(
                            'min-w-0 flex-1 rounded-[1px] transition-[height,background-color] duration-[350ms] hover:brightness-[1.35]',
                            tierClass[day.tier],
                        )}
                        style={{
                            height: Math.max(
                                2,
                                Math.round(
                                    (day.points / chart.scaleMax) * chartHeight,
                                ),
                            ),
                        }}
                    />
                ))}
            </div>
            <div className="mt-1.5 flex justify-between font-mono text-2xs text-faint">
                <span>{chart.startLabel}</span>
                <span>{chart.middleLabel}</span>
                <span className="text-accent">today</span>
            </div>
            {!hasPoints && (
                <p role="status" className="mt-2 font-mono text-xs text-faint">
                    <span className="text-accent">›</span> no points yet · check
                    an action to start
                </p>
            )}
            <div className="mt-3 grid grid-cols-3 gap-2.5 border-t border-divider pt-2.5">
                <Stat label="average">{hasPoints ? chart.average : '—'}</Stat>
                <Stat label="best day">{hasPoints ? chart.bestDay : '—'}</Stat>
                <Stat label="days ≥ target">
                    {hasPoints ? chart.daysAtTarget : '—'}
                    {hasPoints && (
                        <span className="text-[11px] text-faint">
                            /{chart.days.length}
                        </span>
                    )}
                </Stat>
            </div>
        </Panel>
    );
}
