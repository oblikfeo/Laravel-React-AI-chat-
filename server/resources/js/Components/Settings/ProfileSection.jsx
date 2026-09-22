import { useForm } from '@inertiajs/react';
import Field from '@/Components/Settings/Field';

/**
 * Имя и почта.
 */
export default function ProfileSection({ user }) {
    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm({ name: user.name, email: user.email });

    const submit = (event) => {
        event.preventDefault();
        put('/settings/profile', { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <Field
                label="Name"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                error={errors.name}
                autoComplete="name"
            />

            <Field
                label="Email"
                type="email"
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
                error={errors.email}
                autoComplete="email"
                hint="Used to sign in and to recover your account."
            />

            <div className="flex items-center gap-3 pt-1">
                <button
                    type="submit"
                    disabled={processing}
                    className="h-10 rounded-full bg-white px-5 text-[13px] font-semibold text-black transition hover:bg-white/90 disabled:opacity-50"
                >
                    Save changes
                </button>

                {recentlySuccessful && (
                    <span className="text-[13px] text-emerald-300">Saved</span>
                )}
            </div>
        </form>
    );
}
