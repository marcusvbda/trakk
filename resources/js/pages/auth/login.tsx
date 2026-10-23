import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { AuthPanel } from '@/components/trakk/auth-panel';
import { BootLog } from '@/components/trakk/boot-log';
import { TextField } from '@/components/trakk/text-field';
import { TrakkButton } from '@/components/ui/trakk-button';
import { store } from '@/routes/login';

const throttleStatus = 429;

export default function Login() {
    const form = useForm({ email: '', password: '' });
    const [isPasswordShown, setIsPasswordShown] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);
    const [throttleSeconds, setThrottleSeconds] = useState<number | null>(null);

    const serverError =
        form.errors.email || form.errors.password
            ? 'invalid credentials'
            : null;
    const throttleError =
        throttleSeconds === null
            ? null
            : `too many attempts · retry in ${throttleSeconds}s`;
    const error = localError ?? throttleError ?? serverError;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setThrottleSeconds(null);

        if (!form.data.email.trim() || !form.data.password) {
            setLocalError('email and password are required');

            return;
        }

        setLocalError(null);
        form.post(store.url(), {
            onHttpException: (response) => {
                if (response.status !== throttleStatus) {
                    return;
                }

                setThrottleSeconds(
                    Number(response.headers['retry-after'] ?? 60),
                );

                return false;
            },
            onFinish: () => form.reset('password'),
        });
    }

    return (
        <>
            <Head title="Sign in" />

            <BootLog />

            <AuthPanel>
                <form
                    onSubmit={submit}
                    noValidate
                    className="flex flex-col gap-3.5"
                >
                    <TextField
                        id="email"
                        name="email"
                        label="Email"
                        type="email"
                        autoComplete="username"
                        placeholder="you@domain.com"
                        invalid={error !== null}
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                    />

                    <TextField
                        id="password"
                        name="password"
                        label="Password"
                        type={isPasswordShown ? 'text' : 'password'}
                        autoComplete="current-password"
                        placeholder="••••••••••"
                        className="tracking-[0.08em]"
                        invalid={error !== null}
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                        action={
                            <button
                                type="button"
                                aria-pressed={isPasswordShown}
                                aria-label={
                                    isPasswordShown
                                        ? 'Hide password'
                                        : 'Show password'
                                }
                                onClick={() =>
                                    setIsPasswordShown((shown) => !shown)
                                }
                                className="h-[42px] w-14 flex-none border-l border-divider text-center font-mono text-xs tracking-[0.08em] text-muted uppercase transition-colors hover:text-fg"
                            >
                                {isPasswordShown ? 'hide' : 'show'}
                            </button>
                        }
                    />

                    {error && (
                        <div
                            role="alert"
                            className="flex animate-fade-in items-center gap-2 rounded-control border border-danger-line px-2.5 py-[9px] font-mono text-sm text-danger"
                        >
                            <span>×</span>
                            <span>{error}</span>
                        </div>
                    )}

                    <TrakkButton
                        type="submit"
                        variant="accent"
                        size="lg"
                        aria-busy={form.processing}
                        className="mt-1 transition-colors hover:bg-accent/8"
                    >
                        {form.processing ? (
                            <span>authenticating</span>
                        ) : (
                            <>
                                <span>sign in</span>
                                <span aria-hidden="true">→</span>
                            </>
                        )}
                    </TrakkButton>
                </form>
            </AuthPanel>

            <div className="text-center font-mono text-xs text-faint">
                v0.1 · tls · Europe/Dublin
            </div>
        </>
    );
}
