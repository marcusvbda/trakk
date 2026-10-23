import { router, usePage } from '@inertiajs/react';
import * as Dialog from '@radix-ui/react-dialog';
import type { ReactNode } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { EmptyState } from '@/components/trakk/empty-state';
import { CommandPaletteContext } from '@/hooks/use-command-palette';
import { useSheets } from '@/hooks/use-sheets';
import type { DashboardSheet } from '@/lib/dashboard-sheet';
import { dashboardSheetEvent } from '@/lib/dashboard-sheet';
import { goalColorClass } from '@/lib/goal-color';
import { signOut } from '@/lib/sign-out';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as goalsIndex, show } from '@/routes/goals';
import { edit as settings } from '@/routes/profile';
import {
    destroy as undoCompletion,
    store as completeTask,
} from '@/routes/tasks/completions';

type Group = 'commands' | 'goals' | 'tasks today';

type PaletteItem = {
    id: string;
    group: Group;
    label: string;
    hint?: string;
    search?: string;
    glyph: ReactNode;
    run: () => void;
};

const groupOrder: Group[] = ['commands', 'goals', 'tasks today'];

const idleLimit: Record<Group, number> = {
    commands: 9,
    goals: 5,
    'tasks today': 6,
};

const searchLimit = 8;

function normalise(text: string): string {
    return text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();
}

export function CommandPaletteHost({ children }: { children: ReactNode }) {
    const [isOpen, setIsOpen] = useState(false);
    const api = useMemo(() => ({ open: () => setIsOpen(true) }), []);

    useEffect(() => {
        function toggleOnShortcut(event: KeyboardEvent) {
            if (
                event.key.toLowerCase() === 'k' &&
                (event.metaKey || event.ctrlKey)
            ) {
                event.preventDefault();
                setIsOpen((current) => !current);
            }
        }

        window.addEventListener('keydown', toggleOnShortcut);

        return () => window.removeEventListener('keydown', toggleOnShortcut);
    }, []);

    return (
        <CommandPaletteContext value={api}>
            {children}
            <Dialog.Root open={isOpen} onOpenChange={setIsOpen}>
                <Dialog.Portal>
                    <Dialog.Overlay className="fixed inset-0 z-50 bg-canvas/70" />
                    <Dialog.Content
                        aria-describedby={undefined}
                        className="fixed top-[15vh] left-1/2 z-50 flex max-h-[70vh] w-[calc(100%-2rem)] max-w-[560px] -translate-x-1/2 flex-col rounded-panel border border-line bg-surface outline-none"
                    >
                        <Dialog.Title className="sr-only">
                            Search or run a command
                        </Dialog.Title>
                        <PaletteBody close={() => setIsOpen(false)} />
                    </Dialog.Content>
                </Dialog.Portal>
            </Dialog.Root>
        </CommandPaletteContext>
    );
}

function usePaletteItems(close: () => void): PaletteItem[] {
    const { commandIndex } = usePage().props;
    const sheets = useSheets();
    const { component } = usePage();

    return useMemo(() => {
        const openDashboardSheet = (sheet: DashboardSheet) => {
            if (component === 'dashboard') {
                window.dispatchEvent(
                    new CustomEvent(dashboardSheetEvent, { detail: sheet }),
                );

                return;
            }

            router.visit(dashboard({ query: { sheet } }));
        };

        const act = (run: () => void) => () => {
            close();
            queueMicrotask(run);
        };
        const commandGlyph = <span className="text-accent">›</span>;
        const command = (
            id: string,
            label: string,
            run: () => void,
        ): PaletteItem => ({
            id: `command-${id}`,
            group: 'commands',
            label,
            glyph: commandGlyph,
            run: act(run),
        });

        const commands: PaletteItem[] = [
            command('new-goal', 'new goal', () =>
                sheets.open({ kind: 'goal' }),
            ),
            command('new-task', 'new task', () =>
                sheets.open({ kind: 'task' }),
            ),
            command('new-reminder', 'new reminder', () =>
                sheets.open({ kind: 'reminder' }),
            ),
            command('check-in', 'check-in', () =>
                openDashboardSheet('check-in'),
            ),
            command('replan', 'replan', () => openDashboardSheet('replan')),
            command('dashboard', 'go to dashboard', () =>
                router.visit(dashboard()),
            ),
            command('goals', 'go to goals', () => router.visit(goalsIndex())),
            command('settings', 'settings', () => router.visit(settings())),
            command('sign-out', 'sign out', signOut),
        ];

        const goals: PaletteItem[] = (commandIndex?.goals ?? []).map(
            (goal) => ({
                id: `goal-${goal.id}`,
                group: 'goals',
                label: goal.title,
                hint:
                    goal.status === 'active'
                        ? goal.type
                        : `${goal.type} · ${goal.status}`,
                glyph: (
                    <span
                        className={cn(
                            'inline-block size-2 rounded-[2px]',
                            goalColorClass[goal.color].bg,
                        )}
                    />
                ),
                run: act(() => router.visit(show(goal.slug))),
            }),
        );

        const tasks: PaletteItem[] = (commandIndex?.tasks ?? []).map(
            (task) => ({
                id: `task-${task.id}`,
                group: 'tasks today',
                label: task.title,
                hint: `${task.goalName} · ${task.isDone ? 'done' : `+${task.points}`}`,
                search: task.goalName,
                glyph: (
                    <span className={task.isDone ? 'text-accent' : 'text-dim'}>
                        {task.isDone ? '✓' : '○'}
                    </span>
                ),
                run: act(() =>
                    task.isDone
                        ? router.delete(undoCompletion.url(task.id), {
                              preserveScroll: true,
                          })
                        : router.post(
                              completeTask.url(task.id),
                              {},
                              { preserveScroll: true },
                          ),
                ),
            }),
        );

        return [...commands, ...goals, ...tasks];
    }, [close, commandIndex, component, sheets]);
}

function PaletteBody({ close }: { close: () => void }) {
    const items = usePaletteItems(close);
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const list = useRef<HTMLDivElement>(null);
    const needle = normalise(query.trim());

    useEffect(() => {
        router.reload({ only: ['commandIndex'] });
    }, []);

    const visible = useMemo(() => {
        const limit = (group: Group) =>
            needle === '' ? idleLimit[group] : searchLimit;

        return groupOrder.flatMap((group) =>
            items
                .filter(
                    (item) =>
                        item.group === group &&
                        (needle === '' ||
                            normalise(
                                `${item.label} ${item.search ?? ''}`,
                            ).includes(needle)),
                )
                .slice(0, limit(group)),
        );
    }, [items, needle]);
    const activeIndex = Math.min(active, Math.max(visible.length - 1, 0));

    useEffect(() => {
        list.current
            ?.querySelector('[aria-selected="true"]')
            ?.scrollIntoView({ block: 'nearest' });
    }, [activeIndex, visible]);

    function onKeyDown(event: React.KeyboardEvent) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((activeIndex + 1) % Math.max(visible.length, 1));
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(
                (activeIndex - 1 + visible.length) %
                    Math.max(visible.length, 1),
            );
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            visible[activeIndex]?.run();
        }
    }

    return (
        <>
            <div className="flex h-11 flex-none items-center gap-2 border-b border-divider px-3">
                <span className="font-mono text-[12px] text-accent">›</span>
                <input
                    autoFocus
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="palette-results"
                    aria-activedescendant={
                        visible[activeIndex]
                            ? `palette-${visible[activeIndex].id}`
                            : undefined
                    }
                    aria-label="Search or run a command"
                    placeholder="search or run a command…"
                    value={query}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setActive(0);
                    }}
                    onKeyDown={onKeyDown}
                    className="h-10 min-w-0 flex-1 bg-transparent font-mono text-[11.5px] text-fg outline-none"
                />
                <span className="rounded-chip border border-line-strong px-[5px] py-px font-mono text-xs text-muted">
                    esc
                </span>
            </div>
            <div
                ref={list}
                id="palette-results"
                role="listbox"
                className="flex-1 overflow-y-auto p-1"
            >
                {visible.length === 0 && (
                    <EmptyState
                        size="compact"
                        hint="try a goal or task name"
                        className="m-1.5"
                    >
                        no matches for "{query.trim()}"
                    </EmptyState>
                )}
                {groupOrder.map((group) => {
                    const rows = visible.filter((item) => item.group === group);

                    if (rows.length === 0) {
                        return null;
                    }

                    return (
                        <div key={group} role="group" aria-label={group}>
                            <div className="px-2.5 pt-2 pb-1 font-mono text-2xs tracking-[0.12em] text-muted uppercase">
                                {group}
                            </div>
                            {rows.map((item) => {
                                const index = visible.indexOf(item);

                                return (
                                    <div
                                        key={item.id}
                                        id={`palette-${item.id}`}
                                        role="option"
                                        aria-selected={index === activeIndex}
                                        onMouseMove={() => setActive(index)}
                                        onClick={item.run}
                                        className={cn(
                                            'flex h-9 cursor-pointer items-center gap-2.5 rounded-control px-2.5 text-base',
                                            index === activeIndex
                                                ? 'bg-selected text-fg'
                                                : 'text-body',
                                        )}
                                    >
                                        <span className="flex w-3.5 flex-none justify-center font-mono text-[12px]">
                                            {item.glyph}
                                        </span>
                                        <span className="min-w-0 flex-1 truncate">
                                            {item.label}
                                        </span>
                                        {item.hint && (
                                            <span className="flex-none font-mono text-xs text-faint">
                                                {item.hint}
                                            </span>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    );
                })}
            </div>
        </>
    );
}
