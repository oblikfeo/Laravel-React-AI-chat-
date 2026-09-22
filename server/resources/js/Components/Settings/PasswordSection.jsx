import { useForm } from '@inertiajs/react';
import Field from '@/Components/Settings/Field';

/**
 * Смена пароля.
 */
export default function PasswordSection() {
    const { data, setData, put, processing, errors, reset, recentlySuccessful } =
        useForm({
            current_password: '',
            password: '',
            password_confirmation: '',
        });

    const submit = (event) => {
        event.preventDefault();

        put('/settings/password', {
            preserveScroll: true,
            onSuccess: () => reset(),
            // Ошибка относится к конкретному полю: очищаем только его,
            // чтобы не заставлять набирать новый пароль заново.
            onError: (errs) => {
                if (errs.current_password) {
                    reset('current_password');
                }
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <Field
                label="Current password"
                type="password"
                value={data.current_password}
                onChange={(e) => setData('current_password', e.target.value)}
                error={errors.current_password}
                autoComplete="current-password"
            />

            <Field
                label="New password"
                type="password"
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                error={errors.password}
                autoComplete="new-password"
                hint="At least 8 characters."
            />

            <Field
                label="Confirm new password"
                type="password"
                value={data.password_confirmation}
                onChange={(e) =>
                    setData('password_confirmation', e.target.value)
                }
                error={errors.password_confirmation}
                autoComplete="new-password"
            />

            <div className="flex items-center gap-3 pt-1">
                <button
                    type="submit"
                    disabled={processing}
                    className="h-10 rounded-full bg-white px-5 text-[13px] font-semibold text-black transition hover:bg-white/90 disabled:opacity-50"
                >
                    Update password
                </button>

                {recentlySuccessful && (
                    <span className="text-[13px] text-emerald-300">Updated</span>
                )}
            </div>
        </form>
    );
}
