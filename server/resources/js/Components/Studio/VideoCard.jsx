import { Download, RotateCw, Trash2 } from 'lucide-react';
import CardAction from '@/Components/Studio/CardAction';
import { removeWork, retryWork } from '@/Components/Studio/workActions';

/** Как называется работа в галерее. */
const KINDS = {
    video: 'Text to Video',
    animate: 'Animated Image',
};

/**
 * Готовый ролик в галерее: проигрыватель, подпись, действия.
 */
export default function VideoCard({ item, fresh }) {
    const details = [
        KINDS[item.operation] ?? 'Video',
        item.duration ? `${item.duration} sec` : null,
        item.resolution,
    ].filter(Boolean);

    return (
        <div
            className={`relative rounded-2xl border bg-slate-950/55 p-3 transition ${
                fresh ? 'border-sky-300/50 ring-1 ring-sky-300/25' : 'border-white/[0.08]'
            }`}
        >
            <div className="on-media media-viewer relative flex aspect-video items-center justify-center overflow-hidden rounded-xl bg-black">
                <video
                    src={item.url}
                    poster={item.poster ?? undefined}
                    controls
                    playsInline
                    preload="metadata"
                    className="h-full w-full object-contain"
                />
            </div>

            <p className="mt-2.5 text-[12px] text-white/40">{details.join(' · ')}</p>

            <p className="mt-0.5 line-clamp-2 text-[14px] leading-snug text-white/85">{item.prompt}</p>

            <div className="mt-2.5 flex flex-wrap items-center gap-1.5 border-t border-white/[0.06] pt-2.5">
                <CardAction label="Download" href={`${item.url}?download=1`} icon={Download} />
                <CardAction label="Again" onClick={() => retryWork(item)} icon={RotateCw} />
                <CardAction label="Delete" onClick={() => removeWork(item)} icon={Trash2} danger />
            </div>
        </div>
    );
}
