import { Link } from '@inertiajs/react';
import { LiveDot } from '@/components/trakk/live-dot';
import { TopBarSlot } from '@/components/trakk/top-bar-slot';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import { dashboard } from '@/routes';
import { index } from '@/routes/goals';
import type { GoalForm } from '@/types';

export function GoalHeader({ form }: { form: GoalForm }) {
    const sheets = useSheets();

    return (
        <>
            <TopBarSlot>
                <div className="ml-auto flex items-center gap-2">
                    <span className="inline-flex items-center gap-1.5 px-1.5 font-mono text-sm text-muted">
                        <LiveDot />
                        synced
                    </span>
                    <TrakkButton
                        onClick={() =>
                            sheets.open({ kind: 'goal', goal: form })
                        }
                    >
                        edit
                    </TrakkButton>
                    <TrakkButton
                        variant="accent"
                        onClick={() =>
                            sheets.open({ kind: 'task', goalSlug: form.slug })
                        }
                    >
                        + task
                    </TrakkButton>
                </div>
            </TopBarSlot>
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
                <Link
                    href={index()}
                    className="text-accent-soft hover:text-accent-hover"
                >
                    goals
                </Link>
                <span>/</span>
                <span className="text-fg">{form.slug}</span>
            </nav>
        </>
    );
}
