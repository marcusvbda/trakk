import { cn } from '@/lib/utils';

export type GoalFilter = 'all' | 'habit' | 'project' | 'off';

export const goalFilters: Array<{ id: GoalFilter; label: string }> = [
    { id: 'all', label: 'all' },
    { id: 'habit', label: 'habit' },
    { id: 'project', label: 'project' },
    { id: 'off', label: 'off pace' },
];

type GoalFilterTabsProps = {
    active: GoalFilter;
    counts: Record<GoalFilter, number>;
    onChange: (filter: GoalFilter) => void;
};

export function GoalFilterTabs({
    active,
    counts,
    onChange,
}: GoalFilterTabsProps) {
    return (
        <div
            role="group"
            aria-label="Filter goals"
            className="flex flex-wrap gap-1"
        >
            {goalFilters.map((filter) => {
                const isActive = filter.id === active;

                return (
                    <button
                        key={filter.id}
                        type="button"
                        aria-pressed={isActive}
                        onClick={() => onChange(filter.id)}
                        className={cn(
                            'h-7 rounded-control border px-2.5 font-mono text-xs tracking-[0.08em] uppercase',
                            isActive
                                ? 'border-dim bg-selected text-fg'
                                : 'border-line text-muted',
                        )}
                    >
                        {filter.label}{' '}
                        <span className="text-faint">{counts[filter.id]}</span>
                    </button>
                );
            })}
        </div>
    );
}
