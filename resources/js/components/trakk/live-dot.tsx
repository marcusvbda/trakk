import { cn } from '@/lib/utils';

export function LiveDot({
    size = 6,
    className,
}: {
    size?: 5 | 6;
    className?: string;
}) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'inline-block animate-pulse-dot rounded-full bg-accent',
                size === 5 ? 'size-[5px]' : 'size-1.5',
                className,
            )}
        />
    );
}
