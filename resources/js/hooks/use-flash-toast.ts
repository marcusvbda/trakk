import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        const stopFlash = router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            toast[data.type](data.message);
        });
        const stopHttpException = router.on('httpException', () => {
            toast.error('something went wrong · try again');
        });
        const stopNetworkError = router.on('networkError', () => {
            toast.error('network error · check your connection');
        });

        return () => {
            stopFlash();
            stopHttpException();
            stopNetworkError();
        };
    }, []);
}
