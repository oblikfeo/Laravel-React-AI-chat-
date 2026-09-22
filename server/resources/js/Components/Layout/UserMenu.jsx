import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import { Sparkles, LogOut, Moon, Sun, Settings } from 'lucide-react';
import { useTheme } from '@/Contexts/ThemeContext';
import { usePlans } from '@/Contexts/PlansContext';
import { useSettings } from '@/Contexts/SettingsContext';

/**
 * Меню по клику на карточку пользователя.
 *
 * Здесь всё, что относится к учётной записи: тариф, переход на платный,
 * оформление и выход. Отдельной страницы кабинета нет.
 */
export default function UserMenu({ user, collapsed, onClose }) {
    const ref = useRef(null);
    const { theme, toggleTheme } = useTheme();
    const { openPlans } = usePlans();
    const { openSettings } = useSettings();

    // Закрытие по клику мимо меню и по Escape — обычное поведение
    // всплывающих меню, без него оно ощущается сломанным.
    useEffect(() => {
        const onPointerDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) {
                onClose();
            }
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [onClose]);

    const item =
        'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm text-white/80 transition hover:bg-white/10 hover:text-white';

    return (
        <div
            ref={ref}
            role="menu"
            className={`absolute bottom-full z-40 mb-2 w-[252px] overflow-hidden rounded-2xl border border-white/10 bg-slate-950/90 p-1.5 shadow-2xl shadow-black/60 backdrop-blur-2xl ${
                collapsed ? 'left-0' : 'left-1 right-1 w-auto'
            }`}
        >
            <div className="border-b border-white/[0.07] px-3 pb-3 pt-2">
                <p className="truncate text-sm font-medium text-white">
                    {user.name}
                </p>
                <p className="truncate text-xs text-white/45">{user.email}</p>

                <span className="mt-2 inline-flex items-center rounded-full border border-white/[0.12] px-2.5 py-0.5 text-[11px] font-medium text-white/70">
                    {user.plan} plan
                </span>
            </div>

            <div className="pt-1.5">
                {user.canUpgrade && (
                    <button
                        type="button"
                        role="menuitem"
                        onClick={() => {
                            onClose();
                            openPlans();
                        }}
                        className={item}
                    >
                        <Sparkles className="h-4 w-4" strokeWidth={1.75} />
                        Upgrade plan
                    </button>
                )}

                <button
                    type="button"
                    role="menuitem"
                    onClick={() => {
                        onClose();
                        openSettings();
                    }}
                    className={item}
                >
                    <Settings className="h-4 w-4" strokeWidth={1.75} />
                    Settings
                </button>

                <button
                    type="button"
                    role="menuitem"
                    onClick={toggleTheme}
                    className={item}
                >
                    {theme === 'dark' ? (
                        <Sun className="h-4 w-4" strokeWidth={1.75} />
                    ) : (
                        <Moon className="h-4 w-4" strokeWidth={1.75} />
                    )}
                    {theme === 'dark' ? 'Light theme' : 'Dark theme'}
                </button>

                <button
                    type="button"
                    role="menuitem"
                    onClick={() => router.post('/logout')}
                    className={item}
                >
                    <LogOut className="h-4 w-4" strokeWidth={1.75} />
                    Sign out
                </button>
            </div>
        </div>
    );
}
