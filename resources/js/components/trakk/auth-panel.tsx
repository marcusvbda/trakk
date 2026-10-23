import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { LogoMark } from '@/components/trakk/logo-mark';

const corner = 'pointer-events-none absolute size-3';

export function AuthPanel({ children }: { children: ReactNode }) {
    const { name } = usePage().props;

    return (
        <section
            aria-labelledby="sign-in-title"
            className="relative rounded-panel border border-line bg-surface px-7 pt-7 pb-[22px]"
        >
            <span
                aria-hidden="true"
                className={`${corner} -top-px -left-px rounded-tl-panel border-t border-l border-accent`}
            />
            <span
                aria-hidden="true"
                className={`${corner} -top-px -right-px rounded-tr-panel border-t border-r border-dim`}
            />
            <span
                aria-hidden="true"
                className={`${corner} -bottom-px -left-px rounded-bl-panel border-b border-l border-dim`}
            />
            <span
                aria-hidden="true"
                className={`${corner} -right-px -bottom-px rounded-br-panel border-r border-b border-accent`}
            />

            <div className="mb-5 flex items-center gap-3.5 border-b border-divider pb-5">
                <LogoMark size={40} />
                <div className="flex flex-col gap-1">
                    <h1
                        id="sign-in-title"
                        className="text-[28px] leading-none font-light tracking-[-0.02em] text-fg-strong"
                    >
                        {name}
                    </h1>
                    <span className="font-mono text-sm tracking-[0.06em] text-muted">
                        goals · pace · focus
                    </span>
                </div>
            </div>

            {children}

            <div className="mt-[18px] flex justify-between gap-2 border-t border-divider pt-3 font-mono text-xs text-faint">
                <span>single-user instance</span>
                <span>access is logged</span>
            </div>
        </section>
    );
}
