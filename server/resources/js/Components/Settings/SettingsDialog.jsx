import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { X, User, Lock, CreditCard } from 'lucide-react';
import ProfileSection from '@/Components/Settings/ProfileSection';
import PasswordSection from '@/Components/Settings/PasswordSection';
import BillingSection from '@/Components/Settings/BillingSection';

/**
 * Настройки учётной записи.
 *
 * Разделы перечислены в одном массиве: добавить новый — это одна
 * строка здесь и один компонент рядом, трогать остальное не нужно.
 */
const sections = [
    { key: 'profile', label: 'Profile', icon: User, Component: ProfileSection },
    { key: 'password', label: 'Password', icon: Lock, Component: PasswordSection },
    { key: 'billing', label: 'Subscription', icon: CreditCard, Component: BillingSection },
];

export default function SettingsDialog({ open, onClose }) {
    const { auth } = usePage().props;
    const [active, setActive] = useState('profile');

    if (!open || !auth?.user) {
        return null;
    }

    const Current = sections.find((s) => s.key === active)?.Component;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-label="Settings"
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="flex h-[560px] max-h-full w-full max-w-[760px] overflow-hidden rounded-2xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/60 backdrop-blur-2xl">
                {/* Разделы: на узких экранах уезжают наверх строкой */}
                <nav className="hidden w-[200px] shrink-0 border-r border-white/[0.07] p-3 sm:block">
                    <p className="px-3 pb-2 pt-1 text-xs font-medium uppercase tracking-wide text-white/35">
                        Settings
                    </p>

                    {sections.map(({ key, label, icon: Icon }) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setActive(key)}
                            className={`flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm transition ${
                                active === key
                                    ? 'bg-white/10 font-medium text-white'
                                    : 'text-white/65 hover:bg-white/[0.06] hover:text-white'
                            }`}
                        >
                            <Icon className="h-4 w-4" strokeWidth={1.75} />
                            {label}
                        </button>
                    ))}
                </nav>

                <div className="flex min-w-0 flex-1 flex-col">
                    <div className="flex shrink-0 items-center justify-between border-b border-white/[0.07] px-5 py-4">
                        <div className="flex gap-1 sm:hidden">
                            {sections.map(({ key, label }) => (
                                <button
                                    key={key}
                                    type="button"
                                    onClick={() => setActive(key)}
                                    className={`rounded-lg px-2.5 py-1.5 text-[13px] transition ${
                                        active === key
                                            ? 'bg-white/10 font-medium text-white'
                                            : 'text-white/60'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>

                        <p className="hidden text-[15px] font-semibold text-white sm:block">
                            {sections.find((s) => s.key === active)?.label}
                        </p>

                        <button
                            type="button"
                            onClick={onClose}
                            aria-label="Close"
                            className="-mr-1.5 rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white"
                        >
                            <X className="h-5 w-5" strokeWidth={1.75} />
                        </button>
                    </div>

                    <div className="min-h-0 flex-1 overflow-y-auto scrollbar-thin px-5 py-5">
                        {Current && <Current user={auth.user} onClose={onClose} />}
                    </div>
                </div>
            </div>
        </div>
    );
}
