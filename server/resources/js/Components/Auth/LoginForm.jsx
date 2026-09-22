import { useForm } from '@inertiajs/react';
import AuthField from '@/Components/Auth/AuthField';
import AuthSubmit from '@/Components/Auth/AuthSubmit';

export default function LoginForm({ onSwitch }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post('/login');
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <AuthField
                id="email"
                label="Email"
                type="email"
                value={data.email}
                onChange={(value) => setData('email', value)}
                error={errors.email}
                autoComplete="email"
                autoFocus
                placeholder="you@example.com"
            />

            <AuthField
                id="password"
                label="Password"
                type="password"
                value={data.password}
                onChange={(value) => setData('password', value)}
                error={errors.password}
                autoComplete="current-password"
                placeholder="••••••••"
            />

            <div className="flex items-center justify-between">
                <label className="flex cursor-pointer items-center gap-2 text-sm text-white/60">
                    <input
                        type="checkbox"
                        checked={data.remember}
                        onChange={(event) =>
                            setData('remember', event.target.checked)
                        }
                        className="h-4 w-4 rounded border-white/20 bg-white/10 text-sky-400 focus:ring-0 focus:ring-offset-0"
                    />
                    Remember me
                </label>

                <button
                    type="button"
                    onClick={() => onSwitch('forgot')}
                    className="text-sm text-white/60 transition hover:text-white"
                >
                    Forgot password?
                </button>
            </div>

            <AuthSubmit disabled={processing}>
                {processing ? 'Signing in…' : 'Sign in'}
            </AuthSubmit>

            <p className="pt-1 text-center text-sm text-white/55">
                Don&apos;t have an account?{' '}
                <button
                    type="button"
                    onClick={() => onSwitch('register')}
                    className="font-medium text-white transition hover:text-sky-300"
                >
                    Sign up
                </button>
            </p>
        </form>
    );
}
