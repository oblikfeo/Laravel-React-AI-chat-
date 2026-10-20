import { useState } from 'react';
import { Sparkles } from 'lucide-react';

/** Подпись поля со счётчиком и текстом ошибки. */
export function Labeled({ label, hint, error, count, max, action, children }) {
    return (
        <div>
            <div className="mb-1.5 flex items-center justify-between gap-3">
                <span className="text-[13px] font-medium text-white/70">{label}</span>

                <span className="flex items-center gap-2">
                    {typeof count === 'number' && (
                        <span
                            className={`text-[12px] tabular-nums ${
                                count > max ? 'text-rose-300' : 'text-white/35'
                            }`}
                        >
                            {count}/{max}
                        </span>
                    )}
                    {action}
                </span>
            </div>

            {children}

            {hint && !error && (
                <p className="mt-1.5 text-[12px] leading-snug text-white/40">{hint}</p>
            )}

            {error && <p className="mt-1.5 text-[12px] text-rose-300">{error}</p>}
        </div>
    );
}

/** Оформление текстовых полей окна. */
export const INPUT =
    'w-full rounded-xl border border-white/[0.12] bg-white/[0.04] px-3.5 text-[15px] text-white outline-none transition placeholder:text-white/30 focus:border-white/25 focus:ring-0';

/**
 * Кнопка «допиши за меня».
 *
 * По тому, что уже заполнено, модель предлагает недостающий текст.
 * Ответ приходит данными и подставляется в открытую форму.
 */
export function WriteButton({ target, data, onText, label }) {
    const [busy, setBusy] = useState(false);
    const [problem, setProblem] = useState(null);

    const run = async () => {
        setBusy(true);
        setProblem(null);

        try {
            const response = await window.axios.post('/characters/write', {
                target,
                name: data.name,
                description: data.description,
                instructions: data.instructions,
            });

            onText(response.data.text);
        } catch (error) {
            setProblem(
                error.response?.data?.message ??
                    'Could not write it right now. Please try again in a moment.',
            );
        } finally {
            setBusy(false);
        }
    };

    return (
        <div>
            <button
                type="button"
                onClick={run}
                disabled={busy}
                className="flex h-9 w-full items-center justify-center gap-2 rounded-xl border border-white/[0.12] text-[13px] text-white/70 transition hover:bg-white/10 hover:text-white disabled:opacity-50"
            >
                <Sparkles
                    className={`h-4 w-4 ${busy ? 'animate-pulse' : ''}`}
                    strokeWidth={1.75}
                />
                {busy ? 'Writing…' : label}
            </button>

            {problem && <p className="mt-1.5 text-[12px] text-rose-300">{problem}</p>}
        </div>
    );
}
