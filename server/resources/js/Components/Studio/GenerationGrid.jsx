import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Download,
    RotateCw,
    Trash2,
    Wand2,
    AlertCircle,
    Pencil,
    Volume2,
} from 'lucide-react';

/**
 * Лента работ: свежие сверху.
 *
 * Действия появляются при наведении, чтобы не перегружать сетку.
 */
export default function GenerationGrid({ items, onReuse, onEdit }) {
    const [preview, setPreview] = useState(null);

    if (!items.length) {
        return (
            <div className="mt-8 rounded-2xl border border-white/[0.07] bg-slate-950/40 px-6 py-14 text-center backdrop-blur-md">
                <Wand2
                    className="mx-auto h-7 w-7 text-white/20"
                    strokeWidth={1.5}
                />
                <p className="mt-3 text-[15px] text-white/60">
                    Nothing here yet
                </p>
                <p className="mt-1 text-sm text-white/35">
                    Describe an image above and it will appear here.
                </p>
            </div>
        );
    }

    return (
        <>
            <div className="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                {items.map((item) => (
                    <Card
                        key={item.id}
                        item={item}
                        onReuse={onReuse}
                        onEdit={onEdit}
                        onOpen={() =>
                            item.url && item.kind !== 'audio' && setPreview(item)
                        }
                    />
                ))}
            </div>

            {preview && (
                <Lightbox item={preview} onClose={() => setPreview(null)} />
            )}
        </>
    );
}

function Card({ item, onReuse, onEdit, onOpen }) {
    const failed = item.status === 'failed';
    const isAudio = item.kind === 'audio';

    const remove = () => {
        router.delete(`/studio/${item.id}`, {
            preserveScroll: true,
            showProgress: false,
        });
    };

    const retry = () => {
        router.post(
            `/studio/${item.id}/retry`,
            {},
            { preserveScroll: true, showProgress: false },
        );
    };

    return (
        <div className="group relative overflow-hidden rounded-2xl border border-white/[0.08] bg-slate-950/55 backdrop-blur-xl">
            <div className="aspect-square w-full">
                {isAudio && item.url ? (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-3 px-4">
                        <Volume2
                            className="h-7 w-7 text-white/35"
                            strokeWidth={1.5}
                        />
                        <audio
                            controls
                            src={item.url}
                            className="w-full"
                        />
                    </div>
                ) : item.url ? (
                    <button
                        type="button"
                        onClick={onOpen}
                        className="h-full w-full"
                    >
                        <img
                            src={item.url}
                            alt={item.prompt}
                            loading="lazy"
                            className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                        />
                    </button>
                ) : (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-2 px-4 text-center">
                        {failed ? (
                            <>
                                <AlertCircle
                                    className="h-5 w-5 text-rose-300/70"
                                    strokeWidth={1.75}
                                />
                                <span className="text-[13px] text-white/50">
                                    Could not create this one
                                </span>
                            </>
                        ) : (
                            <span className="h-6 w-6 animate-spin rounded-full border-2 border-white/15 border-t-white/60" />
                        )}
                    </div>
                )}
            </div>

            {/* Подпись и действия проявляются при наведении. */}
            <div className="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/45 to-transparent p-3 opacity-0 transition group-hover:opacity-100">
                <p className="line-clamp-2 text-[12px] leading-snug text-white/85">
                    {item.prompt}
                </p>

                <div className="pointer-events-auto mt-2 flex items-center gap-1.5">
                    {item.url && (
                        <IconButton
                            title="Download"
                            href={`${item.url}?download=1`}
                            icon={Download}
                        />
                    )}

                    <IconButton
                        title={failed ? 'Try again' : 'Create another'}
                        onClick={retry}
                        icon={RotateCw}
                    />

                    {!isAudio && (
                        <IconButton
                            title="Use these settings"
                            onClick={() => onReuse(item)}
                            icon={Wand2}
                        />
                    )}

                    {!isAudio && item.url && onEdit && (
                        <IconButton
                            title="Edit this image"
                            onClick={() => onEdit(item)}
                            icon={Pencil}
                        />
                    )}

                    <IconButton
                        title="Delete"
                        onClick={remove}
                        icon={Trash2}
                        danger
                    />
                </div>
            </div>
        </div>
    );
}

function IconButton({ title, icon: Icon, onClick, href, danger }) {
    const className = `flex h-8 w-8 items-center justify-center rounded-lg border border-white/15 bg-black/40 backdrop-blur transition hover:bg-white/15 ${
        danger ? 'text-white/70 hover:text-rose-300' : 'text-white/80 hover:text-white'
    }`;

    if (href) {
        return (
            <a href={href} title={title} aria-label={title} className={className}>
                <Icon className="h-3.5 w-3.5" strokeWidth={1.75} />
            </a>
        );
    }

    return (
        <button
            type="button"
            onClick={onClick}
            title={title}
            aria-label={title}
            className={className}
        >
            <Icon className="h-3.5 w-3.5" strokeWidth={1.75} />
        </button>
    );
}

/** Просмотр во весь экран. */
function Lightbox({ item, onClose }) {
    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
        >
            <div
                className="max-h-full w-auto"
                onClick={(event) => event.stopPropagation()}
            >
                <img
                    src={item.url}
                    alt={item.prompt}
                    className="max-h-[80vh] w-auto rounded-2xl"
                />

                <div className="mx-auto mt-4 max-w-[640px] text-center">
                    <p className="text-[14px] leading-relaxed text-white/80">
                        {item.prompt}
                    </p>

                    <p className="mt-2 text-[12px] text-white/35">
                        {item.model} · {item.aspectRatio} · seed {item.seed}
                    </p>
                </div>
            </div>
        </div>
    );
}
