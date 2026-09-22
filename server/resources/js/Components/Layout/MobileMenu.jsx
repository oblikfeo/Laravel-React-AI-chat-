import { useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { navigationItems } from '@/Constants/navigation';
import { LogoFull } from '@/Components/Layout/Logo';
import ProBanner from '@/Components/Layout/ProBanner';
import UserCard from '@/Components/Layout/UserCard';
import ChatList from '@/Components/Layout/ChatList';

/**
 * Мобильное меню: полноэкранный оверлей (figma/Arcana mobile homepage.png).
 * Пункты и порядок те же, что в десктопном сайдбаре.
 */
export default function MobileMenu({ open, onClose, current, activeChatId }) {
    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex flex-col overflow-y-auto bg-[#0a0a0f] lg:hidden">
            <div className="flex h-16 shrink-0 items-center justify-between px-5">
                <LogoFull />

                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Close menu"
                    className="rounded-lg p-1.5 text-white/70 transition hover:bg-white/10 hover:text-white"
                >
                    <X className="h-6 w-6" strokeWidth={1.75} />
                </button>
            </div>

            <div className="px-5 pb-2">
                <Link
                    href="/"
                    onClick={onClose}
                    className="flex h-12 items-center gap-2.5 rounded-full bg-gradient-to-r from-[#dff3ff] to-[#eaf6ff] px-5 font-medium text-black"
                >
                    <Plus className="h-5 w-5" strokeWidth={2.4} />
                    <span className="text-base">New chat</span>
                </Link>
            </div>

            <nav className="flex flex-col gap-1 px-5 py-3">
                {navigationItems.map(({ key, label, icon: Icon, href, ready }) => {
                    const active = current === key;

                    const classes = `flex h-12 items-center gap-3.5 rounded-xl px-2 transition ${
                        active
                            ? 'text-white'
                            : ready
                              ? 'text-white/65 hover:bg-white/[0.07] hover:text-white'
                              : 'cursor-not-allowed text-white/25'
                    }`;

                    const inner = (
                        <>
                            <Icon className="h-5 w-5" strokeWidth={1.75} />
                            <span
                                className={`text-base ${active ? 'font-medium' : ''}`}
                            >
                                {label}
                            </span>
                        </>
                    );

                    // Неготовый раздел — неактивная кнопка, а не ссылка.
                    return ready ? (
                        <Link
                            key={key}
                            href={href}
                            onClick={onClose}
                            className={classes}
                        >
                            {inner}
                        </Link>
                    ) : (
                        <button
                            key={key}
                            type="button"
                            disabled
                            title="Coming soon"
                            className={classes}
                        >
                            {inner}
                        </button>
                    );
                })}

                <ChatList activeId={activeChatId} onNavigate={onClose} />
            </nav>

            <div className="mt-auto space-y-4 px-5 pb-6 pt-4">
                <ProBanner />

                <div className="border-t border-white/[0.07] pt-4">
                    <UserCard />
                </div>
            </div>
        </div>
    );
}
