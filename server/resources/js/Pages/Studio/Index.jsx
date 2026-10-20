import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Image as ImageIcon, Wand2, Music, Video, Images, PenLine } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import GenerationForm from '@/Components/Studio/GenerationForm';
import EditForm from '@/Components/Studio/EditForm';
import AudioStudio from '@/Components/Studio/AudioStudio';
import VideoStudio from '@/Components/Studio/VideoStudio';
import Gallery from '@/Components/Studio/Gallery';
import WorkQueue from '@/Components/Studio/WorkQueue';
import AssetPicker from '@/Components/Studio/AssetPicker';
import Switch from '@/Components/Studio/Switch';
import Ready from '@/Components/Studio/Ready';
import { sectionOf } from '@/Components/Studio/sections';
import useStudioRequests from '@/Components/Studio/useStudioRequests';
import useFreshWorks from '@/Components/Studio/useFreshWorks';

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
    compose,
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
     * С чем открыть Студию, если пришли из чата или ленты: раздел,
     * режим, описание и картинка.
     *
     * Только один раз. Адрес страницы хранит эти данные, и сервер
     * присылает их заново с каждым ответом — в том числе на опрос
     * готовности. Без этой отметки страница каждые несколько секунд
     * сбрасывала бы форму и раздел.
     */
    const [incoming, setIncoming] = useState(null);
    const composed = useRef(null);

    useEffect(() => {
        if (!compose) {
            return;
        }

        const key = JSON.stringify([
            compose.tab,
            compose.mode,
            compose.prompt,
            compose.work?.id,
            compose.attachment?.url,
        ]);

        if (composed.current === key) {
            return;
        }

        composed.current = key;

        setView('editor');
        setTab(compose.tab);

        if (compose.tab === 'audio') {
            setAudioMode(['music', 'speech', 'effect'].includes(compose.mode) ? compose.mode : 'music');
        }

        if (compose.tab === 'image') {
            setPreset({ prompt: compose.prompt });

            return;
        }

        if (compose.work) {
            if (compose.tab === 'video') {
                setPicture(compose.work);
            } else {
                setSources([compose.work]);
            }
        }

        const deliver = (files = []) =>
            setIncoming({ key, prompt: compose.prompt, mode: compose.mode, files });

        // Фото из чата приходит ссылкой: забираем его и кладём в форму
        // как обычный загруженный файл.
        if (compose.attachment) {
            fetch(compose.attachment.url, { credentials: 'same-origin' })
                .then((response) => response.blob())
                .then((blob) =>
                    deliver([new File([blob], compose.attachment.name, { type: compose.attachment.mime })]),
                )
                .catch(() => deliver());
        } else {
            deliver();
        }
    }, [compose]);

    const { send, pending, busy, refusal } = useStudioRequests();

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

    const { unseen, highlighted, unseenIn } = useFreshWorks(generations, view, tab);

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
                                    incoming={incoming}
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
                                    incoming={incoming}
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
                                    incoming={incoming}
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

StudioIndex.layout = (page) => <MainLayout>{page}</MainLayout>;
