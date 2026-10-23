import { toneBg, toneText } from '@/lib/tone';
import { cn } from '@/lib/utils';
import { ProgressBar } from '@/components/ui/progress-bar';
import type { Kpi } from '@/types';

export function KpiCard({ kpi }: { kpi: Kpi }) {
    return (
        <div className="flex min-w-0 flex-col gap-[7px] rounded-panel border border-line bg-surface px-3.5 py-3 transition-colors duration-200 hover:border-line-hover">
            <div className="font-mono text-xs tracking-widest text-muted uppercase">
                {kpi.label}
            </div>
            <div className="flex items-baseline gap-0.5">
                <span
                    className={cn(
                        'font-mono text-[26px] leading-none font-light',
                        toneText[kpi.valueTone],
                    )}
                >
                    {kpi.value}
                </span>
                <span className="font-mono text-[11.5px] text-faint">
                    {kpi.suffix}
                </span>
            </div>
            <ProgressBar
                size="xs"
                percent={kpi.progress ?? 0}
                fillClassName={toneBg[kpi.progressTone]}
                className={cn(kpi.progress === null && 'opacity-0')}
            />
            <div className={cn('font-mono text-sm', toneText[kpi.subTone])}>
                {kpi.sub}
            </div>
        </div>
    );
}
