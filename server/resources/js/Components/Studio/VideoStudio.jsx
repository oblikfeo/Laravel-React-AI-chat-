import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import {
    Clapperboard,
    ImagePlay,
    Clock,
    Crop,
    Cpu,
    MonitorPlay,
    Upload,
    Images,
    Lock,
    Video,
    X,
} from 'lucide-react';
import { Panel, NotReady, SubmitBar } from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';
import ModeTiles from '@/Components/Studio/ModeTiles';
import { usePlans } from '@/Contexts/PlansContext';

/**
 * Режимы видео.
 *
 * Стоят крупными плитками, как режимы звука и инструменты правки.
 */
const MODES = [
    {
        key: 'text',
        label: 'Text to Video',
        hint: 'A clip from your description',
        icon: Clapperboard,
    },
    {
        key: 'image',
        label: 'Animate Image',
        hint: 'Bring a still picture to life',
        icon: ImagePlay,
    },
];

/** Как подписать разрешение: цифры говорят не всем. */
const RESOLUTIONS = {
    '480p': 'Standard, renders faster',
    '720p': 'HD, sharper picture',
    '1080p': 'Full HD, the most detail',
};

const RATIOS = {
    '16:9': ['Landscape', 'Widescreen, for desktop'],
    '9:16': ['Vertical', 'Stories and reels'],
    '1:1': ['Square', 'Feed posts'],
};

/**
 * Видео: ролик по описанию и оживление картинки.
 *
 * Раздел есть только на платных тарифах. Остальным он виден целиком,
 * но закрыт: человек должен понимать, что получит.
 */
export default function VideoStudio({
    models,
    defaultModel,
    access,
    limit,
    available,
    busy,
    picture,
    onClearPicture,
    onBrowse,
    incoming = null,
    onSubmit,
}) {
    const [mode, setMode] = useState('text');
    const [prompt, setPrompt] = useState('');
    const [model, setModel] = useState(defaultModel);
    const [duration, setDuration] = useState(null);
    const [resolution, setResolution] = useState(null);
    const [ratio, setRatio] = useState(null);
    const [file, setFile] = useState(null);

    // Просьба, принесённая из чата или ленты: заполняем форму.
    useEffect(() => {
        if (!incoming) {
            return;
        }

        setMode(incoming.mode === 'animate' || incoming.files?.length ? 'image' : 'text');
        setPrompt(incoming.prompt ?? '');

        if (incoming.files?.length) {
            setFile(incoming.files[0]);
        }
    }, [incoming]);
    const fileRef = useRef(null);

    const current = models.find((item) => item.key === model) ?? models[0];

    const chosenDuration = duration ?? String(current.defaultDuration);
    const chosenResolution = resolution ?? current.defaultResolution;
    const chosenRatio = ratio ?? current.defaultAspectRatio;

    const animating = mode === 'image';
    const hasPicture = Boolean(file || picture);

    const price =
        current.prices?.[animating ? 'image' : 'text']?.[chosenResolution]?.[
            chosenDuration
        ] ?? null;

    const open = access === 'open';
    const exhausted = open && limit?.remaining === 0;

    const canSubmit =
        open &&
        available &&
        !busy &&
        !exhausted &&
        (animating ? hasPicture : prompt.trim().length >= 3);

    const submit = (event) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        const settings = {
            model,
            duration: Number(chosenDuration),
            resolution: chosenResolution,
        };

        if (animating) {
            onSubmit('animate', {
                ...settings,
                prompt: prompt.trim() || null,
                image: file,
                source_id: file ? null : picture?.id,
            });
        } else {
            onSubmit('video', {
                ...settings,
                prompt: prompt.trim(),
                aspect_ratio: chosenRatio,
            });
        }

        // Запрос ушёл и виден в очереди — форма готова к следующему.
        // Выбранную из галереи картинку убирает страница.
        setPrompt('');
        setFile(null);
    };

    const clearPicture = () => {
        setFile(null);
        onClearPicture();
    };

    return (
        <div>
            <ModeTiles items={MODES} value={mode} onChange={setMode} wide />

            <Panel onSubmit={submit}>
                {!available && <NotReady />}

                {available && !open && <Locked access={access} />}

                {animating && (
                    <div className="mb-4">
                        <input
                            ref={fileRef}
                            type="file"
                            accept="image/*"
                            className="hidden"
                            onChange={(event) => {
                                const chosen = event.target.files[0] ?? null;

                                if (chosen) {
                                    onClearPicture();
                                    setFile(chosen);
                                }

                                event.target.value = '';
                            }}
                        />

                        {hasPicture ? (
                            <div className="flex items-center gap-3 rounded-xl border border-white/[0.12] bg-white/[0.03] p-2.5">
                                <img
                                    src={
                                        file
                                            ? URL.createObjectURL(file)
                                            : (picture.thumbnail ?? picture.url)
                                    }
                                    alt=""
                                    className="h-[72px] w-[72px] shrink-0 rounded-lg object-cover"
                                />

                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-[14px] text-white/85">
                                        {file ? file.name : picture.prompt}
                                    </span>
                                    <span className="text-[12px] text-white/35">
                                        {file ? 'From your device' : 'From your gallery'}
                                    </span>
                                </span>

                                <button
                                    type="button"
                                    onClick={clearPicture}
                                    aria-label="Remove"
                                    className="rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white"
                                >
                                    <X className="h-4 w-4" strokeWidth={1.75} />
                                </button>
                            </div>
                        ) : (
                            <div className="flex flex-wrap items-center gap-2.5 rounded-xl border border-dashed border-white/[0.14] px-4 py-3.5">
                                <span className="mr-auto text-[13px] text-white/50">
                                    Choose the picture to animate
                                </span>

                                <button
                                    type="button"
                                    onClick={() => fileRef.current?.click()}
                                    className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-4 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                                >
                                    <Upload className="h-4 w-4" strokeWidth={1.75} />
                                    Upload
                                </button>

                                <button
                                    type="button"
                                    onClick={() => {
                                        setFile(null);
                                        onBrowse();
                                    }}
                                    className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-4 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                                >
                                    <Images className="h-4 w-4" strokeWidth={1.75} />
                                    Gallery
                                </button>
                            </div>
                        )}
                    </div>
                )}

                <textarea
                    value={prompt}
                    onChange={(event) => setPrompt(event.target.value)}
                    placeholder={
                        animating
                            ? 'Describe how it should move — optional…'
                            : 'Describe your video…'
                    }
                    rows={3}
                    maxLength={2000}
                    className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
                />

                <div className="mt-4 flex flex-wrap items-center gap-2">
                    {models.length > 1 && (
                        <Dropdown
                            value={model}
                            onChange={(next) => {
                                setModel(next);
                                setDuration(null);
                                setResolution(null);
                                setRatio(null);
                            }}
                            options={models}
                            label="Model"
                            icon={Cpu}
                        />
                    )}

                    <Dropdown
                        value={chosenDuration}
                        onChange={setDuration}
                        label="Length"
                        icon={Clock}
                        options={current.durations.map((value) => ({
                            key: String(value),
                            label: `${value} sec`,
                        }))}
                    />

                    <Dropdown
                        value={chosenResolution}
                        onChange={setResolution}
                        label="Quality"
                        icon={MonitorPlay}
                        options={current.resolutions.map((value) => ({
                            key: value,
                            label: value,
                            description: RESOLUTIONS[value] ?? null,
                        }))}
                    />

                    {/* У оживления пропорции задаёт сама картинка. */}
                    {!animating && (
                        <Dropdown
                            value={chosenRatio}
                            onChange={setRatio}
                            label="Size"
                            icon={Crop}
                            options={current.aspectRatios.map((value) => ({
                                key: value,
                                label: RATIOS[value]?.[0] ?? value,
                                badge: value,
                                description: RATIOS[value]?.[1] ?? null,
                            }))}
                        />
                    )}

                    <SubmitBar
                        price={price}
                        limit={open ? limit : null}
                        canSubmit={canSubmit}
                        busy={busy}
                        icon={Video}
                    />
                </div>
            </Panel>
        </div>
    );
}

/**
 * Раздел закрыт: видео есть только на платных тарифах.
 */
function Locked({ access }) {
    const { openPlans } = usePlans();
    const guest = access === 'guest';

    const action =
        'flex h-9 shrink-0 items-center rounded-full bg-white px-4 text-[13px] font-semibold text-black transition hover:bg-white/90';

    return (
        <div className="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-white/[0.12] bg-white/[0.04] px-4 py-3">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/[0.07] text-white/60">
                <Lock className="h-[18px] w-[18px]" strokeWidth={1.75} />
            </span>

            <span className="min-w-0 flex-1">
                <span className="block text-[14px] font-medium text-white">
                    Video is part of paid plans
                </span>
                <span className="mt-0.5 block text-[12px] text-white/45">
                    {guest
                        ? 'Create an account and pick a plan to make video.'
                        : 'Upgrade to turn text and pictures into clips.'}
                </span>
            </span>

            {guest ? (
                <Link href="/auth?mode=register" className={action}>
                    Sign up
                </Link>
            ) : (
                <button type="button" onClick={openPlans} className={action}>
                    See plans
                </button>
            )}
        </div>
    );
}
