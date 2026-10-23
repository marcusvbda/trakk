import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import { createPortal } from 'react-dom';

export const TopBarSlotContext = createContext<HTMLElement | null>(null);

export function TopBarSlot({ children }: { children: ReactNode }) {
    const slot = useContext(TopBarSlotContext);

    return slot ? createPortal(children, slot) : null;
}
