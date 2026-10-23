import type { Auth } from '@/types/auth';
import type { TopBarData } from '@/types/dashboard';
import type { CommandIndex, SheetOptions } from '@/types/sheets';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            version: string;
            auth: Auth;
            topBar: TopBarData | null;
            sheetOptions: SheetOptions | null;
            commandIndex?: CommandIndex | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
