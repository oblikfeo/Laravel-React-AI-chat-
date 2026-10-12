import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Image as ImageIcon,
    Wand2,
    Music,
    Video,
    Images,
    PenLine,
    ArrowRight,
    Sparkles,
} from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import GenerationForm from '@/Components/Studio/GenerationForm';
import EditForm from '@/Components/Studio/EditForm';
import AudioStudio from '@/Components/Studio/AudioStudio';
import VideoStudio from '@/Components/Studio/VideoStudio';
import Gallery from '@/Components/Studio/GenerationGrid';
import WorkQueue from '@/Components/Studio/WorkQueue';
import AssetPicker from '@/Components/Studio/AssetPicker';

/** Режимы Студии: создавать или смотреть готовое. */
const VIEWS = [
    { key: 'gallery', label: 'Gallery', icon: Images },
    { key: 'editor', label: 'Editor', icon: PenLine },
];

/** Разделы. Одни и те же у редактора и у галереи. */
const TABS = [
    { key: 'image', label: 'Image', icon: ImageIcon },
    { key: 'edit', label: 'Edit', icon: Wand2 },
    { key: 'audio', label: 'Audio', icon: Music },
    { key: 'video', label: 'Video', icon: Video },
];

/** Как назвать готовое в каждом разделе. */
const NOUNS = {
    image: ['image', 'images'],
    edit: ['edit', 'edits'],
    audio: ['track', 'tracks'],
    video: ['video', 'videos'],
};

/**
 * К какому разделу относится работа.
 *
 * Правило одно на всю страницу: по нему работа попадает и в очередь
 * своего раздела, и в его галерею. Раньше вкладки делили работы каждая
 * по-своему, и чужое всплывало не там.
 */
function sectionOf(item) {
    if (item.kind === 'audio') {
        return 'audio';
    }

    if (item.kind === 'video') {
        return 'video';
    }

    return !item.operation || item.operation === 'generate' ? 'image' : 'edit';
}

/** Где человек был в Студии: переживает перезагрузку страницы. */
function remembered(key, allowed, fallback) {
    try {
        const value = window.sessionStorage.getItem(`studio.${key}`);

        return allowed.includes(value) ? value : fallback;
    } catch {
        return fallback;
    }
}

function remember(key, value) {
    try {
        window.sessionStorage.setItem(`studio.${key}`, value);
    } catch {
        // Хранилище закрыто — просто не запоминаем.
    }
}

/**
 * Студия.
 *
 * Два режима: редактор и галерея. В редакторе — форма раздела и под
 * ней очередь того, что сейчас создаётся. Готовое уходит в галерею:
 * у каждого раздела она своя, и выбирается тем же переключателем
 * разделов.
 */
export default function StudioIndex({
    generations,
    models,
    aspectRatios,
    styles,
    speechModels,
    musicModels,
    effectModels,
    videoModels,
    defaultVideoModel,
    editPrices,
    video,
    voices,
    defaultModel,
    defaultSpeechModel,
    defaultMusicModel,
    defaultEffectModel,
    defaultVoice,
    maxLyrics,
    maxVariants,
    maxSeed,
    upscaleScales,
    maxCombine,
    studioReady,
    limit,
    fromFeed,
}) {
    const [view, setView] = useState(() =>
        remembered('view', ['gallery', 'editor'], 'editor'),
    );
    const [tab, setTab] = useState(() =>
        remembered('tab', TABS.map((item) => item.key), 'image'),
    );

    // Режим звука в редакторе: музыка, озвучка, смена голоса, эффект.
    const [audioMode, setAudioMode] = useState('music');

    useEffect(() => remember('view', view), [view]);
    useEffect(() => remember('tab', tab), [tab]);

    // Повтор подставляет условия готовой работы в форму, а не создаёт
    // копию молча: человек чаще хочет что-то поменять.
    const [preset, setPreset] = useState(null);

    // Исходники для правки: объединение берёт несколько работ.
    const [sources, setSources] = useState([]);
    const [browsing, setBrowsing] = useState(false);

    // Картинка из галереи, которую оживляет видео.
    const [picture, setPicture] = useState(null);
    const [pickingPicture, setPickingPicture] = useState(false);

    /*
     * Работа, принесённая из общей ленты: открываем правку с ней.
     *
     * Только один раз. Адрес страницы хранит номер работы, и сервер
     * присылает её заново с каждым ответом — в том числе на опрос
     * готовности. Без этой отметки человека каждые несколько секунд
     * перебрасывало обратно в правку, чем бы он ни занимался.
     */
    const broughtFromFeed = useRef(null);

    useEffect(() => {
        if (!fromFeed || broughtFromFeed.current === fromFeed.id) {
            return;
        }

        broughtFromFeed.current = fromFeed.id;

        setSources([fromFeed]);
        setView('editor');
        setTab('edit');
    }, [fromFeed]);

    // Отказ сервера принять запрос: слишком большой файл и подобное.
    const [refusal, setRefusal] = useState(null);

    /*
     * Запросы, на которые сервер ещё не ответил.
     *
     * Картинка рисуется несколько секунд, и всё это время её нет в
     * базе. Заглушка появляется в очереди сразу по нажатию: иначе
     * экран замирает, и непонятно, принят ли запрос.
     */
    const [pending, setPending] = useState([]);
    const counter = useRef(0);

    // Защита от двойного нажатия: каждая работа стоит денег.
    const [busy, setBusy] = useState(false);

    /**
     * Отправляет запрос на создание.
     *
     * Запросы идут параллельно: пока рисуется одна картинка, можно
     * запустить следующую или уйти в другой раздел. Обычный переход
     * Inertia отменял предыдущий запрос — отсюда были «прыжки» и
     * пропавшие результаты.
     */
    const send = (url, values, { section, prompt, count = 1, form = false }) => {
        const ids = Array.from(
            { length: count },
            () => `local-${(counter.current += 1)}`,
        );

        setPending((previous) => [
            ...ids.map((id) => ({
                id,
                section,
                status: 'pending',
                prompt,
                createdAt: new Date().toISOString(),
            })),
            ...previous,
        ]);

        setBusy(true);
        setTimeout(() => setBusy(false), 900);
        setRefusal(null);

        router.post(url, values, {
            async: true,
            preserveScroll: true,
            preserveState: true,
            forceFormData: form,
            // Запрос не принят: без сообщения заглушка в очереди
            // просто исчезла бы, и человек не понял бы почему.
            onError: (errors) =>
                setRefusal(
                    Object.values(errors)[0] ?? 'This request was not accepted.',
                ),
            onFinish: () =>
                setPending((previous) =>
                    previous.filter((item) => !ids.includes(item.id)),
                ),
        });
    };

    const submitImage = (values) =>
        send('/studio', values, {
            section: 'image',
            prompt: values.prompt,
            count: values.variants ?? 1,
        });

    const submitEdit = (values) => {
        send('/studio/edit', values, {
            section: 'edit',
            prompt: values.prompt ?? 'Working on your image',
            // Файлы уходят как форма, иначе вложения не доедут.
            form: true,
        });

        // Выбранные картинки убираем сразу, а не когда работа
        // досчитается: иначе форма выглядит неотправленной.
        setSources([]);
    };

    const submitAudio = (kind, values) =>
        send(`/studio/${kind}`, values, {
            section: 'audio',
            prompt: values.prompt ?? values.text ?? 'Voice change',
            form: kind === 'voice-change',
        });

    const submitVideo = (kind, values) => {
        send(`/studio/${kind}`, values, {
            section: 'video',
            prompt: values.prompt || 'Animating your image',
            form: kind === 'animate',
        });

        setPicture(null);
    };

    // Музыка, эффекты и ролики считаются у провайдера: пока есть
    // незаконченные, спрашиваем готовность, и результат появляется сам.
    const waiting = generations.some((item) => item.status === 'queued');

    useEffect(() => {
        if (!waiting) {
            return;
        }

        const timer = setInterval(() => {
            router.post(
                '/studio/collect',
                {},
                { async: true, preserveScroll: true, preserveState: true },
            );
        }, 5000);

        return () => clearInterval(timer);
    }, [waiting]);

    /*
     * Готовое, которого человек ещё не видел.
     *
     * Работа сюда попадает в тот момент, когда стала готовой у нас на
     * глазах: была в очереди — и вот готова, либо её не было вовсе.
     * По этому списку подсвечиваются переключатели и сами работы в
     * галерее.
     */
    const [unseen, setUnseen] = useState([]);
    const known = useRef(null);

    useEffect(() => {
        const before = known.current;

        known.current = {
            statuses: new Map(generations.map((item) => [item.id, item.status])),
            newest: Math.max(0, ...generations.map((item) => item.id)),
        };

        if (!before) {
            return;
        }

        const fresh = generations
            .filter((item) => {
                if (item.status !== 'ready') {
                    return false;
                }

                const was = before.statuses.get(item.id);

                // Работа старше известных — не новая: она просто
                // вошла в список на место удалённой.
                return was ? was !== 'ready' : item.id > before.newest;
            })
            .map((item) => item.id);

        if (fresh.length) {
            setUnseen((previous) => [...new Set([...previous, ...fresh])]);
        }
    }, [generations]);

    // Подсвеченные в открытой галерее: увиденное перестаёт считаться
    // новым, но подсветка держится, пока человек не уйдёт.
    const [highlighted, setHighlighted] = useState([]);

    const unseenIn = (section) =>
        generations.filter(
            (item) => unseen.includes(item.id) && sectionOf(item) === section,
        );

    useEffect(() => {
        if (view !== 'gallery') {
            setHighlighted([]);

            return;
        }

        const ids = unseenIn(tab).map((item) => item.id);

        setHighlighted(ids);

        if (ids.length) {
            setUnseen((previous) => previous.filter((id) => !ids.includes(id)));
        }
        // Пересчитываем только при смене режима и раздела: работа,
        // пришедшая в открытую галерею, подсветится эффектом ниже.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [view, tab]);

    // Работа, пришедшая прямо в открытую галерею своего раздела.
    useEffect(() => {
        if (view !== 'gallery') {
            return;
        }

        const ids = unseenIn(tab).map((item) => item.id);

        if (ids.length) {
            setHighlighted((previous) => [...new Set([...previous, ...ids])]);
            setUnseen((previous) => previous.filter((id) => !ids.includes(id)));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [unseen]);

    const openGallery = (section) => {
        setTab(section);
        setView('gallery');
    };

    // Очередь раздела: то, чего ждём, и то, что не получилось.
    const queue = [
        ...pending.filter((item) => item.section === tab),
        ...generations.filter(
            (item) => item.status !== 'ready' && sectionOf(item) === tab,
        ),
    ];

    const finished = generations.filter(
        (item) => item.status === 'ready' && sectionOf(item) === tab,
    );

    // Всё, что сейчас считается, по всем разделам.
    const inProgress = [
        ...pending,
        ...generations
            .filter((item) => item.status === 'queued' || item.status === 'pending')
            .map((item) => ({ section: sectionOf(item) })),
    ];

    const shared = { available: studioReady, busy, limit };
    const freshHere = unseenIn(tab).length;

    return (
        <>
            <Head title="Studio — Uncensia" />

            <div className="flex-1 overflow-y-auto scrollbar-thin">
                <div className="mx-auto w-full max-w-[1100px] px-4 py-6 sm:px-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <h1 className="pt-1.5 text-2xl font-light tracking-tight text-white">
                            Studio
                        </h1>

                        <div className="flex flex-col items-end gap-2">
                            <Switch
                                items={VIEWS}
                                value={view}
                                onChange={setView}
                                // Готовое ждёт в галерее: зовём туда.
                                badges={{ gallery: unseen.length }}
                                // Повтор из галереи ставит работу в
                                // очередь редактора: её там видно.
                                working={{ editor: inProgress.length > 0 }}
                            />

                            <Switch
                                items={TABS}
                                value={tab}
                                onChange={setTab}
                                compact
                                badges={Object.fromEntries(
                                    TABS.map((item) => [
                                        item.key,
                                        unseenIn(item.key).length,
                                    ]),
                                )}
                                working={Object.fromEntries(
                                    TABS.map((item) => [
                                        item.key,
                                        inProgress.some(
                                            (work) => work.section === item.key,
                                        ),
                                    ]),
                                )}
                            />
                        </div>
                    </div>

                    {view === 'editor' ? (
                        <div className="mt-6">
                            {tab === 'image' && (
                                <GenerationForm
                                    {...shared}
                                    models={models}
                                    aspectRatios={aspectRatios}
                                    styles={styles}
                                    defaultModel={defaultModel}
                                    maxVariants={maxVariants}
                                    maxSeed={maxSeed}
                                    preset={preset}
                                    onSubmit={submitImage}
                                />
                            )}

                            {tab === 'edit' && (
                                <EditForm
                                    {...shared}
                                    aspectRatios={aspectRatios}
                                    prices={editPrices}
                                    upscaleScales={upscaleScales}
                                    maxCombine={maxCombine}
                                    sources={sources}
                                    onRemoveSource={(id) =>
                                        setSources((previous) =>
                                            previous.filter((item) => item.id !== id),
                                        )
                                    }
                                    onBrowse={() => setBrowsing(true)}
                                    onSubmit={submitEdit}
                                />
                            )}

                            {tab === 'audio' && (
                                <AudioStudio
                                    {...shared}
                                    speechModels={speechModels}
                                    musicModels={musicModels}
                                    effectModels={effectModels}
                                    voices={voices}
                                    defaultSpeechModel={defaultSpeechModel}
                                    defaultMusicModel={defaultMusicModel}
                                    defaultEffectModel={defaultEffectModel}
                                    defaultVoice={defaultVoice}
                                    maxLyrics={maxLyrics}
                                    mode={audioMode}
                                    onModeChange={setAudioMode}
                                    onSubmit={submitAudio}
                                />
                            )}

                            {tab === 'video' && (
                                <VideoStudio
                                    models={videoModels}
                                    defaultModel={defaultVideoModel}
                                    access={video.access}
                                    limit={video.limit}
                                    available={studioReady}
                                    busy={busy}
                                    picture={picture}
                                    onClearPicture={() => setPicture(null)}
                                    onBrowse={() => setPickingPicture(true)}
                                    onSubmit={submitVideo}
                                />
                            )}

                            {refusal && (
                                <p className="mt-4 rounded-xl border border-rose-400/25 bg-rose-950/60 px-4 py-2.5 text-[13px] text-rose-100">
                                    {refusal}
                                </p>
                            )}

                            <WorkQueue items={queue} section={tab} />

                            {freshHere > 0 && (
                                <Ready
                                    count={freshHere}
                                    nouns={NOUNS[tab]}
                                    onOpen={() => openGallery(tab)}
                                />
                            )}
                        </div>
                    ) : (
                        <Gallery
                            section={tab}
                            items={finished}
                            highlighted={highlighted}
                            onCreate={() => setView('editor')}
                            onReuse={(item) => {
                                setPreset(item);
                                setTab('image');
                                setView('editor');
                            }}
                            onEdit={(item) => {
                                setSources([item]);
                                setTab('edit');
                                setView('editor');
                            }}
                        />
                    )}

                    {browsing && (
                        <AssetPicker
                            items={generations}
                            onPick={(items) => {
                                // Выбранное добавляется к уже
                                // отмеченному, а не заменяет его.
                                setSources((previous) => {
                                    const taken = new Set(
                                        previous.map((item) => item.id),
                                    );

                                    return [
                                        ...previous,
                                        ...items.filter(
                                            (item) => !taken.has(item.id),
                                        ),
                                    ].slice(0, maxCombine);
                                });
                                setBrowsing(false);
                            }}
                            multiple={maxCombine > 1}
                            chosenIds={sources.map((item) => item.id)}
                            onClose={() => setBrowsing(false)}
                        />
                    )}

                    {pickingPicture && (
                        <AssetPicker
                            items={generations}
                            hint="Pick the picture you want to animate"
                            onPick={(items) => {
                                setPicture(items[0] ?? null);
                                setPickingPicture(false);
                            }}
                            onClose={() => setPickingPicture(false)}
                        />
                    )}
                </div>
            </div>
        </>
    );
}

/**
 * Переключатель: режимы сверху, разделы под ними.
 *
 * Голубая точка на пункте значит, что там ждёт готовая работа,
 * которую человек ещё не открывал. Вращающееся кольцо — что там
 * сейчас что-то создаётся.
 */
function Switch({ items, value, onChange, badges = {}, working = {}, compact = false }) {
    return (
        <div className="flex items-center gap-1 rounded-full border border-white/[0.12] bg-slate-950/50 p-1 backdrop-blur-xl">
            {items.map(({ key, label, icon: Icon }) => {
                const fresh = badges[key] > 0;

                return (
                    <button
                        key={key}
                        type="button"
                        onClick={() => onChange(key)}
                        title={label}
                        className={`relative flex h-9 items-center gap-2 rounded-full px-4 text-sm transition ${
                            value === key
                                ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                                : fresh
                                  ? 'text-sky-200 hover:text-white'
                                  : 'text-white/55 hover:text-white'
                        }`}
                    >
                        <Icon className="h-4 w-4" strokeWidth={1.75} />

                        <span className={compact ? 'hidden sm:inline' : ''}>
                            {label}
                        </span>

                        {fresh && !compact && (
                            <span className="keep-light flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-sky-400 px-1 text-[11px] font-semibold text-slate-950">
                                {badges[key]}
                            </span>
                        )}

                        {!fresh && working[key] && (
                            <span className="absolute right-1 top-1 h-2 w-2 animate-spin rounded-full border border-sky-300/80 border-t-transparent" />
                        )}

                        {fresh && (
                            <span className="absolute right-1 top-1 flex h-2 w-2">
                                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75" />
                                <span className="relative inline-flex h-2 w-2 rounded-full bg-sky-400" />
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}

/**
 * Готово: приглашение в галерею.
 *
 * Появляется под формой, когда работа раздела досчиталась. Сама
 * работа живёт в галерее, а не под формой.
 */
function Ready({ count, nouns, onOpen }) {
    return (
        <button
            type="button"
            onClick={onOpen}
            className="mt-4 flex w-full animate-[message-in_0.3s_ease-out] items-center gap-3 rounded-2xl border border-sky-300/40 bg-sky-400/[0.08] px-4 py-3 text-left ring-1 ring-sky-300/20 transition hover:bg-sky-400/[0.14]"
        >
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-400/20 text-sky-200">
                <Sparkles className="h-[18px] w-[18px]" strokeWidth={1.75} />
            </span>

            <span className="min-w-0 flex-1">
                <span className="block text-[14px] font-medium text-white">
                    {count === 1
                        ? `Your ${nouns[0]} is ready`
                        : `${count} new ${nouns[1]} are ready`}
                </span>
                <span className="mt-0.5 block text-[12px] text-white/50">
                    Saved to your gallery
                </span>
            </span>

            <span className="flex shrink-0 items-center gap-1.5 text-[13px] font-medium text-sky-200">
                Open gallery
                <ArrowRight className="h-4 w-4" strokeWidth={2} />
            </span>
        </button>
    );
}

StudioIndex.layout = (page) => <MainLayout>{page}</MainLayout>;
