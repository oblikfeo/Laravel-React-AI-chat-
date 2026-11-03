import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ShieldCheck } from 'lucide-react';
import { ThemeProvider } from '@/Contexts/ThemeContext';
import FlashMessage from '@/Components/Layout/FlashMessage';
import ThemeToggle from '@/Components/Layout/ThemeToggle';

const SECTIONS = [
    { href: '/admin', label: 'Overview', match: (url) => url === '/admin' || url.startsWith('/admin?') },
    { href: '/admin/models', label: 'Models', match: (url) => url.startsWith('/admin/models') },
    { href: '/admin/subscriptions', label: 'Subscriptions', match: (url) => url.startsWith('/admin/subscriptions') },
    { href: '/admin/payments', label: 'Payments', match: (url) => url.startsWith('/admin/payments') },
    { href: '/admin/users', label: 'Users', match: (url) => url.startsWith('/admin/users') },
];

/**
 * Админка: служебная часть для команды.
 *
 * Отдельный макет без бокового меню приложения: здесь широкие
 * таблицы, им нужно всё место.
 */
function AdminShell({ title, children }) {
    const { url } = usePage();

    return (
        <div className="min-h-screen bg-surface">
            <Head title={`${title} — Admin`} />
            <FlashMessage />

            <header className="sticky top-0 z-30 border-b border-white/[0.08] bg-slate-950/90 backdrop-blur-xl">
                <div className="mx-auto flex h-14 max-w-[1240px] items-center gap-4 px-4 sm:px-6">
                    <Link href="/" className="flex shrink-0 items-center gap-2 text-white/60 transition hover:text-white" title="Back to the app">
                        <ArrowLeft className="h-4 w-4" strokeWidth={1.75} />
                        <span className="hidden text-[13px] sm:inline">App</span>
                    </Link>

                    <span className="flex shrink-0 items-center gap-2 text-[15px] font-semibold text-white">
                        <ShieldCheck className="h-[18px] w-[18px] text-sky-300" strokeWidth={1.75} />
                        Admin
                    </span>

                    <nav className="scrollbar-thin flex min-w-0 flex-1 gap-1 overflow-x-auto">
                        {SECTIONS.map((section) => (
                            <Link
                                key={section.href}
                                href={section.href}
                                className={`shrink-0 rounded-lg px-3 py-1.5 text-[13px] transition ${
                                    section.match(url)
                                        ? 'bg-white/10 font-medium text-white'
                                        : 'text-white/55 hover:bg-white/[0.06] hover:text-white'
                                }`}
                            >
                                {section.label}
                            </Link>
                        ))}
                    </nav>

                    <ThemeToggle />
                </div>
            </header>

            <main className="mx-auto max-w-[1240px] px-4 py-6 sm:px-6">
                <h1 className="text-2xl font-light tracking-tight text-white">{title}</h1>
                <div className="mt-5">{children}</div>
            </main>
        </div>
    );
}

export default function AdminLayout(props) {
    return (
        <ThemeProvider>
            <AdminShell {...props} />
        </ThemeProvider>
    );
}
