import { createContext, useContext } from 'react';
import type { GoalForm, ReminderForm, TaskSheet } from '@/types';

export type SheetRequest =
    | { kind: 'goal'; goal?: GoalForm }
    | { kind: 'task'; goalSlug?: string; task?: TaskSheet }
    | { kind: 'reminder'; goalSlug?: string; reminder?: ReminderForm };

export type SheetsApi = {
    open: (request: SheetRequest) => void;
};

export const SheetsContext = createContext<SheetsApi | null>(null);

export function useSheets(): SheetsApi {
    const sheets = useContext(SheetsContext);

    if (!sheets) {
        throw new Error('useSheets needs the SheetHost from TrakkLayout.');
    }

    return sheets;
}
