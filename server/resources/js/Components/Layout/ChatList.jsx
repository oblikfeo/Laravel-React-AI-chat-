import { Link, usePage } from '@inertiajs/react';

/**
 * Список чатов в боковом меню. Свежие сверху.
 * Показывается только авторизованному пользователю и только
 * в развёрнутом сайдбаре.
 */
export default function ChatList({ activeId, onNavigate }) {
    const { sidebarChats = [] } = usePage().props;

    if (!sidebarChats.length) {
        return null;
    }

    return (
        <div className="mt-4">
            <p className="px-3 pb-1.5 text-xs font-medium text-white/40">
                Recent
            </p>

            <div className="flex flex-col gap-0.5">
                {sidebarChats.map((chat) => (
                    <Link
                        key={chat.id}
                        href={`/chats/${chat.id}`}
                        onClick={onNavigate}
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
        </div>
    );
}
