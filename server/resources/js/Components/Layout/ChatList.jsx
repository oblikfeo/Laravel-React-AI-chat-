import { Link, usePage } from '@inertiajs/react';

/**
 * Список чатов в боковом меню. Свежие сверху.
 *
 * Показывается и гостю: он тоже ведёт разговоры, просто ограниченно.
 * Пустой список скрываем, чтобы не занимать место у того, кто зашёл
 * впервые.
 */
export default function ChatList({ activeId, onNavigate }) {
    const { sidebarChats = [], auth, guest } = usePage().props;

    if (!auth?.user && !guest) {
        return null;
    }

    if (guest && sidebarChats.length === 0) {
        return null;
    }

    return (
        <div className="side-divider mt-4 border-t pt-3">
            <p className="side-label px-3 pb-1.5 text-xs font-medium">
                Recent
            </p>

            {sidebarChats.length === 0 ? (
                <p className="side-label px-3 py-2 text-sm leading-relaxed">
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
                                        ? 'nav-item-active font-medium'
                                        : 'nav-item'
                                }`}
                            >
                                <span className="truncate">{chat.title}</span>
                            </Link>
                        ))}
                    </div>

                    <Link
                        href="/chats"
                        onClick={onNavigate}
                        className="nav-item-muted mt-1 flex h-9 items-center rounded-lg px-3 text-sm transition"
                    >
                        All chats
                    </Link>
                </>
            )}
        </div>
    );
}
