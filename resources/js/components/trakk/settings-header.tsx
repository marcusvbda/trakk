import { Link } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

const tabs = [
    { label: 'profile', href: editProfile() },
    { label: 'security', href: editSecurity() },
];

export function SettingsHeader() {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <>
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
                <span className="text-fg">settings</span>
            </nav>

            <h1 className="text-[24px] font-light tracking-[-0.01em] text-fg-strong">
                Settings
            </h1>

            <nav aria-label="Settings" className="flex flex-wrap gap-1">
                {tabs.map((tab) => (
                    <Link
                        key={tab.label}
                        href={tab.href}
                        aria-current={
                            isCurrentUrl(tab.href) ? 'page' : undefined
                        }
                        className={cn(
                            'inline-flex h-7 items-center rounded-control border px-2.5 font-mono text-xs tracking-[0.08em] uppercase',
                            isCurrentUrl(tab.href)
                                ? 'border-dim bg-selected text-fg'
                                : 'border-line text-muted',
                        )}
                    >
                        {tab.label}
                    </Link>
                ))}
            </nav>
        </>
    );
}
