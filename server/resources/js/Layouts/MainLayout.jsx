import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { ThemeProvider } from '@/Contexts/ThemeContext';
import { PlansProvider } from '@/Contexts/PlansContext';
import AppBackground from '@/Components/Layout/AppBackground';
import Sidebar from '@/Components/Layout/Sidebar';
import MobileMenu from '@/Components/Layout/MobileMenu';
import ThemeToggle from '@/Components/Layout/ThemeToggle';

const SIDEBAR_STORAGE_KEY = 'uncensia-sidebar-collapsed';

/** Первое состояние меню: из памяти браузера, иначе развёрнуто. */
function initialCollapsed() {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.localStorage.getItem(SIDEBAR_STORAGE_KEY) === 'true';
}

function MainLayoutInner({ children }) {
    const { url } = usePage();

    // Состояние читается сразу при создании: если сделать это в
    // useEffect, меню успевает моргнуть чужим состоянием.
    const [collapsed, setCollapsed] = useState(initialCollapsed);
    const [mobileOpen, setMobileOpen] = useState(false);

    // Текущий раздел и открытый чат берём из адреса, а не из пропсов
    // страницы: макет постоянный и переживает переходы между страницами.
    const current = url.startsWith('/chats') ? 'chat' : null;
    const activeChatId = Number(url.match(/^\/chats\/(\d+)/)?.[1]) || null;

    // Переход на другую страницу закрывает мобильное меню: иначе
    // оно остаётся поверх новой страницы.
    useEffect(() => {
        setMobileOpen(false);
    }, [url]);

    const toggleSidebar = () => {
        setCollapsed((value) => {
            window.localStorage.setItem(SIDEBAR_STORAGE_KEY, String(!value));

            return !value;
        });
    };

    return (
        <div className="relative flex h-screen w-full overflow-hidden">
            <AppBackground />

            <Sidebar
                collapsed={collapsed}
                onToggle={toggleSidebar}
                current={current}
                activeChatId={activeChatId}
            />

            <MobileMenu
                open={mobileOpen}
                onClose={() => setMobileOpen(false)}
                current={current}
                activeChatId={activeChatId}
            />

            <div className="relative flex min-w-0 flex-1 flex-col overflow-hidden">
                <header className="flex h-16 shrink-0 items-center justify-between px-4 sm:px-6">
                    <button
                        type="button"
                        onClick={() => setMobileOpen(true)}
                        aria-label="Open menu"
                        className="rounded-lg p-1.5 text-white/80 transition hover:bg-white/10 hover:text-white lg:hidden"
                    >
                        <Menu className="h-6 w-6" strokeWidth={1.75} />
                    </button>

                    <div className="ml-auto">
                        <ThemeToggle />
                    </div>
                </header>

                {/* Прокрутку задаёт сама страница: у диалога прокручивается
                    лента сообщений, а поле ввода остаётся внизу экрана. */}
                <main className="flex min-h-0 flex-1 flex-col">{children}</main>
            </div>
        </div>
    );
}

/**
 * Основной макет.
 *
 * Подключается как постоянный макет Inertia (Page.layout), поэтому при
 * переходах между страницами не пересоздаётся: боковое меню сохраняет
 * состояние и не моргает.
 */
export default function MainLayout({ children }) {
    return (
        <ThemeProvider>
            <PlansProvider>
                <MainLayoutInner>{children}</MainLayoutInner>
            </PlansProvider>
        </ThemeProvider>
    );
}
