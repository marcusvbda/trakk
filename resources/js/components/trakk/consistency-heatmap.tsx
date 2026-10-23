import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { cn } from '@/lib/utils';
import type { ConsistencyData, HeatLevel } from '@/types';

const levelClass: Record<HeatLevel, string> = {
    0: 'bg-heat-0 border-heat-0',
    1: 'bg-heat-1 border-heat-1',
    2: 'bg-heat-2 border-heat-2',
    3: 'bg-heat-3 border-heat-3',
    4: 'bg-heat-4 border-heat-4',
};

const levelLabel: Record<HeatLevel, string> = {
    0: 'no entries',
    1: 'weak',
    2: 'ok',
    3: 'good',
    4: 'target hit',
};

const dayLabels = ['mon', '', 'wed', '', 'fri', '', 'sun'];

const levels: HeatLevel[] = [0, 1, 2, 3, 4];

export function ConsistencyHeatmap({ data }: { data: ConsistencyData }) {
    const ariaLabel = `${data.goodDays} of ${data.totalDays} days with at least 10 points over the last ${data.weeks.length} weeks`;

    return (
        <Panel aria-labelledby="h-consistency" className="flex-[1_1_330px]">
            <PanelHeader id="h-consistency" title="Consistency · 12 weeks">
                <span className="font-mono text-xs text-muted">
                    {data.goodDays}/{data.totalDays} good days
                </span>
            </PanelHeader>
            <div className="flex gap-2 overflow-x-auto">
                <div
                    aria-hidden="true"
                    className="flex flex-col gap-[3px] font-mono text-3xs text-faint"
                >
                    {dayLabels.map((label, index) => (
                        <span key={index} className="h-[13px] leading-[13px]">
                            {label || ' '}
                        </span>
                    ))}
                </div>
                <div
                    role="img"
                    aria-label={ariaLabel}
                    className="flex gap-[3px]"
                >
                    {data.weeks.map((week, weekIndex) => (
                        <div
                            key={weekIndex}
                            className="flex flex-col gap-[3px]"
                        >
                            {week.map((level, dayIndex) => (
                                <span
                                    key={dayIndex}
                                    title={
                                        level === null
                                            ? 'future'
                                            : levelLabel[level]
                                    }
                                    className={cn(
                                        'size-[13px] rounded-[2px] border',
                                        level === null
                                            ? 'border-line-subtle'
                                            : levelClass[level],
                                    )}
                                />
                            ))}
                        </div>
                    ))}
                </div>
            </div>
            <div className="mt-3 flex items-center justify-between gap-1.5 font-mono text-2xs text-muted">
                <span>{data.goodPercent}% of days with ≥ 10 pts</span>
                <span className="inline-flex items-center gap-[3px]">
                    less
                    {levels.map((level) => (
                        <span
                            key={level}
                            className={cn(
                                'size-[9px] rounded-[2px]',
                                levelClass[level],
                            )}
                        />
                    ))}
                    more
                </span>
            </div>
        </Panel>
    );
}
