import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { PasswordField } from '@/components/trakk/password-field';
import { Panel } from '@/components/ui/panel';
import { PanelHeader } from '@/components/ui/panel-header';
import { TrakkButton } from '@/components/ui/trakk-button';
import { destroy } from '@/routes/profile';

export function DeleteAccountPanel() {
    const form = useForm({ password: '' });
    const [isConfirming, setIsConfirming] = useState(false);

    function cancel() {
        form.reset();
        form.clearErrors();
        setIsConfirming(false);
    }

    return (
        <Panel
            aria-labelledby="h-delete-account"
            className="flex flex-col gap-3.5 border-danger-line"
        >
            <PanelHeader
                id="h-delete-account"
                title="Delete account"
                className="mb-0"
            />
            <p className="text-base leading-[1.5] text-body">
                Deletes your account with every goal, task, reminder and log
                entry. This cannot be undone.
            </p>
            {isConfirming ? (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.delete(destroy.url(), { preserveScroll: true });
                    }}
                    className="flex flex-col gap-3.5"
                >
                    <PasswordField
                        id="delete-password"
                        label="confirm with your password"
                        autoComplete="current-password"
                        value={form.data.password}
                        error={form.errors.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                    />
                    <div className="flex gap-2">
                        <TrakkButton onClick={cancel}>keep account</TrakkButton>
                        <TrakkButton
                            type="submit"
                            disabled={form.processing}
                            aria-busy={form.processing}
                            data-test="confirm-delete-user-button"
                            className="border-danger-line text-danger disabled:opacity-40"
                        >
                            delete account
                        </TrakkButton>
                    </div>
                </form>
            ) : (
                <button
                    type="button"
                    data-test="delete-user-button"
                    onClick={() => setIsConfirming(true)}
                    className="self-start font-mono text-xs text-danger hover:underline"
                >
                    delete account
                </button>
            )}
        </Panel>
    );
}
