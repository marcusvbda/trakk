import { LogoMark } from '@/components/trakk/logo-mark';
import { Chip } from '@/components/ui/chip';
import { useClock } from '@/hooks/use-clock';
import { formatClock } from '@/lib/format';
import type { TopBarData } from '@/types';

export function TopBar({
    data,
    onSlotChange,
}: {
    data: TopBarData;
    onSlotChange: (slot: HTMLElement | null) => void;
}) {
    const now = useClock();

    return (
        <header className="flex flex-wrap items-center gap-x-5 gap-y-3 border-b border-divider pt-3.5 pb-3">
            <div className="flex items-center gap-2.5">
                <LogoMark size={18} />
                <span className="font-mono text-md font-medium tracking-[0.04em] text-fg">
                    ~/trakk
                </span>
                <Chip size="plain" tone="faint">
                    v0.1
                </Chip>
            </div>
            <div className="flex items-center gap-2.5 font-mono text-[11px] text-muted">
                <span>{data.dateLabel}</span>
                <span className="text-fg">{formatClock(now)}</span>
                <span
                    title="year progress"
                    className="inline-flex items-center gap-1.5"
                >
                    <span className="inline-block h-0.5 w-[60px] overflow-hidden rounded-[1px] bg-divider">
                        <span
                            className="block h-full bg-dim"
                            style={{ width: `${data.yearProgress}%` }}
                        />
                    </span>
                    <span>{data.yearProgress}% of year</span>
                </span>
            </div>
            <div ref={onSlotChange} className="contents" />
        </header>
    );
}
