import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import AudioCard from '@/Components/Studio/AudioCard';
import VideoCard from '@/Components/Studio/VideoCard';
import {
    Download,
    RotateCw,
    Trash2,
    Wand2,
    AlertCircle,
    Pencil,
    Sparkles,
    Images,
    Music,
    Video,
    Plus,
    Maximize2,
    X,
} from 'lucide-react';

/** Как подписана галерея каждого раздела. */
const LOOKS = {
    image: {
        icon: Images,
        title: 'Images',
        one: 'image',
        many: 'images',
        empty: 'Images you create will appear here.',
        action: 'Create an image',
    },
    edit: {
        icon: Wand2,
        title: 'Edits',
        one: 'edit',
        many: 'edits',
        empty: 'Edited, combined and upscaled images will appear here.',
        action: 'Edit an image',
    },
    audio: {
        icon: Music,
        title: 'Audio',
        one: 'track',
        many: 'tracks',
        empty: 'Music, voiceovers and sound effects will appear here.',
        action: 'Create audio',
    },
    video: {
        icon: Video,
        title: 'Videos',
        one: 'video',
        many: 'videos',
        empty: 'Videos you create will appear here.',
        action: 'Create a video',
    },
};

/** Виды звука: в галерее их можно смотреть вместе и по отдельности. */
const AUDIO_KINDS = [
    { key: 'all', label: 'All' },
    { key: 'music', label: 'Music' },
    { key: 'speech', label: 'Voice' },
    { key: 'voice_change', label: 'Voice Changer' },
    { key: 'effect', label: 'Sound Effects' },
];

/**
 * Галерея раздела.
 *
 * Живёт отдельно от редактора: под формой показывается только то, что
 * сейчас создаётся, а готовое лежит здесь. Какую галерею показывать,
 * выбирает переключатель разделов наверху страницы.
 *
 * Только что досчитанные работы подсвечены: человек пришёл сюда за
 * ними и должен увидеть их сразу.
 */
export default function Gallery({
    section,
    items,
    highlighted = [],
    onCreate,
    onReuse,
    onEdit,
}) {
    const [preview, setPreview] = useState(null);
    const [audioKind, setAudioKind] = useState('all');

    const look = LOOKS[section] ?? LOOKS.image;
    const audio = section === 'audio';

    // Свежая работа видна при любом выбранном виде звука.
    useEffect(() => {
        if (highlighted.length) {
            setAudioKind('all');
        }
    }, [highlighted.length]);

    const shown =
        audio && audioKind !== 'all'
            ? items.filter((item) => item.operation === audioKind)
            : items;

    return (
        <div className="mt-6">
            <div className="flex flex-wrap items-center gap-3 rounded-2xl border border-white/[0.09] bg-slate-950/45 px-4 py-3 backdrop-blur-md">
                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/[0.07] text-white/60">
                    <look.icon className="h-[18px] w-[18px]" strokeWidth={1.75} />
                </span>

                <span className="min-w-0 flex-1">
                    <span className="block text-[15px] font-medium text-white">
                        {look.title}
                    </span>
                    <span className="block text-[12px] text-white/40">
                        {items.length
                            ? `${items.length} ${
                                  items.length === 1 ? look.one : look.many
                              } saved`
                            : 'Nothing saved yet'}
                    </span>
                </span>

                <button
                    type="button"
                    onClick={onCreate}
                    className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-4 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                >
                    <Plus className="h-4 w-4" strokeWidth={1.75} />
                    {look.action}
                </button>
            </div>

            {audio && items.length > 0 && (
                <div className="mt-3 flex w-fit max-w-full flex-wrap gap-1 rounded-2xl border border-white/[0.09] bg-slate-950/45 p-1 backdrop-blur-md">
                    {AUDIO_KINDS.map(({ key, label }) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setAudioKind(key)}
                            className={`h-8 rounded-xl px-3.5 text-[13px] transition ${
                                audioKind === key
                                    ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                                    : 'text-white/55 hover:text-white'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>
            )}

            <div className="mt-4">
                {shown.length ? (
                    <div
                        className={`grid gap-3 ${
                            audio
                                ? 'lg:grid-cols-2'
                                : section === 'video'
                                  ? 'sm:grid-cols-2'
                                  : 'sm:grid-cols-2 xl:grid-cols-3'
                        }`}
                    >
                        {shown.map((item) => {
                            const fresh = highlighted.includes(item.id);

                            return (
                                <div key={item.id} className="relative">
                                    {item.kind === 'video' ? (
                                        <VideoCard item={item} fresh={fresh} />
                                    ) : item.kind === 'audio' ? (
                                        <AudioCard item={item} fresh={fresh} />
                                    ) : (
                                        <Card
                                            item={item}
                                            fresh={fresh}
                                            onReuse={onReuse}
                                            onEdit={onEdit}
                                            onOpen={() =>
                                                item.url && setPreview(item)
                                            }
                                        />
                                    )}

                                    {fresh && (
                                        <span className="keep-light pointer-events-none absolute -top-2 left-3 z-10 flex h-5 items-center gap-1 rounded-full bg-sky-400 px-2 text-[11px] font-semibold text-slate-950 shadow-lg shadow-sky-500/30">
                                            <Sparkles className="h-3 w-3" strokeWidth={2.25} />
                                            New
                                        </span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="rounded-2xl border border-white/[0.07] bg-slate-950/40 px-6 py-14 text-center">
                        <look.icon
                            className="mx-auto h-7 w-7 text-white/20"
                            strokeWidth={1.5}
                        />
                        <p className="mt-3 text-[15px] text-white/60">
                            Nothing here yet
                        </p>
                        <p className="mt-1 text-sm text-white/35">{look.empty}</p>
                    </div>
                )}
            </div>

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
    const working = !item.url && !failed;

    const remove = () => {
        router.delete(`/studio/${item.id}`, {
            preserveScroll: true,
            async: true,
        });
    };

    const retry = () => {
        router.post(
            `/studio/${item.id}/retry`,
            {},
            { preserveScroll: true, async: true },
        );
    };

    return (
        <div
            className={`relative flex gap-3 rounded-2xl border bg-slate-950/55 p-3 transition ${
                working ? 'work-border border-white/[0.06]' : ''
            } ${
                fresh
                    ? 'border-sky-300/50 ring-1 ring-sky-300/25'
                    : working
                      ? ''
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

                        <span className="on-media absolute inset-0 flex items-center justify-center bg-black/55 opacity-0 transition group-hover:opacity-100">
                            <Maximize2
                                className="h-5 w-5 text-white"
                                strokeWidth={2}
                            />
                        </span>
                    </button>
                ) : failed ? (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-2 px-4 text-center">
                        <AlertCircle
                            className="h-5 w-5 text-rose-300/70"
                            strokeWidth={1.75}
                        />
                        <span className="text-[12px] text-white/45">
                            Could not create this one
                        </span>
                    </div>
                ) : (
                    <Working prompt={item.prompt} />
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

/**
 * Положение звёзд задано заранее, а не случайно при отрисовке: иначе
 * они прыгали бы с места на место при каждом обновлении ленты.
 */
const STARS = [
    { top: '16%', left: '20%', size: 3, delay: 0 },
    { top: '30%', left: '70%', size: 2, delay: 0.4 },
    { top: '52%', left: '12%', size: 2, delay: 0.9 },
    { top: '68%', left: '55%', size: 3, delay: 0.2 },
    { top: '24%', left: '46%', size: 2, delay: 1.3 },
    { top: '80%', left: '80%', size: 2, delay: 0.7 },
    { top: '44%', left: '88%', size: 3, delay: 1.1 },
    { top: '60%', left: '32%', size: 2, delay: 1.6 },
    { top: '36%', left: '34%', size: 2, delay: 2.0 },
];

/**
 * Работа в процессе.
 *
 * Полоса прогресса здесь не годится: провайдер не сообщает, как
 * далеко продвинулся. Мерцают звёзды и идёт счётчик времени — то же,
 * что у звука.
 */
function Working({ prompt }) {
    const [elapsed, setElapsed] = useState(0);

    useEffect(() => {
        const started = Date.now();
        const timer = setInterval(() => setElapsed(Date.now() - started), 500);

        return () => clearInterval(timer);
    }, []);

    return (
        <div className="relative flex h-full w-full flex-col items-center justify-center gap-2 overflow-hidden px-4 text-center">
            <span aria-hidden="true" className="pointer-events-none absolute inset-0">
                {STARS.map((star, index) => (
                    <span
                        key={index}
                        className="absolute rounded-full bg-sky-200"
                        style={{
                            top: star.top,
                            left: star.left,
                            width: `${star.size}px`,
                            height: `${star.size}px`,
                            boxShadow: '0 0 6px 1px rgb(125 211 252 / 0.8)',
                            animation: `work-twinkle 2.4s ease-in-out ${star.delay}s infinite`,
                        }}
                    />
                ))}
            </span>

            <Sparkles
                className="relative h-6 w-6 text-sky-300/70"
                strokeWidth={1.5}
                style={{ animation: 'work-pulse 1.8s ease-in-out infinite' }}
            />

            <span className="relative line-clamp-2 text-[12px] leading-snug text-white/55">
                {prompt}
            </span>

            <span className="relative text-[11px] tabular-nums text-white/35">
                {clock(elapsed / 1000)}
            </span>
        </div>
    );
}

/** Время словами. */
function clock(seconds) {
    if (!seconds || !Number.isFinite(seconds)) {
        return '0:00';
    }

    const whole = Math.floor(seconds);

    return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}`;
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
            className="on-media media-viewer fixed inset-0 z-[70] flex items-center justify-center bg-black/92 p-4 backdrop-blur-sm"
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
