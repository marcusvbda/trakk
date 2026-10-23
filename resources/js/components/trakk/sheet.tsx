import type { ReactNode } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from '@/components/ui/sheet';
import { TrakkButton } from '@/components/ui/trakk-button';

type TrakkSheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    children: ReactNode;
    footer?: ReactNode;
};

export function TrakkSheet({
    open,
    onOpenChange,
    title,
    description,
    children,
    footer,
}: TrakkSheetProps) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                showClose={false}
                overlayClassName="bg-canvas/70"
                className="w-full gap-0 border-line bg-surface p-0 shadow-none sm:max-w-[440px]"
            >
                <div className="flex min-h-11 items-center justify-between gap-2 border-b border-divider pr-1 pl-4">
                    <SheetTitle className="font-mono text-sm font-medium tracking-widest text-fg-2 uppercase">
                        {title}
                    </SheetTitle>
                    <div className="flex items-center gap-2">
                        <span className="font-mono text-xs text-faint">
                            esc
                        </span>
                        <TrakkButton
                            size="icon"
                            aria-label="Close"
                            className="size-11 border-transparent"
                            onClick={() => onOpenChange(false)}
                        >
                            ×
                        </TrakkButton>
                    </div>
                </div>
                <SheetDescription className="sr-only">
                    {description}
                </SheetDescription>
                <div className="flex flex-1 flex-col gap-3.5 overflow-y-auto p-4">
                    {children}
                </div>
                {footer && (
                    <div className="flex justify-end gap-2 border-t border-divider p-4">
                        {footer}
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}
