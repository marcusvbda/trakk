import type { ComponentProps } from 'react';
import { useState } from 'react';
import { TextField } from '@/components/trakk/text-field';
import { cn } from '@/lib/utils';

type PasswordFieldProps = Omit<
    ComponentProps<typeof TextField>,
    'type' | 'action'
>;

export function PasswordField({ className, ...props }: PasswordFieldProps) {
    const [isShown, setIsShown] = useState(false);

    return (
        <TextField
            type={isShown ? 'text' : 'password'}
            className={cn('tracking-[0.08em]', className)}
            action={
                <button
                    type="button"
                    aria-pressed={isShown}
                    aria-label={isShown ? 'Hide password' : 'Show password'}
                    onClick={() => setIsShown((shown) => !shown)}
                    className="h-[42px] w-14 flex-none border-l border-divider text-center font-mono text-xs tracking-[0.08em] text-muted uppercase transition-colors hover:text-fg"
                >
                    {isShown ? 'hide' : 'show'}
                </button>
            }
            {...props}
        />
    );
}
