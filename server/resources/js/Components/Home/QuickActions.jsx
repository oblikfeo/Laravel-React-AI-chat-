import { Globe, Image as ImageIcon, Video, Sparkles } from 'lucide-react';

/**
 * Быстрые действия под полем ввода. Тексты 1-в-1 с макета.
 * На мобильном переносятся в две строки (figma/1.png).
 */
const actions = [
    {
        key: 'learn',
        label: 'Learn about the World',
        icon: Globe,
        prompt: 'Tell me something genuinely fascinating that most people don\'t know about. Pick a topic that\'s thought-provoking, weird, or challenges common assumptions. Explain why it matters or what makes it interesting.',
    },
    {
        key: 'image',
        label: 'Generate any image',
        icon: ImageIcon,
        prompt: 'Generate an image of ',
    },
    {
        key: 'video',
        label: 'Create AI Video',
        icon: Video,
        prompt: 'Create a video of ',
    },
    {
        key: 'surprise',
        label: 'Surprise Me',
        icon: Sparkles,
        prompt: 'Surprise me with something unexpected and delightful.',
    },
];

export default function QuickActions({ onSelect }) {
    return (
        <div className="flex flex-wrap items-center justify-center gap-2.5">
            {actions.map(({ key, label, icon: Icon, prompt }) => (
                <button
                    key={key}
                    type="button"
                    onClick={() => onSelect?.(prompt)}
                    className="flex h-10 items-center gap-2 rounded-full border border-white/[0.14] bg-slate-950/50 px-4 text-sm text-white/80 backdrop-blur-xl transition hover:bg-white/[0.13] hover:text-white"
                >
                    <Icon className="h-[18px] w-[18px]" strokeWidth={1.75} />
                    {label}
                </button>
            ))}
        </div>
    );
}
