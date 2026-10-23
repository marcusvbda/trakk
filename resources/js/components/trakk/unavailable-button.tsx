import type { ComponentProps } from 'react';
import { TrakkButton } from '@/components/ui/trakk-button';
import { cn } from '@/lib/utils';

export function UnavailableButton({
    className,
    ...props
}: ComponentProps<typeof TrakkButton>) {
    return (
        <TrakkButton
            disabled
            title="not available yet"
            className={cn('opacity-40', className)}
            {...props}
        />
    );
}
