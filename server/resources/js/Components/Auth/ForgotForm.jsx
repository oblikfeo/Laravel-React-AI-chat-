import { useState } from 'react';
import { MailCheck } from 'lucide-react';
import AuthField from '@/Components/Auth/AuthField';
import AuthSubmit from '@/Components/Auth/AuthSubmit';

/**
 * Восстановление пароля — пока только вёрстка.
 *
 * SMTP намеренно не подключён: форма показывает состояние «письмо
 * отправлено» локально. Когда почта заработает, сюда добавится
 * обычный post на роут восстановления.
 */
export default function ForgotForm({ onSwitch }) {
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);

    const submit = (event) => {
        event.preventDefault();

        if (email.trim()) {
            setSent(true);
        }
    };

    if (sent) {
        return (
            <div className="flex flex-col items-center gap-5 text-center">
                <span className="flex h-14 w-14 items-center justify-center rounded-full bg-white/10">
                    <MailCheck
                        className="h-7 w-7 text-sky-300"
                        strokeWidth={1.75}
                    />
                </span>

                <div className="space-y-2">
                    <p className="text-[15px] text-white">Check your inbox</p>
                    <p className="text-sm leading-relaxed text-white/55">
                        If an account exists for {email}, we&apos;ll send a
                        password reset link to it.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={() => onSwitch('login')}
                    className="text-sm font-medium text-white transition hover:text-sky-300"
                >
                    Back to sign in
                </button>
            </div>
        );
    }

    return (
        <form onSubmit={submit} className="space-y-5">
            <p className="text-sm leading-relaxed text-white/55">
                Enter the email address linked to your account and we&apos;ll
                send you a reset link.
            </p>

            <AuthField
                id="forgot-email"
                label="Email"
                type="email"
                value={email}
                onChange={setEmail}
                autoComplete="email"
                autoFocus
                placeholder="you@example.com"
            />

            <AuthSubmit disabled={!email.trim()}>Send reset link</AuthSubmit>

            <p className="pt-1 text-center text-sm text-white/55">
                Remembered your password?{' '}
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
