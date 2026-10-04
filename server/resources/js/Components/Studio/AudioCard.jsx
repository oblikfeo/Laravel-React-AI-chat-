import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Play,
    Pause,
    Download,
    RotateCw,
    Trash2,
    AudioWaveform,
    AlertCircle,
} from 'lucide-react';

/** Как называется работа в галерее. */
const KINDS = {
    music: 'Music',
    effect: 'Sound Effect',
    speech: 'Voice',
    voice_change: 'Voice Changer',
};

/**
 * Запись в галерее: обложка, название, проигрыватель.
 *
 * Пока провайдер считает, на месте проигрывателя идёт полоса
 * ожидания: человек должен видеть, что работа не потерялась.
 */
export default function AudioCard({ item, fresh }) {
    const audioRef = useRef(null);
    const [playing, setPlaying] = useState(false);
    const [progress, setProgress] = useState(0);
    const [duration, setDuration] = useState(0);

    const queued = item.status === 'queued';
    const failed = item.status === 'failed';

    useEffect(() => {
        const node = audioRef.current;

        if (!node) {
            return;
        }

        const onTime = () => setProgress(node.currentTime);
        const onMeta = () => setDuration(node.duration || 0);
        const onEnd = () => setPlaying(false);

        node.addEventListener('timeupdate', onTime);
        node.addEventListener('loadedmetadata', onMeta);
        node.addEventListener('ended', onEnd);

        return () => {
            node.removeEventListener('timeupdate', onTime);
            node.removeEventListener('loadedmetadata', onMeta);
            node.removeEventListener('ended', onEnd);
        };
    }, [item.url]);

    const toggle = () => {
        const node = audioRef.current;

        if (!node) {
            return;
        }

        if (playing) {
            node.pause();
        } else {
            node.play();
        }

        setPlaying(!playing);
    };

    const seek = (event) => {
        const node = audioRef.current;

        if (!node || !duration) {
            return;
        }

        const box = event.currentTarget.getBoundingClientRect();
        const part = (event.clientX - box.left) / box.width;

        node.currentTime = part * duration;
        setProgress(node.currentTime);
    };

    const remove = () =>
        router.delete(`/studio/${item.id}`, {
            preserveScroll: true,
            showProgress: false,
        });

    const retry = () =>
        router.post(
            `/studio/${item.id}/retry`,
            {},
            { preserveScroll: true, showProgress: false },
        );

    return (
        <div
            className={`relative overflow-hidden rounded-2xl border bg-slate-950/55 p-3 transition ${
                fresh
                    ? 'border-sky-300/50 ring-1 ring-sky-300/25'
                    : queued
                      ? 'border-sky-300/25'
                      : 'border-white/[0.08]'
            }`}
        >
            {queued && (
                <span
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-y-0 -left-1/3 w-1/3 bg-gradient-to-r from-transparent via-white/[0.07] to-transparent"
                    style={{ animation: 'work-sheen 2.2s ease-in-out infinite' }}
                />
            )}

            <div className="relative flex gap-3">
                <div className="flex h-[88px] w-[88px] shrink-0 items-center justify-center rounded-xl bg-white/[0.06]">
                    {failed ? (
                        <AlertCircle
                            className="h-7 w-7 text-rose-300/70"
                            strokeWidth={1.5}
                        />
                    ) : (
                        <AudioWaveform
                            className={`h-8 w-8 ${
                                queued ? 'text-sky-300/60' : 'text-white/45'
                            }`}
                            strokeWidth={1.5}
                            style={
                                queued
                                    ? {
                                          animation:
                                              'work-pulse 1.8s ease-in-out infinite',
                                      }
                                    : undefined
                            }
                        />
                    )}
                </div>

                <div className="flex min-w-0 flex-1 flex-col">
                    <p className="text-[12px] text-white/40">
                        {KINDS[item.operation] ?? 'Audio'}
                        {item.model ? ` · ${item.model}` : ''}
                    </p>

                    <p className="mt-0.5 line-clamp-2 text-[14px] leading-snug text-white/85">
                        {failed ? 'Could not create this one' : item.prompt}
                    </p>

                    {queued ? (
                        <Waiting />
                    ) : item.url ? (
                        <div className="mt-auto flex items-center gap-3 pt-2">
                            <button
                                type="button"
                                onClick={toggle}
                                aria-label={playing ? 'Pause' : 'Play'}
                                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-black transition hover:bg-white/90"
                            >
                                {playing ? (
                                    <Pause
                                        className="h-4 w-4"
                                        strokeWidth={2.5}
                                        fill="currentColor"
                                    />
                                ) : (
                                    <Play
                                        className="ml-0.5 h-4 w-4"
                                        strokeWidth={2.5}
                                        fill="currentColor"
                                    />
                                )}
                            </button>

                            <div className="min-w-0 flex-1">
                                <div
                                    onClick={seek}
                                    role="presentation"
                                    className="h-1.5 cursor-pointer rounded-full bg-white/15"
                                >
                                    <div
                                        className="h-full rounded-full bg-white transition-[width]"
                                        style={{
                                            width: duration
                                                ? `${(progress / duration) * 100}%`
                                                : '0%',
                                        }}
                                    />
                                </div>

                                <div className="mt-1 flex justify-between text-[11px] text-white/35">
                                    <span>{clock(progress)}</span>
                                    <span>{clock(duration)}</span>
                                </div>
                            </div>

                            <audio
                                ref={audioRef}
                                src={item.url}
                                preload="metadata"
                                className="hidden"
                            />
                        </div>
                    ) : null}
                </div>
            </div>

            {!queued && (
                <div className="mt-2.5 flex items-center gap-1.5 border-t border-white/[0.06] pt-2.5">
                    {item.url && (
                        <Action
                            label="Download"
                            href={`${item.url}?download=1`}
                            icon={Download}
                        />
                    )}

                    <Action
                        label={failed ? 'Try again' : 'Again'}
                        onClick={retry}
                        icon={RotateCw}
                    />

                    <Action
                        label="Delete"
                        onClick={remove}
                        icon={Trash2}
                        danger
                    />
                </div>
            )}
        </div>
    );
}

/**
 * Ожидание готовности.
 *
 * Без полосы прогресса: провайдер не сообщает, как далеко
 * продвинулся, и полоса либо врёт, либо замирает у края. Вместо неё
 * бегущие полоски и счётчик времени — ничего не обещают, но видно,
 * что работа идёт.
 */
function Waiting() {
    const [elapsed, setElapsed] = useState(0);

    useEffect(() => {
        const started = Date.now();
        const timer = setInterval(() => setElapsed(Date.now() - started), 500);

        return () => clearInterval(timer);
    }, []);

    return (
        <div className="mt-auto flex items-center gap-2.5 pt-3">
            <span className="flex items-end gap-[3px]">
                {[0, 1, 2, 3, 4].map((index) => (
                    <span
                        key={index}
                        className="w-[3px] rounded-full bg-sky-300/70"
                        style={{
                            height: `${8 + (index % 3) * 5}px`,
                            animation: `work-pulse 1.1s ease-in-out ${
                                index * 0.13
                            }s infinite`,
                        }}
                    />
                ))}
            </span>

            <span className="text-[12px] text-white/45">
                Working on it… {clock(elapsed / 1000)}
            </span>
        </div>
    );
}

function Action({ label, icon: Icon, onClick, href, danger }) {
    const className = `flex h-8 items-center gap-1.5 rounded-lg border border-white/[0.1] bg-white/[0.03] px-2.5 text-[12px] transition hover:bg-white/[0.12] ${
        danger
            ? 'text-white/55 hover:border-rose-300/30 hover:text-rose-300'
            : 'text-white/70 hover:text-white'
    }`;

    const inner = (
        <>
            <Icon className="h-3.5 w-3.5 shrink-0" strokeWidth={1.75} />
            <span>{label}</span>
        </>
    );

    return href ? (
        <a href={href} aria-label={label} className={className}>
            {inner}
        </a>
    ) : (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            className={className}
        >
            {inner}
        </button>
    );
}

function clock(seconds) {
    if (!seconds || !Number.isFinite(seconds)) {
        return '0:00';
    }

    const whole = Math.floor(seconds);

    return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}`;
}
