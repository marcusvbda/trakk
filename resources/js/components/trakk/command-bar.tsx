import { useEffect, useRef } from 'react';

export function CommandBar() {
    const input = useRef<HTMLInputElement>(null);

    useEffect(() => {
        function focusOnShortcut(event: KeyboardEvent) {
            if (
                event.key.toLowerCase() === 'k' &&
                (event.metaKey || event.ctrlKey)
            ) {
                event.preventDefault();
                input.current?.focus();
            }
        }

        window.addEventListener('keydown', focusOnShortcut);

        return () => window.removeEventListener('keydown', focusOnShortcut);
    }, []);

    return (
        <label className="flex h-9 flex-[1_1_302px] items-center gap-2 rounded-button border border-line bg-field px-2.5 transition-colors focus-within:border-accent hover:border-line-hover">
            <span className="font-mono text-[12px] text-accent">›</span>
            <input
                ref={input}
                aria-label="Command: log, search or ask AI"
                placeholder="log, search or ask AI…"
                className="h-[30px] min-w-0 flex-1 bg-transparent font-mono text-[11.5px] text-fg outline-none"
            />
            <span className="rounded-chip border border-line-strong px-[5px] py-px font-mono text-xs text-muted">
                ⌘K
            </span>
        </label>
    );
}
