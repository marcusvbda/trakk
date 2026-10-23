import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { PasswordField } from '@/components/trakk/password-field';
import { SettingsHeader } from '@/components/trakk/settings-header';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkButton } from '@/components/ui/trakk-button';
import { update } from '@/routes/user-password';

export default function Security({ passwordRules }: { passwordRules: string }) {
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.put(update.url(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
            onError: (errors) => {
                form.reset(
                    'current_password',
                    'password',
                    'password_confirmation',
                );
                document
                    .getElementById(
                        errors.current_password
                            ? 'current_password'
                            : 'password',
                    )
                    ?.focus();
            },
        });
    }

    return (
        <>
            <Head title="Security settings" />

            <SettingsHeader />

            <form onSubmit={submit} className="flex flex-col gap-3.5">
                <Panel
                    aria-labelledby="h-password"
                    className="flex flex-col gap-3.5"
                >
                    <PanelHeader
                        id="h-password"
                        title="Password"
                        className="mb-0"
                    >
                        <span className="font-mono text-xs text-muted">
                            use a long, random one
                        </span>
                    </PanelHeader>
                    <div className="grid grid-cols-[repeat(auto-fit,minmax(260px,1fr))] gap-3.5">
                        <PasswordField
                            id="current_password"
                            label="current password"
                            autoComplete="current-password"
                            value={form.data.current_password}
                            error={form.errors.current_password}
                            onChange={(event) =>
                                form.setData(
                                    'current_password',
                                    event.target.value,
                                )
                            }
                        />
                        <PasswordField
                            id="password"
                            label="new password"
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            value={form.data.password}
                            error={form.errors.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                        />
                        <PasswordField
                            id="password_confirmation"
                            label="confirm password"
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            value={form.data.password_confirmation}
                            error={form.errors.password_confirmation}
                            onChange={(event) =>
                                form.setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                </Panel>

                <div className="flex justify-end">
                    <TrakkButton
                        type="submit"
                        variant="accent"
                        disabled={form.processing}
                        aria-busy={form.processing}
                        data-test="update-password-button"
                    >
                        save
                    </TrakkButton>
                </div>
            </form>
        </>
    );
}
