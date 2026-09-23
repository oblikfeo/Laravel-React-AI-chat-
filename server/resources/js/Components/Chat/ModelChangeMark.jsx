import { Sparkles } from 'lucide-react';

/**
 * Отметка в ленте: дальше отвечает другая модель.
 *
 * Тонкая строка с линиями по бокам — не спорит с сообщениями,
 * но видно, где разговор сменил собеседника.
 */
export default function ModelChangeMark({ label }) {
    return (
        <div className="flex items-center gap-3 py-1" role="separator">
            <span className="h-px flex-1 bg-white/[0.08]" />

            <span className="flex items-center gap-1.5 text-[12px] text-white/40">
                <Sparkles className="h-3 w-3" strokeWidth={1.75} />
                Switched to {label}
            </span>

            <span className="h-px flex-1 bg-white/[0.08]" />
        </div>
    );
}
