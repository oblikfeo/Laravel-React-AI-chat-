import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ThemeProvider } from '@/Contexts/ThemeContext';
import AppBackground from '@/Components/Layout/AppBackground';
import { LogoFull } from '@/Components/Layout/Logo';
import LoginForm from '@/Components/Auth/LoginForm';
import RegisterForm from '@/Components/Auth/RegisterForm';
import ForgotForm from '@/Components/Auth/ForgotForm';

const titles = {
    login: {
        title: 'Welcome back',
        subtitle: 'Sign in to continue',
    },
    register: {
        title: 'Create account',
        subtitle: 'Three steps and you are in',
    },
    forgot: {
        title: 'Reset password',
        subtitle: 'Let us get you back in',
    },
};

/**
 * Единая страница авторизации.
 *
 * Вход, регистрация и восстановление — три компонента в одном роуте,
 * переключаются мгновенно на клиенте без обращения к серверу.
 */
export default function AuthIndex({ mode: initialMode }) {
    const [mode, setMode] = useState(initialMode ?? 'login');

    const { title, subtitle } = titles[mode];

    const switchTo = (next) => {
        setMode(next);

        // Адрес синхронизируем без перезагрузки, чтобы форму можно было
        // открыть прямой ссылкой и вернуться кнопкой «назад».
        window.history.replaceState({}, '', `/auth?mode=${next}`);
    };

    return (
        <ThemeProvider>
            <Head title="Sign in — Uncensia" />

            <div className="relative flex min-h-screen flex-col">
                <AppBackground />

                <header className="flex h-16 shrink-0 items-center px-5 sm:px-8">
                    <Link href="/" aria-label="Uncensia home">
                        <LogoFull />
                    </Link>
                </header>

                <div className="flex flex-1 items-center justify-center px-4 pb-16 sm:px-6">
                    <div className="w-full max-w-[420px] rounded-3xl border border-white/[0.14] bg-slate-950/70 p-7 shadow-2xl shadow-black/50 backdrop-blur-2xl sm:p-8">
                        <div className="mb-7">
                            <h1 className="text-2xl font-light tracking-tight text-white">
                                {title}
                            </h1>
                            <p className="mt-2 text-sm text-white/55">
                                {subtitle}
                            </p>
                        </div>

                        {mode === 'login' && <LoginForm onSwitch={switchTo} />}
                        {mode === 'register' && (
                            <RegisterForm onSwitch={switchTo} />
                        )}
                        {mode === 'forgot' && <ForgotForm onSwitch={switchTo} />}
                    </div>
                </div>
            </div>
        </ThemeProvider>
    );
}
