import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Image as ImageIcon, Video } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import GenerationForm from '@/Components/Studio/GenerationForm';
import GenerationGrid from '@/Components/Studio/GenerationGrid';

/**
 * Студия: создание изображений и лента работ.
 *
 * Видео появится позже, но переключатель стоит сразу: человек должен
 * понимать, что раздел шире одной задачи.
 */
export default function StudioIndex({
    generations,
    models,
    aspectRatios,
    styles,
    defaultModel,
    studioReady,
    limit,
}) {
    const [kind, setKind] = useState('image');
    const [busy, setBusy] = useState(false);

    // Повтор подставляет условия готовой работы в форму, а не создаёт
    // копию молча: человек чаще хочет что-то поменять.
    const [preset, setPreset] = useState(null);

    const submit = (values) => {
        setBusy(true);

        router.post('/studio', values, {
            preserveScroll: true,
            showProgress: false,
            onFinish: () => setBusy(false),
        });
    };

    return (
        <>
            <Head title="Studio — Uncensia" />

            <div className="flex-1 overflow-y-auto scrollbar-thin">
                <div className="mx-auto w-full max-w-[1100px] px-4 py-6 sm:px-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <h1 className="text-outline text-2xl font-light tracking-tight text-white">
                            Studio
                        </h1>

                        <KindSwitch value={kind} onChange={setKind} />
                    </div>

                    {kind === 'image' ? (
                        <>
                            <div className="mt-6">
                                <GenerationForm
                                    models={models}
                                    aspectRatios={aspectRatios}
                                    styles={styles}
                                    defaultModel={defaultModel}
                                    available={studioReady}
                                    busy={busy}
                                    limit={limit}
                                    preset={preset}
                                    onSubmit={submit}
                                />
                            </div>

                            <GenerationGrid
                                items={generations}
                                onReuse={setPreset}
                            />
                        </>
                    ) : (
                        <ComingSoon />
                    )}
                </div>
            </div>
        </>
    );
}

/** Переключатель «Изображение / Видео». */
function KindSwitch({ value, onChange }) {
    const options = [
        { key: 'image', label: 'Image', icon: ImageIcon },
        { key: 'video', label: 'Video', icon: Video },
    ];

    return (
        <div className="flex items-center gap-1 rounded-full border border-white/[0.12] bg-slate-950/50 p-1 backdrop-blur-xl">
            {options.map(({ key, label, icon: Icon }) => (
                <button
                    key={key}
                    type="button"
                    onClick={() => onChange(key)}
                    className={`flex h-9 items-center gap-2 rounded-full px-4 text-sm transition ${
                        value === key
                            ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                            : 'text-white/55 hover:text-white'
                    }`}
                >
                    <Icon className="h-4 w-4" strokeWidth={1.75} />
                    {label}
                </button>
            ))}
        </div>
    );
}

function ComingSoon() {
    return (
        <div className="mt-6 rounded-2xl border border-white/[0.08] bg-slate-950/55 px-6 py-16 text-center backdrop-blur-xl">
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
