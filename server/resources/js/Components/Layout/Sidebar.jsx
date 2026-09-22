import { Link } from '@inertiajs/react';
import { Plus, PanelLeft } from 'lucide-react';
import { navigationItems } from '@/Constants/navigation';
import { LogoMark, LogoFull } from '@/Components/Layout/Logo';
import ProBanner from '@/Components/Layout/ProBanner';
import UserCard from '@/Components/Layout/UserCard';
import ChatList from '@/Components/Layout/ChatList';

/**
 * Десктопный сайдбар. Два состояния по макетам:
 * свёрнутый (72px, только иконки) и развёрнутый (280px, с подписями).
 */
export default function Sidebar({
    collapsed,
    onToggle,
    current,
    activeChatId,
}) {
    return (
        <aside
            className={`relative z-30 hidden shrink-0 flex-col border-r border-white/[0.07] bg-[#0a0a0f]/85 backdrop-blur-xl transition-[width] duration-300 ease-out lg:flex ${
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
                className={`flex flex-1 flex-col gap-0.5 overflow-y-auto py-2 ${
                    collapsed ? 'items-center px-3' : 'px-4'
                }`}
            >
                {navigationItems.map(({ key, label, icon: Icon, href }) => {
                    const active = current === key;

                    return (
                        <Link
                            key={key}
                            href={href}
                            title={collapsed ? label : undefined}
                            className={`flex items-center rounded-xl transition ${
                                collapsed
                                    ? 'h-11 w-11 justify-center'
                                    : 'h-11 gap-3 px-3'
                            } ${
                                active
                                    ? 'bg-white/10 text-white'
                                    : 'text-white/60 hover:bg-white/[0.07] hover:text-white'
                            }`}
                        >
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
                        </Link>
                    );
                })}

                {!collapsed && <ChatList activeId={activeChatId} />}
            </nav>

            <div
                className={`mt-auto space-y-4 pb-5 ${collapsed ? 'px-3' : 'px-4'}`}
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
