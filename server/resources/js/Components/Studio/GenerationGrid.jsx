import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import AudioCard from '@/Components/Studio/AudioCard';
import {
    Download,
    RotateCw,
    Trash2,
    Wand2,
    AlertCircle,
    Pencil,
    Volume2,
    Images,
    ChevronDown,
    Maximize2,
    X,
} from 'lucide-react';

/**
 * Галерея работ.
 *
 * Свёрнута по умолчанию: картинки, висящие под формой, отвлекают от
 * работы и занимают весь экран. В заголовке видно, сколько их всего,
 * и свежий результат разворачивает галерею сам.
 */
export default function GenerationGrid({
    items,
    onReuse,
    onEdit,
    highlight,
    kind = 'image',
}) {
    const [open, setOpen] = useState(false);
    const [preview, setPreview] = useState(null);
    const seen = useRef(null);
    const ready = useRef(null);

    // Галерея открывается сама и когда появилась новая работа, и
    // когда дождались готовой: человек нажал кнопку и должен увидеть
    // результат, а не гадать, что произошло.
    useEffect(() => {
        const newest = items[0]?.id ?? null;
        const done = items.filter((item) => item.status === 'ready').length;

        const appeared =
            newest !== null && seen.current !== null && newest !== seen.current;
        const finished = ready.current !== null && done > ready.current;

        if (appeared || finished) {
            setOpen(true);
        }

        seen.current = newest;
        ready.current = done;
    }, [items]);

    const readyCount = items.filter((item) => item.status === 'ready').length;
    const audio = kind === 'audio';

    return (
        <div className="mt-6">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="flex w-full items-center gap-3 rounded-2xl border border-white/[0.09] bg-slate-950/45 px-4 py-3 text-left transition hover:border-white/20 hover:bg-slate-950/60"
            >
                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/[0.07] text-white/60">
                    {audio ? (
                        <Volume2 className="h-[18px] w-[18px]" strokeWidth={1.75} />
                    ) : (
                        <Images className="h-[18px] w-[18px]" strokeWidth={1.75} />
                    )}
                </span>

                <span className="min-w-0 flex-1">
                    <span className="block text-[14px] font-medium text-white">
                        {audio ? 'Recordings' : 'Gallery'}
                    </span>
                    <span className="mt-0.5 block text-[12px] text-white/40">
                        {readyCount
                            ? `${readyCount} ${
                                  audio
                                      ? readyCount === 1
                                          ? 'recording'
                                          : 'recordings'
                                      : readyCount === 1
                                        ? 'work'
                                        : 'works'
                              } saved`
                            : 'Nothing saved yet'}
                    </span>
                </span>

                <ChevronDown
                    className={`h-4 w-4 shrink-0 text-white/40 transition ${
                        open ? 'rotate-180' : ''
                    }`}
                    strokeWidth={2}
                />
            </button>

            {open && (
                <div className="mt-3">
                    {items.length ? (
                        <div
                            className={`grid gap-3 ${
                                audio ? '' : 'sm:grid-cols-2 xl:grid-cols-3'
                            }`}
                        >
                            {items.map((item) =>
                                item.kind === 'audio' ? (
                                    <AudioCard
                                        key={item.id}
                                        item={item}
                                        fresh={item.id === highlight}
                                    />
                                ) : (
                                <Card
                                    key={item.id}
                                    item={item}
                                    audio={audio}
                                    fresh={item.id === highlight}
                                    onReuse={onReuse}
                                    onEdit={onEdit}
                                    onOpen={() => item.url && setPreview(item)}
                                />
                                ),
                            )}
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-white/[0.07] bg-slate-950/40 px-6 py-12 text-center">
                            {audio ? (
                                <Volume2
                                    className="mx-auto h-7 w-7 text-white/20"
                                    strokeWidth={1.5}
                                />
                            ) : (
                                <Wand2
                                    className="mx-auto h-7 w-7 text-white/20"
                                    strokeWidth={1.5}
                                />
                            )}
                            <p className="mt-3 text-[15px] text-white/60">
                                Nothing here yet
                            </p>
                            <p className="mt-1 text-sm text-white/35">
                                {audio
                                    ? 'Your recordings will appear here.'
                                    : 'Your finished work will appear here.'}
                            </p>
                        </div>
                    )}
                </div>
            )}

            {preview && (
                <Lightbox item={preview} onClose={() => setPreview(null)} />
            )}
        </div>
    );
}

/**
 * Работа: картинка слева, действия и подпись справа.
 *
 * Кнопки поверх картинки прятали её и появлялись только при
 * наведении — на ощупь. Здесь они всегда на виду.
 */
function Card({ item, fresh, audio, onReuse, onEdit, onOpen }) {
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
        <div
            className={`flex gap-3 rounded-2xl border bg-slate-950/55 p-3 transition ${
                fresh
                    ? 'border-sky-300/50 ring-1 ring-sky-300/25'
                    : 'border-white/[0.08]'
            }`}
        >
            {/* Картинка занимает карточку: ради неё сюда и смотрят.
                Записи хватает строки — показывать нечего. */}
            <div
                className={`min-w-0 flex-1 shrink overflow-hidden rounded-xl bg-black/40 ${
                    audio ? '' : 'aspect-square'
                }`}
            >
                {isAudio && item.url ? (
                    <div className="flex h-full w-full flex-col justify-center gap-2 px-4 py-3">
                        <p className="line-clamp-2 text-[13px] leading-snug text-white/75">
                            {item.prompt}
                        </p>
                        <audio controls src={item.url} className="w-full" />
                    </div>
                ) : item.url ? (
                    <button
                        type="button"
                        onClick={onOpen}
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

                        <span className="absolute inset-0 flex items-center justify-center bg-black/55 opacity-0 transition group-hover:opacity-100">
                            <Maximize2
                                className="h-5 w-5 text-white"
                                strokeWidth={2}
                            />
                        </span>
                    </button>
                ) : (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-2 px-4 text-center">
                        {failed ? (
                            <>
                                <AlertCircle
                                    className="h-5 w-5 text-rose-300/70"
                                    strokeWidth={1.75}
                                />
                                <span className="text-[12px] text-white/45">
                                    Could not create this one
                                </span>
                            </>
                        ) : (
                            <span className="h-6 w-6 animate-spin rounded-full border-2 border-white/15 border-t-white/60" />
                        )}
                    </div>
                )}
            </div>

            {/* Действия столбиком во всю высоту картинки: у каждой
                кнопки значок и подпись, иначе их приходится угадывать. */}
            <div className="flex w-[106px] shrink-0 flex-col gap-1">
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

                {!isAudio && (
                    <Action
                        label="Settings"
                        onClick={() => onReuse(item)}
                        icon={Wand2}
                    />
                )}

                {!isAudio && item.url && onEdit && (
                    <Action
                        label="Edit"
                        onClick={() => onEdit(item)}
                        icon={Pencil}
                    />
                )}

                <Action
                    label="Delete"
                    onClick={remove}
                    icon={Trash2}
                    danger
                />
            </div>
        </div>
    );
}

function Action({ label, icon: Icon, onClick, href, danger }) {
    const className = `flex h-9 items-center gap-2 rounded-lg border border-white/[0.1] bg-white/[0.03] px-2.5 text-[12px] transition hover:bg-white/[0.12] ${
        danger
            ? 'text-white/55 hover:border-rose-300/30 hover:text-rose-300'
            : 'text-white/70 hover:text-white'
    }`;

    const inner = (
        <>
            <Icon className="h-3.5 w-3.5 shrink-0" strokeWidth={1.75} />
            <span className="truncate">{label}</span>
        </>
    );

    if (href) {
        return (
            <a href={href} aria-label={label} className={className}>
                {inner}
            </a>
        );
    }

    return (
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

/** Просмотр во весь экран. */
function Lightbox({ item, onClose }) {
    return (
        <div
            className="fixed inset-0 z-[70] flex items-center justify-center bg-black/92 p-4 backdrop-blur-sm"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                onClick={onClose}
                aria-label="Close"
                className="absolute right-4 top-4 rounded-lg p-2 text-white/60 transition hover:bg-white/10 hover:text-white"
            >
                <X className="h-5 w-5" strokeWidth={1.75} />
            </button>

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
                        {[item.model, item.aspectRatio, item.seed ? `seed ${item.seed}` : null]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </div>
            </div>
        </div>
    );
}
