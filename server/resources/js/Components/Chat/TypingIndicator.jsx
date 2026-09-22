import { LogoMark } from '@/Components/Layout/Logo';

/**
 * Показывается, пока модель готовит ответ.
 */
export default function TypingIndicator() {
    return (
        <div className="flex gap-3.5">
            <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.06]">
                <LogoMark className="h-5 w-5" />
            </span>

            <div className="flex items-center gap-1.5 pt-3">
                {[0, 150, 300].map((delay) => (
                    <span
                        key={delay}
                        className="h-2 w-2 animate-bounce rounded-full bg-white/40"
                        style={{ animationDelay: `${delay}ms` }}
                    />
                ))}
            </div>
        </div>
    );
}
