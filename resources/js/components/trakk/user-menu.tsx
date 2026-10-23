import { router, usePage } from '@inertiajs/react';
import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import { firstName, truncate } from '@/lib/format';
import { logout } from '@/routes';

const userNameLimit = 10;

export function UserMenu() {
    const { auth } = usePage().props;

    function signOut() {
        router.flushAll();
        router.post(logout.url());
    }

    return (
        <DropdownMenu.Root>
            <DropdownMenu.Trigger
                aria-label={`Account menu for ${auth.user.name}`}
                className="inline-flex h-[34px] items-center gap-2 rounded-button border border-line-strong px-2.5 font-mono text-[11px] text-fg-2 transition-colors hover:border-muted hover:text-fg data-[state=open]:border-muted data-[state=open]:text-fg [div:empty+&]:ml-auto"
            >
                <span>
                    {truncate(firstName(auth.user.name), userNameLimit)}
                </span>
                <span aria-hidden="true" className="text-xs text-muted">
                    ▾
                </span>
            </DropdownMenu.Trigger>
            <DropdownMenu.Portal>
                <DropdownMenu.Content
                    align="end"
                    sideOffset={6}
                    className="z-50 min-w-[220px] rounded-panel border border-line bg-surface p-1 font-mono"
                >
                    <DropdownMenu.Label className="flex flex-col gap-0.5 border-b border-divider px-2.5 pt-2 pb-2.5">
                        <span className="text-base text-fg">
                            {auth.user.name}
                        </span>
                        <span className="text-xs text-muted">
                            {auth.user.email}
                        </span>
                    </DropdownMenu.Label>
                    <DropdownMenu.Item
                        onSelect={signOut}
                        data-test="logout-button"
                        className="mt-1 flex min-h-11 cursor-pointer items-center gap-2 rounded-control px-2.5 text-[11px] text-danger outline-none data-[highlighted]:bg-hover"
                    >
                        <span aria-hidden="true">×</span>
                        log out
                    </DropdownMenu.Item>
                </DropdownMenu.Content>
            </DropdownMenu.Portal>
        </DropdownMenu.Root>
    );
}
