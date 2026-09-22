import {
    Search,
    MessageSquare,
    Sparkles,
    Video,
    Table2,
    Users,
    BookOpen,
    Fingerprint,
    FolderOpen,
} from 'lucide-react';

/**
 * Пункты бокового меню. Порядок зафиксирован макетом (figma/1440w dark-2.jpg).
 * Единый источник для десктопного сайдбара и мобильного меню.
 *
 * Поле ready отмечает готовые разделы. Неготовые показываются, потому что
 * они есть в макете, но не ведут никуда: иначе клик открывал бы страницу
 * с ошибкой 404.
 */
export const navigationItems = [
    { key: 'search', label: 'Search', icon: Search, ready: false },
    {
        key: 'chat',
        label: 'Chat',
        icon: MessageSquare,
        href: '/chats',
        ready: true,
    },
    {
        key: 'agentic-chat',
        label: 'Agentic Chat',
        icon: Sparkles,
        ready: false,
    },
    { key: 'studio', label: 'Studio', icon: Video, ready: false },
    { key: 'feed', label: 'Feed', icon: Table2, ready: false },
    { key: 'characters', label: 'Characters', icon: Users, ready: false },
    { key: 'stories', label: 'Stories', icon: BookOpen, ready: false },
    { key: 'personas', label: 'Personas', icon: Fingerprint, ready: false },
    { key: 'projects', label: 'Projects', icon: FolderOpen, ready: false },
];
