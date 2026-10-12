import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Image as ImageIcon, Wand2, Music, Video } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import GenerationForm from '@/Components/Studio/GenerationForm';
import EditForm from '@/Components/Studio/EditForm';
import AudioStudio from '@/Components/Studio/AudioStudio';
import VideoStudio from '@/Components/Studio/VideoStudio';
import GenerationGrid from '@/Components/Studio/GenerationGrid';
import AssetPicker from '@/Components/Studio/AssetPicker';

/** Вкладки раздела. */
const TABS = [
    { key: 'image', label: 'Image', icon: ImageIcon },
    { key: 'edit', label: 'Edit', icon: Wand2 },
    { key: 'audio', label: 'Audio', icon: Music },
    { key: 'video', label: 'Video', icon: Video },
];

/**
 * Студия: создание изображений, правка, звук, видео и галерея работ.
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
    const [tab, setTab] = useState('image');

    // Режим звука: у каждого своя галерея, смешивать их незачем.
    const [audioMode, setAudioMode] = useState('music');

    // Работа, принесённая из общей ленты: открываем правку с ней.
    useEffect(() => {
        if (!fromFeed) {
            return;
        }

        setSources([fromFeed]);
        setTab('edit');
    }, [fromFeed]);
    const [busy, setBusy] = useState(false);

    // Повтор подставляет условия готовой работы в форму, а не создаёт
    // копию молча: человек чаще хочет что-то поменять.
    const [preset, setPreset] = useState(null);

    // Исходники для правки: объединение берёт несколько работ.
    const [sources, setSources] = useState([]);

    // Открыт ли выбор из своих работ.
    const [browsing, setBrowsing] = useState(false);

    // Картинка из галереи, которую оживляет видео.
    const [picture, setPicture] = useState(null);
    const [pickingPicture, setPickingPicture] = useState(false);

    // Свежая работа: её видно в галерее сразу после создания.
    const [fresh, setFresh] = useState(null);

    /*
     * Работы, которых ещё нет в базе.
     *
     * Картинка рисуется за несколько секунд, и всё это время запрос
     * висит. Без заглушки экран просто замирает, и непонятно, идёт
     * ли вообще работа.
     */
    const [pending, setPending] = useState([]);

    const startPending = (count, prompt, kind = 'image') =>
        setPending(
            Array.from({ length: count }, (_, index) => ({
                id: `pending-${Date.now()}-${index}`,
                kind,
                status: 'pending',
                prompt,
                url: null,
                thumbnail: null,
            })),
        );

    const finish = {
        preserveScroll: true,
        showProgress: false,
        onFinish: () => {
            setBusy(false);
            setPending([]);
        },
        // Самая свежая работа подсвечивается в галерее: иначе
        // непонятно, что именно получилось.
        onSuccess: (page) => setFresh(page.props.generations?.[0]?.id ?? null),
    };

    const submitImage = (values) => {
        setBusy(true);
        startPending(values.variants ?? 1, values.prompt);
        router.post('/studio', values, finish);
    };

    const submitEdit = (values) => {
        setBusy(true);
        startPending(1, values.prompt ?? 'Working on your image');

        // Файлы уходят как форма, иначе вложения не доедут.
        router.post('/studio/edit', values, {
            ...finish,
            forceFormData: true,
            onSuccess: (page) => {
                setSources([]);
                setFresh(page.props.generations?.[0]?.id ?? null);
            },
        });
    };

    /**
     * Отправка звука.
     *
     * Смена голоса уходит файлом, остальное обычным запросом.
     */
    const submitAudio = (kind, values) => {
        setBusy(true);

        router.post(`/studio/${kind}`, values, {
            ...finish,
            forceFormData: kind === 'voice-change',
        });
    };

    /**
     * Отправка видео.
     *
     * Оживление уходит формой: картинка может быть файлом с диска.
     */
    const submitVideo = (kind, values) => {
        setBusy(true);

        router.post(`/studio/${kind}`, values, {
            ...finish,
            forceFormData: kind === 'animate',
            onSuccess: (page) => {
                setPicture(null);
                setFresh(page.props.generations?.[0]?.id ?? null);
            },
        });
    };

    const startEditing = (item) => {
        setSources([item]);
        setTab('edit');
    };

    const shared = { available: studioReady, busy, limit };

    // Музыка, эффекты и ролики считаются у провайдера: пока есть
    // незаконченные работы, спрашиваем готовность, и результат
    // появляется сам.
    const waiting = generations.some((item) => item.status === 'queued');

    useEffect(() => {
        if (!waiting) {
            return;
        }

        const timer = setInterval(() => {
            router.post(
                '/studio/collect',
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    showProgress: false,
                    // Готовую работу подсвечиваем: человек мог уйти с
                    // вкладки и вернуться к полной галерее.
                    onSuccess: (page) => {
                        const done = (page.props.generations ?? []).find(
                            (item) =>
                                item.kind !== 'image' &&
                                item.status === 'ready',
                        );

                        if (done) {
                            setFresh(done.id);
                        }
                    },
                },
            );
        }, 5000);

        return () => clearInterval(timer);
    }, [waiting]);

    // Какой операции работы показывать в галерее звука.
    const AUDIO_OPERATIONS = {
        music: 'music',
        speech: 'speech',
        changer: 'voice_change',
        effect: 'effect',
    };

    /*
     * Галерея своя у каждого режима: музыка, озвучка, смена голоса и
     * эффекты — разный материал, и человек пришёл за чем-то одним.
     * Картинки и правка работают с общим набором, поэтому у них
     * галерея общая.
     */
    const belongs = (item) => {
        if (tab === 'audio') {
            return (
                item.kind === 'audio' &&
                item.operation === AUDIO_OPERATIONS[audioMode]
            );
        }

        return tab === 'video' ? item.kind === 'video' : item.kind === 'image';
    };

    const shown = [
        // Заглушки сверху: человек только что нажал кнопку.
        ...pending.filter((item) => tab !== 'audio' && tab !== 'video'),
        ...generations.filter(belongs),
    ];

    return (
        <>
            <Head title="Studio — Uncensia" />

            <div className="flex-1 overflow-y-auto scrollbar-thin">
                <div className="mx-auto w-full max-w-[1100px] px-4 py-6 sm:px-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <h1 className="text-2xl font-light tracking-tight text-white">
                            Studio
                        </h1>

                        <TabSwitch value={tab} onChange={setTab} />
                    </div>

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
                    </div>

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

                    {browsing && (
                        <AssetPicker
                            items={generations}
                            onPick={(items) => {
                                // Выбранное добавляется к уже
                                // отмеченному, а не заменяет его.
                                setSources((previous) => {
                                    const known = new Set(
                                        previous.map((item) => item.id),
                                    );

                                    return [
                                        ...previous,
                                        ...items.filter(
                                            (item) => !known.has(item.id),
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

                    <GenerationGrid
                        items={shown}
                        kind={
                            tab === 'audio'
                                ? audioMode
                                : tab === 'video'
                                  ? 'video'
                                  : 'image'
                        }
                        highlight={fresh}
                        onReuse={(item) => {
                            setPreset(item);
                            setTab('image');
                        }}
                        onEdit={startEditing}
                    />
                </div>
            </div>
        </>
    );
}

/** Переключатель вкладок. */
function TabSwitch({ value, onChange }) {
    return (
        <div className="flex items-center gap-1 rounded-full border border-white/[0.12] bg-slate-950/50 p-1 backdrop-blur-xl">
            {TABS.map(({ key, label, icon: Icon }) => (
                <button
                    key={key}
                    type="button"
                    onClick={() => onChange(key)}
                    title={label}
                    className={`flex h-9 items-center gap-2 rounded-full px-4 text-sm transition ${
                        value === key
                            ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                            : 'text-white/55 hover:text-white'
                    }`}
                >
                    <Icon className="h-4 w-4" strokeWidth={1.75} />
                    <span className="hidden sm:inline">{label}</span>
                </button>
            ))}
        </div>
    );
}

StudioIndex.layout = (page) => <MainLayout>{page}</MainLayout>;
