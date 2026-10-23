import { Link } from '@inertiajs/react';
import { LiveDot } from '@/components/trakk/live-dot';
import { TrakkButton } from '@/components/ui/trakk-button';
import { dashboard } from '@/routes';

export function GoalHeader({ slug }: { slug: string }) {
    return (
        <header className="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-b border-divider pt-3.5 pb-3">
            <nav
                aria-label="Breadcrumb"
                className="flex items-center gap-2 font-mono text-[11px] text-faint"
            >
                <Link
                    href={dashboard()}
                    className="text-accent-soft hover:text-accent-hover"
                >
                    ← dashboard
                </Link>
                <span>/</span>
                <span>goals</span>
                <span>/</span>
                <span className="text-fg">{slug}</span>
            </nav>
            <div className="ml-auto flex items-center gap-2">
                <span className="inline-flex items-center gap-1.5 px-1.5 font-mono text-sm text-muted">
                    <LiveDot />
                    synced
                </span>
                <TrakkButton>edit</TrakkButton>
                <TrakkButton variant="accent">+ task</TrakkButton>
            </div>
        </header>
    );
}
