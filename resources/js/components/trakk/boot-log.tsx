import { useState } from 'react';
import { formatClock } from '@/lib/format';

const lines = [
    {
        symbol: '✓',
        symbolClassName: 'text-accent',
        text: 'postgres · connected',
    },
    {
        symbol: '✓',
        symbolClassName: 'text-accent',
        text: 'mcp · 6 tools ready',
    },
    { symbol: '›', symbolClassName: 'text-info', text: 'auth required' },
];

export function BootLog() {
    const [bootedAt] = useState(() => formatClock(new Date()));

    return (
        <div
            aria-hidden="true"
            className="flex flex-col gap-px px-1 font-mono text-[11px] leading-[1.75]"
        >
            {lines.map((line) => (
                <div
                    key={line.text}
                    className="grid grid-cols-[66px_14px_minmax(0,1fr)] gap-1.5"
                >
                    <span className="text-faint">{bootedAt}</span>
                    <span className={line.symbolClassName}>{line.symbol}</span>
                    <span className="text-log">{line.text}</span>
                </div>
            ))}
        </div>
    );
}
