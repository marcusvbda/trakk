import { usePage } from '@inertiajs/react';
import { LiveDot } from '@/components/trakk/live-dot';

export function McpStatus() {
    const { topBar } = usePage().props;

    if (!topBar) {
        return null;
    }

    return (
        <span className="inline-flex items-center gap-1.5 px-1.5 font-mono text-sm text-muted">
            {topBar.isMcpLive ? (
                <LiveDot />
            ) : (
                <span
                    aria-hidden="true"
                    className="inline-block size-1.5 rounded-full bg-dim"
                />
            )}
            {topBar.isMcpLive ? `mcp · ${topBar.mcpTools} tools` : 'mcp idle'}
        </span>
    );
}
