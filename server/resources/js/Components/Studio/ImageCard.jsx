import { Download, RotateCw, Trash2, Wand2, Pencil, Maximize2 } from 'lucide-react';
import CardAction from '@/Components/Studio/CardAction';
import { removeWork, retryWork } from '@/Components/Studio/workActions';

/**
 * Готовая картинка в галерее: изображение слева, действия справа.
 *
 * Кнопки стоят столбиком и всегда на виду, а не поверх картинки:
 * так они не закрывают её и их не приходится искать наведением.
 */
export default function ImageCard({ item, fresh, onOpen, onReuse, onEdit }) {
    return (
        <div
            className={`relative flex gap-3 rounded-2xl border bg-slate-950/55 p-3 transition ${
                fresh ? 'border-sky-300/50 ring-1 ring-sky-300/25' : 'border-white/[0.08]'
            }`}
        >
            <div className="aspect-square min-w-0 flex-1 shrink overflow-hidden rounded-xl bg-black/40">
                <button
                    type="button"
                    onClick={() => onOpen(item)}
                    className="group relative h-full w-full"
                    aria-label="View full size"
                >
                    <img
                        src={item.thumbnail ?? item.url}
                        alt={item.prompt}
                        loading="lazy"
                        decoding="async"
                        className="h-full w-full object-cover"
                    />

                    <span className="on-media absolute inset-0 flex items-center justify-center bg-black/55 opacity-0 transition group-hover:opacity-100">
                        <Maximize2 className="h-5 w-5 text-white" strokeWidth={2} />
                    </span>
                </button>
            </div>

            <div className="flex w-[106px] shrink-0 flex-col gap-1">
                <CardAction label="Download" href={`${item.url}?download=1`} icon={Download} />
                <CardAction label="Again" onClick={() => retryWork(item)} icon={RotateCw} />
                <CardAction label="Settings" onClick={() => onReuse(item)} icon={Wand2} />
                <CardAction label="Edit" onClick={() => onEdit(item)} icon={Pencil} />
                <CardAction label="Delete" onClick={() => removeWork(item)} icon={Trash2} danger />
            </div>
        </div>
    );
}
