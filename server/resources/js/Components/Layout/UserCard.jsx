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
                className="nav-item flex h-10 w-10 items-center justify-center rounded-xl transition"
            >
                <LogIn className="h-5 w-5" strokeWidth={1.75} />
            </Link>
        ) : (
            <Link
                href="/auth"
                className="btn-outline flex h-11 items-center justify-center gap-2 rounded-full border text-[15px] font-medium transition"
            >
                <LogIn className="h-[18px] w-[18px]" strokeWidth={1.75} />
                Sign in
            </Link>
        );
    }

    const avatar = (
        <span className="avatar relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold">
            {initialsOf(user.name)}
            <span className="avatar-dot absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 bg-green-500" />
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
                    className="side-row flex items-center justify-center rounded-xl p-1 transition"
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
                className="side-row flex w-full items-center gap-3 rounded-xl px-1 py-1.5 text-left transition"
            >
                {avatar}

                <span className="min-w-0 flex-1">
                    <span className="side-title block truncate text-sm font-medium">
                        {user.name}
                    </span>
                    <span className="side-sub block truncate text-xs">
                        {user.plan} plan
                    </span>
                </span>
            </button>
        </div>
    );
}
