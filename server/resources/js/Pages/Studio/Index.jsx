import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Image as ImageIcon, Wand2, Music, Video } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import GenerationForm from '@/Components/Studio/GenerationForm';
import EditForm from '@/Components/Studio/EditForm';
import SpeechForm from '@/Components/Studio/SpeechForm';
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
    defaultModel,
    defaultSpeechModel,
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

    // Исходник для правки: работа из ленты.
    const [source, setSource] = useState(null);

    // Открыт ли выбор из своих работ.
    const [browsing, setBrowsing] = useState(false);

    const finish = { preserveScroll: true, showProgress: false, onFinish: () => setBusy(false) };

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
            onSuccess: () => setSource(null),
        });
    };

    const submitSpeech = (values) => {
        setBusy(true);
        router.post('/studio/speech', values, finish);
    };

    const startEditing = (item) => {
        setSource(item);
        setTab('edit');
    };

    const shared = { available: studioReady, busy, limit };

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
                                source={source}
                                onPickSource={setSource}
                                onBrowse={() => setBrowsing(true)}
                                onSubmit={submitEdit}
                            />
                        )}

                        {tab === 'audio' && (
                            <SpeechForm
                                {...shared}
                                models={speechModels}
                                defaultModel={defaultSpeechModel}
                                onSubmit={submitSpeech}
                            />
                        )}

                        {tab === 'video' && <ComingSoon />}
                    </div>

                    {browsing && (
                        <AssetPicker
                            items={generations}
                            onPick={(item) => {
                                setSource(item);
                                setBrowsing(false);
                            }}
                            onClose={() => setBrowsing(false)}
                        />
                    )}

                    {tab !== 'video' && (
                        <GenerationGrid
                            items={generations}
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
