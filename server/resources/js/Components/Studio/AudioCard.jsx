import { useEffect, useRef, useState } from 'react';
import { Play, Pause, Download, RotateCw, Trash2, AudioWaveform } from 'lucide-react';
import CardAction from '@/Components/Studio/CardAction';
import { removeWork, retryWork } from '@/Components/Studio/workActions';

/** Как называется работа в галерее. */
const KINDS = {
    music: 'Music',
    effect: 'Sound Effect',
    speech: 'Voice',
    voice_change: 'Voice Changer',
};

/**
 * Готовая запись в галерее: обложка, название, проигрыватель.
 */
export default function AudioCard({ item, fresh }) {
    const audioRef = useRef(null);
    const [playing, setPlaying] = useState(false);
    const [progress, setProgress] = useState(0);
    const [duration, setDuration] = useState(0);

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

        node.currentTime = ((event.clientX - box.left) / box.width) * duration;
        setProgress(node.currentTime);
    };

    return (
        <div
            className={`relative rounded-2xl border bg-slate-950/55 p-3 transition ${
                fresh ? 'border-sky-300/50 ring-1 ring-sky-300/25' : 'border-white/[0.08]'
            }`}
        >
            <div className="relative flex gap-3">
                <div className="flex h-[88px] w-[88px] shrink-0 items-center justify-center rounded-xl bg-white/[0.06]">
                    <AudioWaveform className="h-8 w-8 text-white/45" strokeWidth={1.5} />
                </div>

                <div className="flex min-w-0 flex-1 flex-col">
                    <p className="text-[12px] text-white/40">
                        {KINDS[item.operation] ?? 'Audio'}
                        {item.model ? ` · ${item.model}` : ''}
                    </p>

                    <p className="mt-0.5 line-clamp-2 text-[14px] leading-snug text-white/85">
                        {item.prompt}
                    </p>

                    <div className="mt-auto flex items-center gap-3 pt-2">
                        <button
                            type="button"
                            onClick={toggle}
                            aria-label={playing ? 'Pause' : 'Play'}
                            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-black transition hover:bg-white/90"
                        >
                            {playing ? (
                                <Pause className="h-4 w-4" strokeWidth={2.5} fill="currentColor" />
                            ) : (
                                <Play className="ml-0.5 h-4 w-4" strokeWidth={2.5} fill="currentColor" />
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
                                        width: duration ? `${(progress / duration) * 100}%` : '0%',
                                    }}
                                />
                            </div>

                            <div className="mt-1 flex justify-between text-[11px] text-white/35">
                                <span>{clock(progress)}</span>
                                <span>{clock(duration)}</span>
                            </div>
                        </div>

                        <audio ref={audioRef} src={item.url} preload="metadata" className="hidden" />
                    </div>
                </div>
            </div>

            <div className="mt-2.5 flex items-center gap-1.5 border-t border-white/[0.06] pt-2.5">
                <CardAction label="Download" href={`${item.url}?download=1`} icon={Download} />
                <CardAction label="Again" onClick={() => retryWork(item)} icon={RotateCw} />
                <CardAction label="Delete" onClick={() => removeWork(item)} icon={Trash2} danger />
            </div>
        </div>
    );
}

/** Секунды в виде «1:05». */
function clock(seconds) {
    if (!seconds || !Number.isFinite(seconds)) {
        return '0:00';
    }

    const whole = Math.floor(seconds);

    return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}`;
}
