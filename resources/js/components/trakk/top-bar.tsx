import { CommandBar } from '@/components/trakk/command-bar';
import { LiveDot } from '@/components/trakk/live-dot';
import { LogoMark } from '@/components/trakk/logo-mark';
import { Chip } from '@/components/ui/chip';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useClock } from '@/hooks/use-clock';
import { formatClock } from '@/lib/format';
import type { TopBarData } from '@/types';

export function TopBar({ data }: { data: TopBarData }) {
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
            <CommandBar />
            <div className="ml-auto flex items-center gap-2">
                <span className="inline-flex items-center gap-1.5 px-1.5 font-mono text-sm text-muted">
                    <LiveDot />
                    mcp connected
                </span>
                <TrakkButton>+ goal</TrakkButton>
                <TrakkButton variant="accent">check-in</TrakkButton>
            </div>
        </header>
    );
}
