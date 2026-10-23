import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { LiveDot } from '@/components/trakk/live-dot';
import { LogoMark } from '@/components/trakk/logo-mark';
import { Chip } from '@/components/ui/chip';
import { useClock } from '@/hooks/use-clock';
import { formatClock } from '@/lib/format';

export default function AuthLayout({ children }: { children: ReactNode }) {
    const { name } = usePage().props;
    const now = useClock();

    return (
        <div className="flex min-h-screen flex-col bg-canvas scanlines">
            <header className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-divider px-5 pt-3.5 pb-3">
                <div className="flex items-center gap-2.5">
                    <LogoMark size={16} />
                    <span className="font-mono text-[12px] font-medium tracking-[0.04em] text-fg">
                        ~/{name.toLowerCase()}
                    </span>
                    <Chip size="plain" tone="faint">
                        auth
                    </Chip>
                </div>
                <div className="flex items-center gap-3 font-mono text-sm text-muted">
                    <span className="inline-flex items-center gap-1.5">
                        <LiveDot />
                        system online
                    </span>
                    <span className="text-fg">{formatClock(now)}</span>
                </div>
            </header>

            <main className="flex flex-1 items-center justify-center px-4 py-10">
                <div className="flex w-full max-w-[380px] flex-col gap-3.5">
                    {children}
                </div>
            </main>

            <footer className="flex flex-wrap justify-between gap-x-4 gap-y-2 border-t border-divider px-5 pt-2.5 pb-3.5 font-mono text-xs text-faint">
                <span>
                    <span className="text-accent">●</span> secure session ·
                    postgres · mcp ready
                </span>
                <span>{name.toLowerCase()}</span>
            </footer>
        </div>
    );
}
