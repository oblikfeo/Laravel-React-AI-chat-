import { Link, usePage, router } from '@inertiajs/react';
import { Settings, LogIn, LogOut } from 'lucide-react';

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
            <button
                type="button"
                title={user.name}
                className="flex items-center justify-center rounded-xl p-1 transition hover:bg-white/10"
            >
                {avatar}
            </button>
        );
    }

    return (
        <div className="flex items-center gap-3 px-1">
            {avatar}

            <span className="min-w-0 flex-1">
                <span className="block truncate text-sm font-medium text-white">
                    {user.name}
                </span>
                <span className="block truncate text-xs text-white/45">
                    0 Credits · local preview
                </span>
            </span>

            <button
                type="button"
                onClick={() => router.post('/logout')}
                aria-label="Sign out"
                title="Sign out"
                className="shrink-0 rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white"
            >
                <LogOut className="h-4 w-4" />
            </button>
        </div>
    );
}
