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
 */
export const navigationItems = [
    { key: 'search', label: 'Search', icon: Search, href: '/search' },
    { key: 'chat', label: 'Chat', icon: MessageSquare, href: '/chat' },
    {
        key: 'agentic-chat',
        label: 'Agentic Chat',
        icon: Sparkles,
        href: '/agentic-chat',
    },
    { key: 'studio', label: 'Studio', icon: Video, href: '/studio' },
    { key: 'feed', label: 'Feed', icon: Table2, href: '/feed' },
    { key: 'characters', label: 'Characters', icon: Users, href: '/characters' },
    { key: 'stories', label: 'Stories', icon: BookOpen, href: '/stories' },
    {
        key: 'personas',
        label: 'Personas',
        icon: Fingerprint,
        href: '/personas',
    },
    { key: 'projects', label: 'Projects', icon: FolderOpen, href: '/projects' },
];
