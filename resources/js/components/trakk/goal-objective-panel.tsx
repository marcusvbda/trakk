import { Link } from '@inertiajs/react';
import { EmptyState } from '@/components/trakk/empty-state';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkButton } from '@/components/ui/trakk-button';
import { useSheets } from '@/hooks/use-sheets';
import { show } from '@/routes/goals';
import type { GoalDetail, GoalForm } from '@/types';

export function GoalObjectivePanel({
    goal,
    form,
}: {
    goal: GoalDetail;
    form: GoalForm;
}) {
    const sheets = useSheets();
    const { text, doneWhen, area, linkedTo } = goal.objective;

    return (
        <Panel
            aria-labelledby="h-objective"
            className="flex flex-[1_1_330px] flex-col gap-3"
        >
            <PanelHeader id="h-objective" title="Objective" className="mb-0" />
            {text ? (
                <p className="text-md leading-[1.6] text-body">{text}</p>
            ) : (
                <EmptyState
                    size="compact"
                    action={
                        <TrakkButton
                            size="sm"
                            onClick={() =>
                                sheets.open({ kind: 'goal', goal: form })
                            }
                        >
                            edit
                        </TrakkButton>
                    }
                >
                    no objective yet
                </EmptyState>
            )}
            {doneWhen ? (
                <div className="flex flex-col gap-1.5 rounded-control border border-dashed border-line-strong p-2.5">
                    <span className="font-mono text-2xs tracking-[0.12em] text-accent uppercase">
                        done when
                    </span>
                    <span className="text-base leading-[1.5] text-body">
                        {doneWhen}
                    </span>
                </div>
            ) : (
                <EmptyState
                    glyph="gap"
                    size="compact"
                    hint="a goal without an end never reaches 100%"
                >
                    done when: not defined
                </EmptyState>
            )}
            <div className="mt-auto font-mono text-sm leading-[1.6] text-muted">
                area · {area ?? '—'}
                <br />
                linked to ·{' '}
                {linkedTo ? (
                    <Link
                        href={show(linkedTo.slug)}
                        className="text-accent-soft hover:text-accent-hover"
                    >
                        {linkedTo.title}
                    </Link>
                ) : (
                    '—'
                )}
            </div>
        </Panel>
    );
}
