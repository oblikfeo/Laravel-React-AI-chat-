import { Link } from '@inertiajs/react';
import { Plus, PanelLeft } from 'lucide-react';
import { navigationItems } from '@/Constants/navigation';
import { LogoMark, LogoFull } from '@/Components/Layout/Logo';
import ProBanner from '@/Components/Layout/ProBanner';
import UserCard from '@/Components/Layout/UserCard';
import ChatList from '@/Components/Layout/ChatList';
import CollapsedChatList from '@/Components/Layout/CollapsedChatList';

/**
 * Десктопный сайдбар. Два состояния по макетам:
 * свёрнутый (72px, только иконки) и развёрнутый (280px, с подписями).
 *
 * Высота ограничена окном, прокручивается только список чатов. Иначе при
 * десятке чатов блок пользователя уезжает за нижний край экрана и до
 * кнопки выхода не добраться.
 */
export default function Sidebar({
    collapsed,
    onToggle,
    current,
    activeChatId,
}) {
    return (
        <aside
            className={`sticky top-0 z-30 hidden h-screen shrink-0 flex-col border-r border-white/[0.07] bg-[#0a0a0f]/85 backdrop-blur-xl transition-[width] duration-300 ease-out lg:flex ${
                collapsed ? 'w-[72px]' : 'w-[280px]'
            }`}
        >
            <div
                className={`flex h-16 items-center ${
                    collapsed ? 'justify-center' : 'justify-between px-5'
                }`}
            >
                {collapsed ? (
                    <button
                        type="button"
                        onClick={onToggle}
                        aria-label="Expand menu"
                        title="Expand menu"
                        className="rounded-lg p-1 transition hover:bg-white/10"
                    >
                        <LogoMark className="h-8 w-8" />
                    </button>
                ) : (
                    <Link href="/" aria-label="Uncensia">
                        <LogoFull />
                    </Link>
                )}

                {!collapsed && (
                    <button
                        type="button"
                        onClick={onToggle}
                        aria-label="Collapse menu"
                        className="rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white"
                    >
                        <PanelLeft className="h-5 w-5" strokeWidth={1.75} />
                    </button>
                )}
            </div>

            <div className={collapsed ? 'px-3 pb-2' : 'px-4 pb-2'}>
                <Link
                    href="/"
                    aria-label="New chat"
                    title={collapsed ? 'New chat' : undefined}
                    className={`flex items-center rounded-full bg-gradient-to-r from-[#dff3ff] to-[#eaf6ff] font-medium text-black shadow-lg shadow-sky-500/10 transition hover:brightness-105 ${
                        collapsed
                            ? 'h-11 w-11 justify-center'
                            : 'h-11 gap-2.5 px-4'
                    }`}
                >
                    <Plus className="h-5 w-5 shrink-0" strokeWidth={2.4} />
                    {!collapsed && <span className="text-[15px]">New chat</span>}
                </Link>
            </div>

            <nav
                className={`flex min-h-0 flex-1 flex-col overflow-y-auto scrollbar-thin py-2 ${
                    collapsed ? 'items-center px-3' : 'px-4'
                }`}
            >
                <div
                    className={`flex flex-col gap-0.5 ${collapsed ? 'items-center' : ''}`}
                >
                    {navigationItems.map(
                        ({ key, label, icon: Icon, href, ready }) => {
                            const active = current === key;

                            const classes = `flex items-center rounded-xl transition ${
                                collapsed
                                    ? 'h-11 w-11 justify-center'
                                    : 'h-11 gap-3 px-3'
                            } ${
                                active
                                    ? 'bg-white/10 text-white'
                                    : ready
                                      ? 'text-white/60 hover:bg-white/[0.07] hover:text-white'
                                      : 'cursor-not-allowed text-white/25'
                            }`;

                            const inner = (
                                <>
                                    <Icon
                                        className="h-5 w-5 shrink-0"
                                        strokeWidth={1.75}
                                    />
                                    {!collapsed && (
                                        <span
                                            className={`text-[15px] ${active ? 'font-medium' : ''}`}
                                        >
                                            {label}
                                        </span>
                                    )}
                                </>
                            );

                            // Неготовый раздел — неактивная кнопка, а не ссылка:
                            // клик по ссылке вёл бы на страницу с ошибкой.
                            return ready ? (
                                <Link
                                    key={key}
                                    href={href}
                                    title={collapsed ? label : undefined}
                                    className={classes}
                                >
                                    {inner}
                                </Link>
                            ) : (
                                <button
                                    key={key}
                                    type="button"
                                    disabled
                                    title={
                                        collapsed
                                            ? `${label} — coming soon`
                                            : 'Coming soon'
                                    }
                                    className={classes}
                                >
                                    {inner}
                                </button>
                            );
                        },
                    )}
                </div>

                {collapsed ? (
                    <CollapsedChatList activeId={activeChatId} />
                ) : (
                    <ChatList activeId={activeChatId} />
                )}
            </nav>

            <div
                className={`mt-auto shrink-0 space-y-4 pb-5 pt-2 ${collapsed ? 'px-3' : 'px-4'}`}
            >
                {!collapsed && <ProBanner />}

                <div
                    className={`border-t border-white/[0.07] pt-4 ${
                        collapsed ? 'flex justify-center' : ''
                    }`}
                >
                    <UserCard collapsed={collapsed} />
                </div>
            </div>
        </aside>
    );
}
