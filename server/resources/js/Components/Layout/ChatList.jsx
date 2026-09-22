import { Link, usePage } from '@inertiajs/react';

/**
 * Список чатов в боковом меню. Свежие сверху.
 * Показывается только авторизованному пользователю.
 */
export default function ChatList({ activeId, onNavigate }) {
    const { sidebarChats = [], auth } = usePage().props;

    if (!auth?.user) {
        return null;
    }

    return (
        <div className="mt-4 border-t border-white/[0.07] pt-3">
            <p className="px-3 pb-1.5 text-xs font-medium text-white/40">
                Recent
            </p>

            {sidebarChats.length === 0 ? (
                <p className="px-3 py-2 text-sm leading-relaxed text-white/35">
                    No chats yet. Start one from the home page.
                </p>
            ) : (
                <>
                    <div className="flex flex-col gap-0.5">
                        {sidebarChats.slice(0, 15).map((chat) => (
                            <Link
                                key={chat.id}
                                href={`/chats/${chat.id}`}
                                onClick={onNavigate}
                                title={chat.title}
                                className={`flex h-9 items-center rounded-lg px-3 text-sm transition ${
                                    String(activeId) === String(chat.id)
                                        ? 'bg-white/10 text-white'
                                        : 'text-white/55 hover:bg-white/[0.07] hover:text-white'
                                }`}
                            >
                                <span className="truncate">{chat.title}</span>
                            </Link>
                        ))}
                    </div>

                    <Link
                        href="/chats"
                        onClick={onNavigate}
                        className="mt-1 flex h-9 items-center rounded-lg px-3 text-sm text-white/40 transition hover:bg-white/[0.07] hover:text-white"
                    >
                        All chats
                    </Link>
                </>
            )}
        </div>
    );
}
