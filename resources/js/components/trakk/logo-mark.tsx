import { cn } from '@/lib/utils';

type LogoMarkProps = {
    size?: 16 | 18 | 40;
    className?: string;
};

export function LogoMark({ size = 18, className }: LogoMarkProps) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 18 18"
            aria-hidden="true"
            className={cn('text-accent', className)}
        >
            <path
                d="M9 1 L17 9 L9 17 L1 9 Z"
                fill="none"
                stroke="currentColor"
                strokeWidth={size === 40 ? 0.6 : 1}
            />
            <path d="M9 5.5 L12.5 9 L9 12.5 L5.5 9 Z" fill="currentColor" />
        </svg>
    );
}
