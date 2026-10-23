import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { TopBar } from '@/components/trakk/top-bar';
import { TopBarSlotContext } from '@/components/trakk/top-bar-slot';

export default function TrakkLayout({ children }: { children: ReactNode }) {
    const { topBar } = usePage().props;
    const [slot, setSlot] = useState<HTMLElement | null>(null);

    return (
        <div className="min-h-screen bg-canvas scanlines pb-7">
            <div className="mx-auto flex flex-col gap-3.5 px-5">
                {topBar && <TopBar data={topBar} onSlotChange={setSlot} />}
                <TopBarSlotContext value={slot}>
                    <main className="flex flex-col gap-3.5">{children}</main>
                </TopBarSlotContext>
            </div>
        </div>
    );
}
