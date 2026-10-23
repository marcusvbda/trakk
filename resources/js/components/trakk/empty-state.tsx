import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Glyph = 'neutral' | 'positive' | 'gap';

const glyphs: Record<Glyph, { symbol: string; className: string }> = {
    neutral: { symbol: '›', className: 'text-accent' },
    positive: { symbol: '✓', className: 'text-accent' },
    gap: { symbol: '!', className: 'text-warning' },
};

type EmptyStateProps = {
    children: ReactNode;
    glyph?: Glyph;
    hint?: ReactNode;
    action?: ReactNode;
    size?: 'panel' | 'compact';
    className?: string;
};

export function EmptyState({
    children,
    glyph = 'neutral',
    hint,
    action,
    size = 'panel',
    className,
}: EmptyStateProps) {
    const { symbol, className: glyphClassName } = glyphs[glyph];

    return (
        <div
            role="status"
            className={cn(
                'flex flex-1 flex-col justify-center gap-1 rounded-control border border-dashed border-line-strong p-3.5',
                size === 'panel' ? 'min-h-[88px]' : 'min-h-14',
                className,
            )}
        >
            <div className="font-mono text-[11px] text-body">
                <span className={glyphClassName}>{symbol}</span> {children}
            </div>
            {hint && <div className="font-mono text-xs text-faint">{hint}</div>}
            {action && <div className="pt-1">{action}</div>}
        </div>
    );
}
