import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type PanelHeaderProps = {
    id: string;
    title: ReactNode;
    count?: ReactNode;
    countClassName?: string;
    inset?: boolean;
    className?: string;
    children?: ReactNode;
};

export function PanelHeader({
    id,
    title,
    count,
    countClassName,
    inset = false,
    className,
    children,
}: PanelHeaderProps) {
    return (
        <div
            className={cn(
                'flex flex-wrap items-center justify-between gap-2 border-b border-divider pb-2.5',
                inset ? 'mb-1 px-1' : 'mb-3',
                className,
            )}
        >
            <h2
                id={id}
                className="font-mono text-sm font-medium tracking-widest text-fg-2 uppercase"
            >
                {title}
                {count !== undefined && (
                    <span
                        className={cn(
                            'font-normal text-faint',
                            countClassName,
                        )}
                    >
                        {' '}
                        · {count}
                    </span>
                )}
            </h2>
            {children}
        </div>
    );
}
