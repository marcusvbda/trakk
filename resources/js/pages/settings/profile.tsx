import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { DeleteAccountPanel } from '@/components/trakk/delete-account-panel';
import { EmptyState } from '@/components/trakk/empty-state';
import { SelectField } from '@/components/trakk/select-field';
import { SettingsHeader } from '@/components/trakk/settings-header';
import { TextField } from '@/components/trakk/text-field';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkButton } from '@/components/ui/trakk-button';
import { update } from '@/routes/profile';

export default function Profile({ timezones }: { timezones: string[] }) {
    const { auth } = usePage().props;
    const form = useForm({
        name: auth.user.name,
        email: auth.user.email,
        timezone: auth.user.timezone,
        daily_points_target: String(auth.user.daily_points_target),
        telegram_chat_id: auth.user.telegram_chat_id ?? '',
    });
    const isTelegramLinked = Boolean(auth.user.telegram_chat_id);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.patch(update.url(), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Profile settings" />

            <SettingsHeader />

            <form onSubmit={submit} className="flex flex-col gap-3.5">
                <div className="flex flex-wrap items-stretch gap-3.5">
                    <Panel
                        aria-labelledby="h-profile"
                        className="flex flex-[1_1_420px] flex-col gap-3.5"
                    >
                        <PanelHeader
                            id="h-profile"
                            title="Profile"
                            className="mb-0"
                        />
                        <TextField
                            id="name"
                            label="name"
                            autoComplete="name"
                            value={form.data.name}
                            error={form.errors.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        <TextField
                            id="email"
                            label="email"
                            type="email"
                            autoComplete="username"
                            value={form.data.email}
                            error={form.errors.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                        />
                        <SelectField
                            id="timezone"
                            label="timezone"
                            value={form.data.timezone}
                            error={form.errors.timezone}
                            options={timezones.map((zone) => ({
                                value: zone,
                                label: zone,
                            }))}
                            onChange={(event) =>
                                form.setData('timezone', event.target.value)
                            }
                        />
                        <TextField
                            id="daily_points_target"
                            label="daily points target"
                            type="number"
                            min={1}
                            max={50}
                            value={form.data.daily_points_target}
                            error={form.errors.daily_points_target}
                            onChange={(event) =>
                                form.setData(
                                    'daily_points_target',
                                    event.target.value,
                                )
                            }
                        />
                    </Panel>

                    <Panel
                        aria-labelledby="h-telegram"
                        className="flex flex-[1_1_320px] flex-col gap-3.5"
                    >
                        <PanelHeader
                            id="h-telegram"
                            title="Telegram"
                            className="mb-0"
                        >
                            <span className="font-mono text-xs text-muted">
                                where reminders arrive
                            </span>
                        </PanelHeader>
                        <TextField
                            id="telegram_chat_id"
                            label="telegram chat id"
                            inputMode="numeric"
                            placeholder="filled in by telegram:link"
                            value={form.data.telegram_chat_id}
                            error={form.errors.telegram_chat_id}
                            onChange={(event) =>
                                form.setData(
                                    'telegram_chat_id',
                                    event.target.value,
                                )
                            }
                        />
                        {isTelegramLinked ? (
                            <EmptyState
                                glyph="positive"
                                hint="relink any time with php artisan telegram:link"
                            >
                                linked
                            </EmptyState>
                        ) : (
                            <EmptyState glyph="gap">
                                not linked · run{' '}
                                <code className="text-fg">
                                    php artisan telegram:link
                                </code>
                            </EmptyState>
                        )}
                    </Panel>
                </div>

                <div className="flex justify-end">
                    <TrakkButton
                        type="submit"
                        variant="accent"
                        disabled={form.processing}
                        aria-busy={form.processing}
                        data-test="update-profile-button"
                    >
                        save
                    </TrakkButton>
                </div>
            </form>

            <DeleteAccountPanel />
        </>
    );
}
