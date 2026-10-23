import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import { EmptyState } from '@/components/trakk/empty-state';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
    timezones: string[];
};

export default function Profile() {
    const { auth, timezones } = usePage<PageProps>().props;
    const isTelegramLinked = Boolean(auth.user.telegram_chat_id);

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name, email, timezone, daily target and telegram link"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="timezone">Timezone</Label>

                                <select
                                    id="timezone"
                                    name="timezone"
                                    defaultValue={auth.user.timezone}
                                    className="mt-1 h-9 w-full rounded-control border border-line bg-field px-3 font-mono text-[12px] text-fg outline-none focus:border-accent"
                                >
                                    {timezones.map((zone) => (
                                        <option key={zone} value={zone}>
                                            {zone}
                                        </option>
                                    ))}
                                </select>

                                <InputError
                                    className="mt-2"
                                    message={errors.timezone}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="daily_points_target">
                                    Daily points target
                                </Label>

                                <Input
                                    id="daily_points_target"
                                    type="number"
                                    min={1}
                                    max={50}
                                    className="mt-1 block w-full font-mono"
                                    defaultValue={auth.user.daily_points_target}
                                    name="daily_points_target"
                                    required
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.daily_points_target}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="telegram_chat_id">
                                    Telegram chat id
                                </Label>

                                <Input
                                    id="telegram_chat_id"
                                    inputMode="numeric"
                                    className="mt-1 block w-full font-mono"
                                    defaultValue={
                                        auth.user.telegram_chat_id ?? ''
                                    }
                                    name="telegram_chat_id"
                                    placeholder="filled in by telegram:link"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.telegram_chat_id}
                                />

                                {isTelegramLinked ? (
                                    <EmptyState
                                        glyph="positive"
                                        size="compact"
                                        hint="relink any time with php artisan telegram:link"
                                    >
                                        linked
                                    </EmptyState>
                                ) : (
                                    <EmptyState glyph="gap" size="compact">
                                        not linked · run{' '}
                                        <code className="text-fg">
                                            php artisan telegram:link
                                        </code>
                                    </EmptyState>
                                )}
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
