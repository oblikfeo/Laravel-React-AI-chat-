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

function MainLayoutInner({ children, current, activeChatId }) {
    const { auth } = usePage().props;

    // У авторизованного меню развёрнуто: иначе список его чатов
    // оказывается скрыт и найти прошлый разговор невозможно.
    // Гостю показывать нечего, поэтому меню свёрнуто.
    const [collapsed, setCollapsed] = useState(!auth?.user);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        const stored = window.localStorage.getItem(SIDEBAR_STORAGE_KEY);

        if (stored !== null) {
            setCollapsed(stored === 'true');
        }
    }, []);

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

export default function MainLayout({ children, current, activeChatId }) {
    return (
        <ThemeProvider>
            <PlansProvider>
                <MainLayoutInner current={current} activeChatId={activeChatId}>
                    {children}
                </MainLayoutInner>
            </PlansProvider>
        </ThemeProvider>
    );
}
