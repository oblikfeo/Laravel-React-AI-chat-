import { useForm } from '@inertiajs/react';
import AuthField from '@/Components/Auth/AuthField';
import AuthSubmit from '@/Components/Auth/AuthSubmit';

/**
 * Регистрация строго в три поля: имя, почта, пароль.
 * Полей подтверждения нет намеренно.
 */
export default function RegisterForm({ onSwitch }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
    });

    const submit = (event) => {
        event.preventDefault();
        post('/register');
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <AuthField
                id="name"
                label="Name"
                value={data.name}
                onChange={(value) => setData('name', value)}
                error={errors.name}
                autoComplete="name"
                autoFocus
                placeholder="How should we call you"
            />

            <AuthField
                id="email"
                label="Email"
                type="email"
                value={data.email}
                onChange={(value) => setData('email', value)}
                error={errors.email}
                autoComplete="email"
                placeholder="you@example.com"
            />

            <AuthField
                id="password"
                label="Password"
                type="password"
                value={data.password}
                onChange={(value) => setData('password', value)}
                error={errors.password}
                autoComplete="new-password"
                placeholder="At least 8 characters"
            />

            <AuthSubmit disabled={processing}>
                {processing ? 'Creating account…' : 'Create account'}
            </AuthSubmit>

            <p className="pt-1 text-center text-sm text-white/55">
                Already have an account?{' '}
                <button
                    type="button"
                    onClick={() => onSwitch('login')}
                    className="font-medium text-white transition hover:text-sky-300"
                >
                    Sign in
                </button>
            </p>
        </form>
    );
}
