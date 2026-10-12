import { router } from '@inertiajs/react';
import { Download, RotateCw, Trash2, Video, AlertCircle } from 'lucide-react';
import { Action, Stars, Waiting } from '@/Components/Studio/AudioCard';

/** Как называется работа в галерее. */
const KINDS = {
    video: 'Text to Video',
    animate: 'Animated Image',
};

/**
 * Ролик в галерее: проигрыватель, подпись, действия.
 *
 * Пока провайдер считает, на месте ролика идёт ожидание со счётчиком
 * времени: ролик снимается минутами, и человек должен видеть, что
 * работа не потерялась.
 */
export default function VideoCard({ item, fresh }) {
    const queued = item.status === 'queued' || item.status === 'pending';
    const failed = item.status === 'failed';

    const remove = () =>
        router.delete(`/studio/${item.id}`, {
            preserveScroll: true,
            async: true,
        });

    const retry = () =>
        router.post(
            `/studio/${item.id}/retry`,
            {},
            { preserveScroll: true, async: true },
        );

    const details = [
        KINDS[item.operation] ?? 'Video',
        item.duration ? `${item.duration} sec` : null,
        item.resolution,
    ].filter(Boolean);

    return (
        <div
            className={`relative rounded-2xl border bg-slate-950/55 p-3 transition ${
                queued ? 'work-border border-white/[0.06]' : ''
            } ${
                fresh
                    ? 'border-sky-300/50 ring-1 ring-sky-300/25'
                    : queued
                      ? ''
                      : 'border-white/[0.08]'
            }`}
        >
            <div className="on-media media-viewer relative flex aspect-video items-center justify-center overflow-hidden rounded-xl bg-black">
                {item.url ? (
                    <video
                        src={item.url}
                        poster={item.poster ?? undefined}
                        controls
                        playsInline
                        preload="metadata"
                        className="h-full w-full object-contain"
                    />
                ) : failed ? (
                    <AlertCircle
                        className="h-7 w-7 text-rose-300/70"
                        strokeWidth={1.5}
                    />
                ) : (
                    <>
                        <Stars />
                        <Video
                            className="h-9 w-9 text-sky-300/60"
                            strokeWidth={1.5}
                            style={{
                                animation: 'work-pulse 1.8s ease-in-out infinite',
                            }}
                        />
                    </>
                )}
            </div>

            <p className="mt-2.5 text-[12px] text-white/40">
                {details.join(' · ')}
            </p>

            <p className="mt-0.5 line-clamp-2 text-[14px] leading-snug text-white/85">
                {failed ? 'Could not create this one' : item.prompt}
            </p>

            {queued ? (
                <Waiting />
            ) : (
                <div className="mt-2.5 flex flex-wrap items-center gap-1.5 border-t border-white/[0.06] pt-2.5">
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
