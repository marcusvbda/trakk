import { router } from '@inertiajs/react';

const reloadGuardKey = 'trakk:chunk-reload';
const reloadGuardMs = 10_000;
const failedImportMessage =
    /dynamically imported module|importing a module script failed/i;

let pendingUrl: string | null = null;

function mayReload(): boolean {
    try {
        const last = Number(window.sessionStorage.getItem(reloadGuardKey));

        if (Date.now() - last < reloadGuardMs) {
            return false;
        }

        window.sessionStorage.setItem(reloadGuardKey, String(Date.now()));

        return true;
    } catch {
        return false;
    }
}

function reload(): boolean {
    if (!mayReload()) {
        return false;
    }

    window.location.assign(pendingUrl ?? window.location.href);

    return true;
}

/**
 * A page chunk that fails to load (a deploy removed the old hashed file, a flaky connection, Vite
 * re-optimising dependencies) is cached as failed by the browser until a full reload, so every later
 * visit to that page renders nothing. Reload once, at most every 10 seconds, to the page being opened.
 */
export function reloadOnStaleChunk(): void {
    router.on('before', (event) => {
        pendingUrl = (event as CustomEvent).detail.visit.url.href;
    });
    router.on('finish', () => {
        pendingUrl = null;
    });

    window.addEventListener('vite:preloadError', (event) => {
        if (reload()) {
            event.preventDefault();
        }
    });
    window.addEventListener('unhandledrejection', (event) => {
        const message = String(event.reason?.message ?? event.reason);

        if (failedImportMessage.test(message) && reload()) {
            event.preventDefault();
        }
    });
}
