import { useEffect, useState } from 'react';
import { Send, Clock, Cpu, FileText } from 'lucide-react';
import { Panel, NotReady, SubmitBar } from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';
import { priceOf } from '@/Components/Studio/price';
import Counter from '@/Components/Studio/Audio/Counter';
import { formatSeconds, stepsBetween } from '@/Components/Studio/Audio/duration';

/** Создание музыки. */
export default function MusicForm({
    models,
    defaultModel,
    maxLyrics,
    available,
    busy,
    limit,
    incoming = null,
    onSubmit,
}) {
    const [prompt, setPrompt] = useState('');

    // Просьба, принесённая из чата или ленты: заполняем форму.
    useEffect(() => {
        if (!incoming) {
            return;
        }

        setPrompt(incoming.prompt ?? '');
    }, [incoming]);
    const [model, setModel] = useState(defaultModel);
    const [lyrics, setLyrics] = useState('');
    const [showLyrics, setShowLyrics] = useState(false);
    const [duration, setDuration] = useState(null);

    const current = models.find((item) => item.key === model) ?? models[0];

    // Длительность задаётся по-разному: одни модели принимают любое
    // значение, другие — только из своего набора.
    const durationOptions = current?.durations
        ? current.durations.map((value) => ({
              key: String(value),
              label: formatSeconds(value),
          }))
        : stepsBetween(current?.minDuration, current?.maxDuration);

    const chosenDuration =
        duration ?? String(current?.defaultDuration ?? durationOptions[0]?.key ?? 60);

    const lyricsNeeded = current?.lyricsRequired;
    const exhausted = limit?.remaining === 0;
    const canSubmit =
        prompt.trim().length >= 10 &&
        (!lyricsNeeded || lyrics.trim().length > 0) &&
        !busy &&
        available &&
        !exhausted;

    const submit = (event) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        onSubmit({
            prompt: prompt.trim(),
            model,
            lyrics: current?.lyrics && lyrics.trim() ? lyrics.trim() : null,
            duration: durationOptions.length ? Number(chosenDuration) : null,
        });

        // Запрос ушёл и виден в очереди — форма готова к следующему.
        // Настройки остаются: менять их каждый раз незачем.
        setPrompt('');
        setLyrics('');
    };

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <textarea
                value={prompt}
                onChange={(event) => setPrompt(event.target.value)}
                placeholder="Describe the music you want to make…"
                rows={3}
                className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
            />

            <Counter value={prompt.length} max={300} />

            {(showLyrics || lyricsNeeded) && current?.lyrics && (
                <div className="mt-3 border-t border-white/[0.07] pt-3">
                    <span className="mb-1.5 flex items-center justify-between text-[13px] text-white/55">
                        Lyrics
                        {!lyricsNeeded && (
                            <button
                                type="button"
                                onClick={() => {
                                    setShowLyrics(false);
                                    setLyrics('');
                                }}
                                className="text-white/35 transition hover:text-white"
                            >
                                Remove
                            </button>
                        )}
                    </span>

                    <textarea
                        value={lyrics}
                        onChange={(event) => setLyrics(event.target.value)}
                        placeholder={'[Verse]\nWrite the words to be sung…'}
                        rows={4}
                        maxLength={maxLyrics}
                        className="w-full resize-none rounded-xl border border-white/[0.1] bg-white/[0.03] p-3 text-[14px] leading-relaxed text-white outline-none transition placeholder:text-white/30 focus:border-white/25"
                    />
                </div>
            )}

            <div className="mt-4 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={model}
                    onChange={(next) => {
                        setModel(next);
                        setDuration(null);
                    }}
                    options={models}
                    label="Style"
                    icon={Cpu}
                />

                {durationOptions.length > 0 && (
                    <Dropdown
                        value={chosenDuration}
                        onChange={setDuration}
                        options={durationOptions}
                        label="Length"
                        icon={Clock}
                    />
                )}

                {current?.lyrics && !showLyrics && !lyricsNeeded && (
                    <button
                        type="button"
                        onClick={() => setShowLyrics(true)}
                        className="flex h-9 items-center gap-2 rounded-full border border-white/[0.12] px-3.5 text-[13px] text-white/60 transition hover:bg-white/10 hover:text-white"
                    >
                        <FileText className="h-4 w-4" strokeWidth={1.75} />
                        Add lyrics
                    </button>
                )}

                <SubmitBar icon={Send}
                    busy={busy}
                    canSubmit={canSubmit}
                    limit={limit}
                    price={priceOf(current?.price, {
                        seconds: Number(chosenDuration),
                    })}
                />
            </div>
        </Panel>
    );
}
