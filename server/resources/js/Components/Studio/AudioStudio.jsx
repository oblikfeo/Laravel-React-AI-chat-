import { useEffect, useRef, useState } from 'react';
import {
    Music,
    Mic,
    Repeat,
    AudioWaveform,
    Send,
    Upload,
    Gauge,
    Clock,
    Cpu,
    FileText,
    Square,
    X,
} from 'lucide-react';
import { Panel, NotReady, Remaining } from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';

/** Режимы звука — как в макете. */
const MODES = [
    { key: 'music', label: 'Music', icon: Music },
    { key: 'speech', label: 'Voice', icon: Mic },
    { key: 'changer', label: 'Voice Changer', icon: Repeat },
    { key: 'effect', label: 'Sound Effect', icon: AudioWaveform },
];

/** Скорость чтения: крайние значения звучат неестественно. */
const SPEEDS = [
    { key: '0.75', label: 'Slow', description: 'Calm, easy to follow' },
    { key: '1', label: 'Normal', description: 'Natural pace' },
    { key: '1.25', label: 'Fast', description: 'Brisk delivery' },
    { key: '1.5', label: 'Faster', description: 'For quick listening' },
];

/**
 * Работа со звуком: музыка, озвучка, смена голоса и эффекты.
 *
 * Режимы переключаются над одной формой: у них общее поле ввода и
 * разные настройки под ним.
 */
export default function AudioStudio({
    speechModels,
    musicModels,
    effectModels,
    voices,
    defaultSpeechModel,
    defaultMusicModel,
    defaultEffectModel,
    defaultVoice,
    maxCharacters = 4000,
    maxLyrics = 3000,
    available,
    busy,
    limit,
    mode,
    onModeChange,
    onSubmit,
}) {

    return (
        <div>
            <div className="mb-3 flex flex-wrap justify-center gap-2">
                {MODES.map(({ key, label, icon: Icon }) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => onModeChange(key)}
                        className={`flex h-9 items-center gap-2 rounded-full px-4 text-[13px] transition ${
                            mode === key
                                ? 'bg-white font-semibold text-black'
                                : 'border border-white/[0.12] text-white/65 hover:bg-white/10 hover:text-white'
                        }`}
                    >
                        <Icon className="h-4 w-4" strokeWidth={1.75} />
                        {label}
                    </button>
                ))}
            </div>

            {mode === 'music' && (
                <MusicForm
                    models={musicModels}
                    defaultModel={defaultMusicModel}
                    maxLyrics={maxLyrics}
                    available={available}
                    busy={busy}
                    limit={limit}
                    onSubmit={(values) => onSubmit('music', values)}
                />
            )}

            {mode === 'speech' && (
                <SpeechForm
                    models={speechModels}
                    voices={voices}
                    defaultModel={defaultSpeechModel}
                    defaultVoice={defaultVoice}
                    maxCharacters={maxCharacters}
                    available={available}
                    busy={busy}
                    limit={limit}
                    onSubmit={(values) => onSubmit('speech', values)}
                />
            )}

            {mode === 'changer' && (
                <ChangerForm
                    voices={voices}
                    defaultVoice={defaultVoice}
                    available={available}
                    busy={busy}
                    limit={limit}
                    onSubmit={(values) => onSubmit('voice-change', values)}
                />
            )}

            {mode === 'effect' && (
                <EffectForm
                    models={effectModels}
                    defaultModel={defaultEffectModel}
                    available={available}
                    busy={busy}
                    limit={limit}
                    onSubmit={(values) => onSubmit('effect', values)}
                />
            )}
        </div>
    );
}

/** Создание музыки. */
function MusicForm({
    models,
    defaultModel,
    maxLyrics,
    available,
    busy,
    limit,
    onSubmit,
}) {
    const [prompt, setPrompt] = useState('');
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

                <Submit busy={busy} canSubmit={canSubmit} limit={limit} />
            </div>
        </Panel>
    );
}

/** Озвучка текста. */
function SpeechForm({
    models,
    voices,
    defaultModel,
    defaultVoice,
    maxCharacters,
    available,
    busy,
    limit,
    onSubmit,
}) {
    const [text, setText] = useState('');
    const [model, setModel] = useState(defaultModel);
    const [voice, setVoice] = useState(defaultVoice);
    const [speed, setSpeed] = useState('1');

    const exhausted = limit?.remaining === 0;
    const canSubmit =
        text.trim().length > 0 &&
        text.length <= maxCharacters &&
        !busy &&
        available &&
        !exhausted;

    const submit = (event) => {
        event.preventDefault();

        if (canSubmit) {
            onSubmit({
                text: text.trim(),
                model,
                voice,
                speed: Number(speed),
            });
        }
    };

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <textarea
                value={text}
                onChange={(event) => setText(event.target.value)}
                placeholder="Enter the text you want to convert to speech…"
                rows={4}
                className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
            />

            <Counter value={text.length} max={maxCharacters} />

            <div className="mt-3 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={voice}
                    onChange={setVoice}
                    options={voices}
                    label="Voice"
                    icon={Mic}
                />

                <Dropdown
                    value={model}
                    onChange={setModel}
                    options={models}
                    label="Quality"
                    icon={Cpu}
                />

                <Dropdown
                    value={speed}
                    onChange={setSpeed}
                    options={SPEEDS}
                    label="Speed"
                    icon={Gauge}
                />

                <Submit busy={busy} canSubmit={canSubmit} limit={limit} />
            </div>
        </Panel>
    );
}

/** Смена голоса в записи. */
function ChangerForm({
    voices,
    defaultVoice,
    available,
    busy,
    limit,
    onSubmit,
}) {
    const [file, setFile] = useState(null);
    const [voice, setVoice] = useState(defaultVoice);
    const [recording, setRecording] = useState(false);
    const [seconds, setSeconds] = useState(0);
    const fileRef = useRef(null);
    const recorderRef = useRef(null);
    const chunksRef = useRef([]);
    const timerRef = useRef(null);

    // Запись останавливаем при уходе со страницы: микрофон не должен
    // остаться включённым.
    useEffect(
        () => () => {
            recorderRef.current?.stream
                ?.getTracks()
                .forEach((track) => track.stop());
            clearInterval(timerRef.current);
        },
        [],
    );

    const startRecording = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });

            const recorder = new MediaRecorder(stream);
            chunksRef.current = [];

            recorder.ondataavailable = (event) => {
                if (event.data.size) {
                    chunksRef.current.push(event.data);
                }
            };

            recorder.onstop = () => {
                const blob = new Blob(chunksRef.current, {
                    type: recorder.mimeType || 'audio/webm',
                });

                setFile(
                    new File([blob], 'recording.webm', { type: blob.type }),
                );

                stream.getTracks().forEach((track) => track.stop());
            };

            recorder.start();
            recorderRef.current = recorder;
            setRecording(true);
            setSeconds(0);

            timerRef.current = setInterval(
                () => setSeconds((value) => value + 1),
                1000,
            );
        } catch {
            // Отказ в доступе к микрофону — обычное дело, загрузка
            // файлом остаётся.
            setRecording(false);
        }
    };

    const stopRecording = () => {
        recorderRef.current?.stop();
        clearInterval(timerRef.current);
        setRecording(false);
    };

    const exhausted = limit?.remaining === 0;
    const canSubmit = Boolean(file) && !busy && available && !exhausted;

    const submit = (event) => {
        event.preventDefault();

        if (canSubmit) {
            onSubmit({ recording: file, voice });
        }
    };

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <input
                ref={fileRef}
                type="file"
                accept="audio/*"
                className="hidden"
                onChange={(event) => {
                    setFile(event.target.files[0] ?? null);
                    event.target.value = '';
                }}
            />

            {file ? (
                <div className="flex items-center gap-3 rounded-xl border border-white/[0.12] bg-white/[0.03] p-3">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/[0.07] text-white/60">
                        <AudioWaveform className="h-5 w-5" strokeWidth={1.75} />
                    </span>

                    <span className="min-w-0 flex-1">
                        <span className="block truncate text-[14px] text-white/85">
                            {file.name}
                        </span>
                        <span className="text-[12px] text-white/35">
                            {Math.round(file.size / 1024)} KB
                        </span>
                    </span>

                    <button
                        type="button"
                        onClick={() => setFile(null)}
                        aria-label="Remove"
                        className="rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white"
                    >
                        <X className="h-4 w-4" strokeWidth={1.75} />
                    </button>
                </div>
            ) : (
                <div className="flex flex-wrap items-center gap-2.5 rounded-xl border border-dashed border-white/[0.14] px-4 py-3.5">
                    <span className="mr-auto text-[13px] text-white/50">
                        {recording
                            ? `Recording… ${formatSeconds(seconds)}`
                            : 'Upload or record your speech'}
                    </span>

                    {recording ? (
                        <button
                            type="button"
                            onClick={stopRecording}
                            className="flex h-9 items-center gap-2 rounded-full bg-rose-500 px-4 text-[13px] font-semibold text-white transition hover:bg-rose-400"
                        >
                            <Square className="h-3.5 w-3.5" strokeWidth={2.5} />
                            Stop
                        </button>
                    ) : (
                        <>
                            <button
                                type="button"
                                onClick={() => fileRef.current?.click()}
                                className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-4 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                            >
                                <Upload className="h-4 w-4" strokeWidth={1.75} />
                                Upload
                            </button>

                            <button
                                type="button"
                                onClick={startRecording}
                                className="flex h-9 items-center gap-2 rounded-full bg-white px-4 text-[13px] font-semibold text-black transition hover:bg-white/90"
                            >
                                <Mic className="h-4 w-4" strokeWidth={2} />
                                Record
                            </button>
                        </>
                    )}
                </div>
            )}

            <div className="mt-4 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={voice}
                    onChange={setVoice}
                    options={voices}
                    label="New voice"
                    icon={Mic}
                />

                <Submit busy={busy} canSubmit={canSubmit} limit={limit} />
            </div>
        </Panel>
    );
}

/** Звуковой эффект. */
function EffectForm({
    models,
    defaultModel,
    available,
    busy,
    limit,
    onSubmit,
}) {
    const [prompt, setPrompt] = useState('');
    const [model, setModel] = useState(defaultModel);
    const [duration, setDuration] = useState(null);

    const current = models.find((item) => item.key === model) ?? models[0];
    const durationOptions = stepsBetween(
        current?.minDuration,
        current?.maxDuration,
    );

    const chosenDuration =
        duration ?? String(current?.defaultDuration ?? durationOptions[0]?.key ?? 5);

    const exhausted = limit?.remaining === 0;
    const canSubmit =
        prompt.trim().length > 0 && !busy && available && !exhausted;

    const submit = (event) => {
        event.preventDefault();

        if (canSubmit) {
            onSubmit({
                prompt: prompt.trim(),
                model,
                duration: Number(chosenDuration),
            });
        }
    };

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <textarea
                value={prompt}
                onChange={(event) => setPrompt(event.target.value)}
                placeholder="Spacious braam suitable for high-impact movie trailer moments…"
                rows={3}
                className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
            />

            <Counter value={prompt.length} max={450} />

            <div className="mt-3 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={model}
                    onChange={(next) => {
                        setModel(next);
                        setDuration(null);
                    }}
                    options={models}
                    label="Kind"
                    icon={AudioWaveform}
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

                <Submit busy={busy} canSubmit={canSubmit} limit={limit} />
            </div>
        </Panel>
    );
}

function Submit({ busy, canSubmit, limit }) {
    return (
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
                <Send className="h-4 w-4" strokeWidth={2} />
                {busy ? 'Sending…' : 'Create'}
            </button>
        </div>
    );
}

function Counter({ value, max }) {
    return (
        <div className="mt-1 text-right text-[12px] text-white/35">
            {value}/{max}
        </div>
    );
}

/** Длительность словами: 90 секунд понятнее как «1:30». */
function formatSeconds(total) {
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;

    return minutes
        ? `${minutes}:${String(seconds).padStart(2, '0')}`
        : `${seconds}s`;
}

/**
 * Набор длительностей в заданных границах.
 *
 * Показываем круглые значения, а не каждую секунду: выбирать из
 * сотни пунктов невозможно.
 */
function stepsBetween(min, max) {
    if (!min || !max) {
        return [];
    }

    const candidates = [5, 10, 15, 20, 30, 45, 60, 90, 120, 180, 240, 300, 600];

    return candidates
        .filter((value) => value >= min && value <= max)
        .map((value) => ({ key: String(value), label: formatSeconds(value) }));
}
