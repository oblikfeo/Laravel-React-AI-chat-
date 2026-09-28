import { useEffect, useState } from 'react';
import { Sparkles, ChevronDown, Dices } from 'lucide-react';

/**
 * Форма создания изображения.
 *
 * Обязательное на виду, тонкие настройки убраны под «Advanced»:
 * большинству хватает описания, а негативный запрос и зерно нужны
 * тем, кто уже понимает, что делает.
 */
export default function GenerationForm({
    models,
    aspectRatios,
    styles,
    defaultModel,
    available,
    busy,
    limit,
    preset,
    onSubmit,
}) {
    const [prompt, setPrompt] = useState('');
    const [negative, setNegative] = useState('');
    const [model, setModel] = useState(defaultModel);
    const [ratio, setRatio] = useState('1:1');
    const [style, setStyle] = useState('none');
    const [seed, setSeed] = useState('');
    const [advanced, setAdvanced] = useState(false);

    // Повтор работы подставляет её условия: человек чаще хочет
    // поменять деталь, а не получить точную копию.
    useEffect(() => {
        if (!preset) {
            return;
        }

        setPrompt(preset.prompt ?? '');
        setNegative(preset.negativePrompt ?? '');
        setModel(preset.modelKey ?? defaultModel);
        setRatio(preset.aspectRatio ?? '1:1');
        setStyle(preset.style ?? 'none');
        setSeed(preset.seed ? String(preset.seed) : '');
        setAdvanced(Boolean(preset.negativePrompt || preset.seed));
    }, [preset, defaultModel]);

    const exhausted = limit?.remaining === 0;
    const canSubmit = prompt.trim().length > 0 && !busy && available && !exhausted;

    const submit = (event) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        onSubmit({
            prompt: prompt.trim(),
            negative_prompt: negative.trim() || null,
            model,
            aspect_ratio: ratio,
            style: style === 'none' ? null : style,
            seed: seed ? Number(seed) : null,
        });
    };

    return (
        <form
            onSubmit={submit}
            className="rounded-3xl border border-white/[0.12] bg-slate-950/55 p-4 shadow-2xl shadow-black/40 backdrop-blur-2xl sm:p-5"
        >
            {!available && (
                <p className="mb-4 rounded-xl border border-amber-400/20 bg-amber-400/[0.07] px-4 py-2.5 text-[13px] text-amber-100/85">
                    Image generation is coming soon. The studio is ready and
                    will start working as soon as it opens.
                </p>
            )}

            <textarea
                value={prompt}
                onChange={(event) => setPrompt(event.target.value)}
                placeholder="Describe what you want to see…"
                rows={3}
                className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
            />

            <div className="mt-4 flex flex-wrap items-center gap-2">
                <Select value={model} onChange={setModel} options={models} />
                <Select value={ratio} onChange={setRatio} options={aspectRatios} />
                <Select value={style} onChange={setStyle} options={styles} />

                <button
                    type="button"
                    onClick={() => setAdvanced((v) => !v)}
                    className="flex h-9 items-center gap-1.5 rounded-full border border-white/[0.12] px-3.5 text-[13px] text-white/60 transition hover:bg-white/10 hover:text-white"
                >
                    Advanced
                    <ChevronDown
                        className={`h-3.5 w-3.5 transition ${advanced ? 'rotate-180' : ''}`}
                        strokeWidth={2}
                    />
                </button>

                <div className="ml-auto flex items-center gap-3">
                    {limit?.remaining !== null &&
                        limit?.remaining !== undefined && (
                            <span className="text-[13px] text-white/40">
                                {limit.remaining} of {limit.total} left today
                            </span>
                        )}

                    <button
                        type="submit"
                        disabled={!canSubmit}
                        className={`flex h-10 items-center gap-2 rounded-full px-5 text-[14px] font-semibold transition ${
                            canSubmit
                                ? 'bg-white text-black hover:bg-white/90'
                                : 'cursor-not-allowed border border-white/[0.12] text-white/35'
                        }`}
                    >
                        <Sparkles className="h-4 w-4" strokeWidth={2} />
                        {busy ? 'Creating…' : 'Create'}
                    </button>
                </div>
            </div>

            {advanced && (
                <div className="mt-4 grid gap-3 border-t border-white/[0.07] pt-4 sm:grid-cols-[1fr_auto]">
                    <label className="block">
                        <span className="mb-1.5 block text-[13px] text-white/55">
                            What to avoid
                        </span>
                        <input
                            value={negative}
                            onChange={(event) => setNegative(event.target.value)}
                            placeholder="blurry, extra fingers, text…"
                            className="h-10 w-full rounded-xl border border-white/[0.12] bg-white/[0.04] px-3.5 text-[14px] text-white outline-none transition placeholder:text-white/30 focus:border-white/25"
                        />
                    </label>

                    <label className="block">
                        <span className="mb-1.5 block text-[13px] text-white/55">
                            Seed
                        </span>
                        <div className="flex gap-2">
                            <input
                                value={seed}
                                onChange={(event) =>
                                    setSeed(
                                        event.target.value.replace(/\D/g, ''),
                                    )
                                }
                                placeholder="random"
                                inputMode="numeric"
                                className="h-10 w-[140px] rounded-xl border border-white/[0.12] bg-white/[0.04] px-3.5 text-[14px] text-white outline-none transition placeholder:text-white/30 focus:border-white/25"
                            />

                            {/* Одно и то же зерно даёт повторяемый результат:
                                удобно менять детали, сохраняя композицию. */}
                            <button
                                type="button"
                                onClick={() =>
                                    setSeed(
                                        String(
                                            Math.floor(
                                                Math.random() * 2147483647,
                                            ) + 1,
                                        ),
                                    )
                                }
                                title="Random seed"
                                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/[0.12] text-white/55 transition hover:bg-white/10 hover:text-white"
                            >
                                <Dices className="h-4 w-4" strokeWidth={1.75} />
                            </button>
                        </div>
                    </label>
                </div>
            )}
        </form>
    );
}

/** Компактный выпадающий список. */
function Select({ value, onChange, options }) {
    return (
        <div className="relative">
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="h-9 cursor-pointer appearance-none rounded-full border border-white/[0.12] bg-slate-950/70 py-0 pl-3.5 pr-8 text-[13px] text-white/75 outline-none transition hover:text-white focus:border-white/25 focus:ring-0"
            >
                {options.map((option) => (
                    <option
                        key={option.key}
                        value={option.key}
                        className="bg-slate-900 text-white"
                    >
                        {option.label}
                    </option>
                ))}
            </select>

            <ChevronDown
                className="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-white/40"
                strokeWidth={2}
            />
        </div>
    );
}
