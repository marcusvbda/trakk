import type { ReactNode } from 'react';

export default function TrakkLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen bg-canvas scanlines pb-7">
            <div className="mx-auto flex max-w-[1440px] flex-col gap-3.5 px-5">
                {children}
            </div>
        </div>
    );
}
