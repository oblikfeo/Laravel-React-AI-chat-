import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import UserMenu from '@/Components/Layout/UserMenu';

function initialsOf(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

/**
 * Подвал меню: профиль авторизованного пользователя
 * либо приглашение войти для гостя.
 */
export default function UserCard({ collapsed = false }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [menuOpen, setMenuOpen] = useState(false);

    if (!user) {
        return collapsed ? (
            <Link
                href="/auth"
                title="Sign in"
                className="flex h-10 w-10 items-center justify-center rounded-xl text-white/60 transition hover:bg-white/10 hover:text-white"
            >
                <LogIn className="h-5 w-5" strokeWidth={1.75} />
            </Link>
        ) : (
            <Link
                href="/auth"
                className="flex h-11 items-center justify-center gap-2 rounded-full border border-white/[0.12] text-[15px] text-white/80 transition hover:bg-white/10 hover:text-white"
            >
                <LogIn className="h-[18px] w-[18px]" strokeWidth={1.75} />
                Sign in
            </Link>
        );
    }

    const avatar = (
        <span className="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-white ring-1 ring-white/15">
            {initialsOf(user.name)}
            <span className="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-[#0a0a0f] bg-green-500" />
        </span>
    );

    if (collapsed) {
        return (
            <div className="relative">
                {menuOpen && (
                    <UserMenu
                        user={user}
                        collapsed
                        onClose={() => setMenuOpen(false)}
                    />
                )}

                <button
                    type="button"
                    title={user.name}
                    aria-haspopup="menu"
                    aria-expanded={menuOpen}
                    onClick={() => setMenuOpen((value) => !value)}
                    className="flex items-center justify-center rounded-xl p-1 transition hover:bg-white/10"
                >
                    {avatar}
                </button>
            </div>
        );
    }

    return (
        <div className="relative">
            {menuOpen && (
                <UserMenu user={user} onClose={() => setMenuOpen(false)} />
            )}

            <button
                type="button"
                aria-haspopup="menu"
                aria-expanded={menuOpen}
                onClick={() => setMenuOpen((value) => !value)}
                className="flex w-full items-center gap-3 rounded-xl px-1 py-1.5 text-left transition hover:bg-white/[0.07]"
            >
                {avatar}

                <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-medium text-white">
                        {user.name}
                    </span>
                    <span className="block truncate text-xs text-white/45">
                        {user.plan} plan
                    </span>
                </span>
            </button>
        </div>
    );
}
