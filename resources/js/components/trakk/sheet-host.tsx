import type { ReactNode } from 'react';
import { useMemo, useRef, useState } from 'react';
import { GoalSheet } from '@/components/trakk/goal-sheet';
import { ReminderSheet } from '@/components/trakk/reminder-sheet';
import { TaskSheet } from '@/components/trakk/task-sheet';
import type { SheetRequest } from '@/hooks/use-sheets';
import { SheetsContext } from '@/hooks/use-sheets';

type OpenSheet = SheetRequest & { id: number };

/**
 * Owns the create and edit sheets so any page can open them. Each opening gets a fresh form; the sheet
 * stays mounted while it closes, so the exit animation plays.
 */
export function SheetHost({ children }: { children: ReactNode }) {
    const [request, setRequest] = useState<OpenSheet | null>(null);
    const [isOpen, setIsOpen] = useState(false);
    const nextId = useRef(0);

    const api = useMemo(
        () => ({
            open: (next: SheetRequest) => {
                nextId.current += 1;
                setRequest({ ...next, id: nextId.current });
                setIsOpen(true);
            },
        }),
        [],
    );

    return (
        <SheetsContext value={api}>
            {children}
            {request?.kind === 'goal' && (
                <GoalSheet
                    key={request.id}
                    open={isOpen}
                    goal={request.goal}
                    onClose={() => setIsOpen(false)}
                />
            )}
            {request?.kind === 'task' && (
                <TaskSheet
                    key={request.id}
                    open={isOpen}
                    goalSlug={request.goalSlug}
                    task={request.task}
                    onClose={() => setIsOpen(false)}
                />
            )}
            {request?.kind === 'reminder' && (
                <ReminderSheet
                    key={request.id}
                    open={isOpen}
                    goalSlug={request.goalSlug}
                    reminder={request.reminder}
                    onClose={() => setIsOpen(false)}
                />
            )}
        </SheetsContext>
    );
}
