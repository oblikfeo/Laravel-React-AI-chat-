import { useEffect, useState } from 'react';
import { Menu } from 'lucide-react';
import { ThemeProvider } from '@/Contexts/ThemeContext';
import AppBackground from '@/Components/Layout/AppBackground';
import Sidebar from '@/Components/Layout/Sidebar';
import MobileMenu from '@/Components/Layout/MobileMenu';
import ThemeToggle from '@/Components/Layout/ThemeToggle';

const SIDEBAR_STORAGE_KEY = 'uncensia-sidebar-collapsed';

function MainLayoutInner({ children, current, activeChatId }) {
    const [collapsed, setCollapsed] = useState(true);
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
        <div className="relative flex min-h-screen w-full">
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

            <div className="relative flex min-w-0 flex-1 flex-col">
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

                <main className="flex flex-1 flex-col">{children}</main>
            </div>
        </div>
    );
}

export default function MainLayout({ children, current, activeChatId }) {
    return (
        <ThemeProvider>
            <MainLayoutInner current={current} activeChatId={activeChatId}>
                {children}
            </MainLayoutInner>
        </ThemeProvider>
    );
}
