import { ArrowRight, Sparkles } from 'lucide-react';

/**
 * Готово: приглашение в галерею.
 *
 * Появляется под формой, когда работа раздела досчиталась. Сама
 * работа живёт в галерее, а не под формой.
 */
export default function Ready({ count, nouns, onOpen }) {
    return (
        <button
            type="button"
            onClick={onOpen}
            className="mt-4 flex w-full animate-[message-in_0.3s_ease-out] items-center gap-3 rounded-2xl border border-sky-300/40 bg-sky-400/[0.08] px-4 py-3 text-left ring-1 ring-sky-300/20 transition hover:bg-sky-400/[0.14]"
        >
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-400/20 text-sky-200">
                <Sparkles className="h-[18px] w-[18px]" strokeWidth={1.75} />
            </span>

            <span className="min-w-0 flex-1">
                <span className="block text-[14px] font-medium text-white">
                    {count === 1
                        ? `Your ${nouns[0]} is ready`
                        : `${count} new ${nouns[1]} are ready`}
                </span>
                <span className="mt-0.5 block text-[12px] text-white/50">
                    Saved to your gallery
                </span>
            </span>

            <span className="flex shrink-0 items-center gap-1.5 text-[13px] font-medium text-sky-200">
                Open gallery
                <ArrowRight className="h-4 w-4" strokeWidth={2} />
            </span>
        </button>
    );
}
