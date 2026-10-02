import { useEffect, useRef, useState } from 'react';
import {
    Sparkles,
    ChevronDown,
    Dices,
    Images,
    Cpu,
    Crop,
    Palette,
    Copy,
} from 'lucide-react';
import { Panel, NotReady, Remaining } from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';

/**
 * Создание изображения по описанию.
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
    maxVariants = 4,
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
    const [variants, setVariants] = useState(1);
    const [advanced, setAdvanced] = useState(false);
    const textareaRef = useRef(null);

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
        textareaRef.current?.focus();
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
            variants,
        });
    };

    const variantOptions = Array.from({ length: maxVariants }, (_, index) => ({
        key: String(index + 1),
        label: index === 0 ? '1 image' : `${index + 1} images`,
        description: index === 0 ? 'One result' : 'Pick from several at once',
    }));

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <textarea
                ref={textareaRef}
                value={prompt}
                onChange={(event) => setPrompt(event.target.value)}
                placeholder="Describe your image…"
                rows={3}
                className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
            />

            <div className="mt-4 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={model}
                    onChange={setModel}
                    options={models}
                    label="Model"
                    icon={Cpu}
                />

                <Dropdown
                    value={ratio}
                    onChange={setRatio}
                    options={aspectRatios}
                    label="Size"
                    icon={Crop}
                />

                <Dropdown
                    value={style}
                    onChange={setStyle}
                    options={styles}
                    label="Style"
                    icon={Palette}
                />

                {/* Несколько вариантов одной идеи: выбрать из набора
                    проще, чем нажимать «ещё раз». */}
                <Dropdown
                    value={String(variants)}
                    onChange={(next) => setVariants(Number(next))}
                    options={variantOptions}
                    label="Count"
                    icon={Copy}
                />

                <button
                    type="button"
                    onClick={() => setAdvanced((value) => !value)}
                    className="flex h-9 items-center gap-1.5 rounded-full border border-white/[0.12] px-3.5 text-[13px] text-white/60 transition hover:bg-white/10 hover:text-white"
                >
                    Advanced
                    <ChevronDown
                        className={`h-3.5 w-3.5 transition ${advanced ? 'rotate-180' : ''}`}
                        strokeWidth={2}
                    />
                </button>

                <div className="ml-auto flex items-center gap-3">
                    <Remaining limit={limit} />

                    <button
                        type="submit"
                        disabled={!canSubmit}
                        className={`flex h-10 items-center gap-2 rounded-full px-5 text-[14px] font-semibold transition ${
                            canSubmit
                                ? 'bg-white text-black hover:bg-white/90'
                                : 'cursor-not-allowed border border-white/[0.12] text-white/35'
                        }`}
                    >
                        {variants > 1 ? (
                            <Images className="h-4 w-4" strokeWidth={2} />
                        ) : (
                            <Sparkles className="h-4 w-4" strokeWidth={2} />
                        )}
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
                                    setSeed(event.target.value.replace(/\D/g, ''))
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
                                            Math.floor(Math.random() * 2147483647) + 1,
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
        </Panel>
    );
}
