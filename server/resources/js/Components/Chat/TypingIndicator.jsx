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

            {/* Подложка та же, что у готового ответа: панель не появляется
                рывком, а просто наполняется текстом. */}
            <div className="flex items-center gap-1.5 rounded-3xl rounded-tl-lg border border-white/[0.07] bg-slate-950/70 px-5 py-5 shadow-lg shadow-black/20 backdrop-blur-xl">
                {[0, 150, 300].map((delay) => (
                    <span
                        key={delay}
                        className="h-2 w-2 animate-bounce rounded-full bg-white/50"
                        style={{ animationDelay: `${delay}ms` }}
                    />
                ))}
            </div>
        </div>
    );
}
