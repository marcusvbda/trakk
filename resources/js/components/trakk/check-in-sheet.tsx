import { router } from '@inertiajs/react';
import { ActionRow } from '@/components/trakk/action-row';
import { EmptyState } from '@/components/trakk/empty-state';
import { TrakkSheet } from '@/components/trakk/sheet';
import { ProgressBar } from '@/components/ui/progress-bar';
import { TrakkButton } from '@/components/ui/trakk-button';
import { percentOf } from '@/lib/format';
import { store } from '@/routes/check-in';
import type { TodayAction } from '@/types';

type CheckInSheetProps = {
    open: boolean;
    actions: TodayAction[];
    pointsToday: number;
    dailyTarget: number;
    onToggle: (action: TodayAction) => void;
    onClose: () => void;
};

export function CheckInSheet({
    open,
    actions,
    pointsToday,
    dailyTarget,
    onToggle,
    onClose,
}: CheckInSheetProps) {
    const percent = Math.round(percentOf(pointsToday, dailyTarget));

    function closeDay() {
        router.post(
            store().url,
            {},
            { preserveScroll: true, only: ['log'], onSuccess: onClose },
        );
    }

    return (
        <TrakkSheet
            open={open}
            onOpenChange={(next) => !next && onClose()}
            title="Check-in"
            description="Review today's actions and close the day."
            footer={
                <>
                    <TrakkButton onClick={onClose}>cancel</TrakkButton>
                    <TrakkButton variant="accent" onClick={closeDay}>
                        close the day
                    </TrakkButton>
                </>
            }
        >
            <div className="flex flex-col gap-2">
                <div className="font-mono text-md text-fg">
                    {pointsToday}/{dailyTarget} pts
                    <span className="text-faint"> · {percent}%</span>
                </div>
                <ProgressBar
                    percent={Math.min(100, percent)}
                    fillClassName="bg-accent"
                />
            </div>
            {actions.length === 0 ? (
                <EmptyState hint="you can still close the day">
                    no actions today
                </EmptyState>
            ) : (
                <div className="flex flex-col">
                    {actions.map((action) => (
                        <ActionRow
                            key={action.id}
                            action={action}
                            onToggle={() => onToggle(action)}
                        />
                    ))}
                </div>
            )}
        </TrakkSheet>
    );
}
