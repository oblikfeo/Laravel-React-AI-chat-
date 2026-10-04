import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Image as ImageIcon, Wand2, Music, Video } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import GenerationForm from '@/Components/Studio/GenerationForm';
import EditForm from '@/Components/Studio/EditForm';
import AudioStudio from '@/Components/Studio/AudioStudio';
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
 * Студия: создание изображений, правка, озвучка и лента работ.
 *
 * Видео пока заглушка, но вкладка стоит сразу: человек должен
 * понимать, что раздел шире одной задачи.
 */
export default function StudioIndex({
    generations,
    models,
    aspectRatios,
    styles,
    speechModels,
    musicModels,
    effectModels,
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
}) {
    const [tab, setTab] = useState('image');
    const [busy, setBusy] = useState(false);

    // Повтор подставляет условия готовой работы в форму, а не создаёт
    // копию молча: человек чаще хочет что-то поменять.
    const [preset, setPreset] = useState(null);

    // Исходники для правки: объединение берёт несколько работ.
    const [sources, setSources] = useState([]);

    // Открыт ли выбор из своих работ.
    const [browsing, setBrowsing] = useState(false);

    // Свежая работа: её видно в галерее сразу после создания.
    const [fresh, setFresh] = useState(null);

    const finish = {
        preserveScroll: true,
        showProgress: false,
        onFinish: () => setBusy(false),
        // Самая свежая работа подсвечивается в галерее: иначе
        // непонятно, что именно получилось.
        onSuccess: (page) => setFresh(page.props.generations?.[0]?.id ?? null),
    };

    const submitImage = (values) => {
        setBusy(true);
        router.post('/studio', values, finish);
    };

    const submitEdit = (values) => {
        setBusy(true);

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

    const startEditing = (item) => {
        setSources([item]);
        setTab('edit');
    };

    const shared = { available: studioReady, busy, limit };

    // Музыка и эффекты считаются у провайдера: пока есть незаконченные
    // работы, спрашиваем готовность, и результат появляется сам.
    const waiting = generations.some((item) => item.status === 'queued');

    useEffect(() => {
        if (!waiting) {
            return;
        }

        const timer = setInterval(() => {
            router.post(
                '/studio/collect',
                {},
                { preserveScroll: true, preserveState: true, showProgress: false },
            );
        }, 5000);

        return () => clearInterval(timer);
    }, [waiting]);

    // На каждой вкладке своя галерея: записи и картинки смешивать
    // незачем, человек пришёл за чем-то одним.
    const shown = generations.filter((item) =>
        tab === 'audio' ? item.kind === 'audio' : item.kind !== 'audio',
    );

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
                                onSubmit={submitAudio}
                            />
                        )}

                        {tab === 'video' && <ComingSoon />}
                    </div>

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

                    {tab !== 'video' && (
                        <GenerationGrid
                            items={shown}
                            kind={tab === 'audio' ? 'audio' : 'image'}
                            highlight={fresh}
                            onReuse={(item) => {
                                setPreset(item);
                                setTab('image');
                            }}
                            onEdit={startEditing}
                        />
                    )}
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

function ComingSoon() {
    return (
        <div className="rounded-2xl border border-white/[0.08] bg-slate-950/55 px-6 py-16 text-center backdrop-blur-xl">
            <Video
                className="mx-auto h-8 w-8 text-white/25"
                strokeWidth={1.5}
            />
            <p className="mt-4 text-[15px] font-medium text-white">
                Video is coming soon
            </p>
            <p className="mt-1.5 text-sm text-white/45">
                Generate clips from text or bring a still image to life.
            </p>
        </div>
    );
}

StudioIndex.layout = (page) => <MainLayout>{page}</MainLayout>;
