import { Link, usePage } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';

/**
 * Недавние чаты в свёрнутом сайдбаре.
 *
 * Раньше в свёрнутом состоянии чаты не показывались совсем, и найти
 * прошлый разговор было невозможно, не догадавшись развернуть меню.
 * Теперь видны последние пять: название читается в подсказке.
 */
export default function CollapsedChatList({ activeId }) {
    const { sidebarChats = [] } = usePage().props;

    if (!sidebarChats.length) {
        return null;
    }

    return (
        <div className="side-divider mt-3 flex flex-col items-center gap-1 border-t pt-3">
            {sidebarChats.slice(0, 5).map((chat) => {
                const active = String(activeId) === String(chat.id);

                return (
                    <Link
                        key={chat.id}
                        href={`/chats/${chat.id}`}
                        title={chat.title}
                        aria-label={chat.title}
                        className={`flex h-10 w-10 items-center justify-center rounded-xl transition ${
                            active
                                ? 'nav-item-active'
                                : 'nav-item'
                        }`}
                    >
                        <MessageSquare className="h-[18px] w-[18px]" strokeWidth={1.75} />
                    </Link>
                );
            })}

            {sidebarChats.length > 5 && (
                <Link
                    href="/chats"
                    title="All chats"
                    className="nav-item-muted flex h-10 w-10 items-center justify-center rounded-xl text-xs transition"
                >
                    +{sidebarChats.length - 5}
                </Link>
            )}
        </div>
    );
}
