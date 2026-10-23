import type { Auth } from '@/types/auth';
import type { TopBarData } from '@/types/dashboard';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            topBar: TopBarData | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
