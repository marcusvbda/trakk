import { router } from '@inertiajs/react';
import { logout } from '@/routes';

export function signOut() {
    router.flushAll();
    router.post(logout.url());
}
