import { useEffect, useState } from 'react';
import { LogoMark } from '@/Components/Layout/Logo';

/**
 * Что показываем, пока модель думает.
 *
 * Первые секунды — просто точки. Если ответа долго нет, подписываем
 * происходящее: человек должен понимать, что мы ждём, а не зависли.
 */
const STAGES = [
    { after: 0, label: null },
    { after: 4000, label: 'Thinking…' },
    { after: 12000, label: 'Still working on it…' },
    { after: 25000, label: 'Taking longer than usual…' },
];

export default function TypingIndicator() {
    const [elapsed, setElapsed] = useState(0);

    useEffect(() => {
        const started = Date.now();
        const timer = setInterval(() => setElapsed(Date.now() - started), 1000);

        return () => clearInterval(timer);
    }, []);

    const stage = [...STAGES].reverse().find((s) => elapsed >= s.after);

    return (
        <div className="flex animate-[message-in_0.3s_ease-out] gap-3.5">
            <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.06]">
                {/* Значок дышит: видно, что интерфейс жив. */}
                <LogoMark className="h-5 w-5 animate-[logo-pulse_2s_ease-in-out_infinite]" />
            </span>

            {/* Подложка та же, что у готового ответа: панель не появляется
                рывком, а просто наполняется текстом. */}
            <div className="flex items-center gap-3 rounded-3xl rounded-tl-lg border border-white/[0.07] bg-slate-950/70 px-5 py-4 shadow-lg shadow-black/20 backdrop-blur-xl">
                <span className="flex items-center gap-1.5">
                    {[0, 160, 320].map((delay) => (
                        <span
                            key={delay}
                            className="h-2 w-2 rounded-full bg-white/60 animate-[dot-wave_1.4s_ease-in-out_infinite]"
                            style={{ animationDelay: `${delay}ms` }}
                        />
                    ))}
                </span>

                {stage?.label && (
                    <span className="text-[13px] text-white/45">
                        {stage.label}
                    </span>
                )}
            </div>
        </div>
    );
}
