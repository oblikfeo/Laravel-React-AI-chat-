import { useRef, useState } from 'react';
import {
    Wand2,
    Layers,
    Maximize2,
    Scissors,
    Upload,
    FolderOpen,
} from 'lucide-react';
import { Select, Panel, NotReady, Remaining, Thumbnails } from '@/Components/Studio/Controls';

/** Инструменты правки — те же, что в панели под полем ввода у Venice. */
const TOOLS = [
    { key: 'edit', label: 'Edit Image', icon: Wand2, needsPrompt: true },
    { key: 'combine', label: 'Combine', icon: Layers, needsPrompt: true },
    { key: 'upscale', label: 'Upscale', icon: Maximize2, needsPrompt: false },
    {
        key: 'background_remove',
        label: 'Remove Background',
        icon: Scissors,
        needsPrompt: false,
    },
];

/**
 * Правка готового изображения.
 *
 * Исходник берётся либо из своих работ, либо с диска: человек правит
 * и то, что нарисовал здесь, и то, что принёс с собой.
 */
export default function EditForm({
    aspectRatios,
    upscaleScales = [2, 4],
    maxCombine = 4,
    available,
    busy,
    limit,
    source,
    onPickSource,
    onSubmit,
}) {
    const [tool, setTool] = useState('edit');
    const [prompt, setPrompt] = useState('');
    const [ratio, setRatio] = useState('1:1');
    const [scale, setScale] = useState(String(upscaleScales[0] ?? 2));
    const [files, setFiles] = useState([]);
    const fileRef = useRef(null);

    const current = TOOLS.find((item) => item.key === tool) ?? TOOLS[0];

    // Объединение требует нескольких картинок, остальные инструменты —
    // одной: своей работы или загруженного файла.
    const minimum = tool === 'combine' ? 2 : 1;
    const total = files.length + (source ? 1 : 0);
    const enoughImages = total >= minimum;

    const exhausted = limit?.remaining === 0;
    const canSubmit =
        enoughImages &&
        (!current.needsPrompt || prompt.trim().length > 0) &&
        !busy &&
        available &&
        !exhausted;

    const addFiles = (incoming) => {
        setFiles((previous) =>
            [...previous, ...Array.from(incoming)].slice(0, maxCombine),
        );
    };

    const submit = (event) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        onSubmit({
            operation: tool,
            prompt: current.needsPrompt ? prompt.trim() : null,
            source_generation_id: source?.id ?? null,
            images: files,
            aspect_ratio: tool === 'upscale' || tool === 'background_remove' ? null : ratio,
            scale: tool === 'upscale' ? Number(scale) : null,
        });
    };

    return (
        <div>
            {/* Выбор исходника стоит над формой: пока картинки нет,
                править нечего. */}
            <div className="mb-4 rounded-3xl border border-white/[0.08] bg-slate-950/40 p-5 text-center backdrop-blur-xl">
                {source ? (
                    <div className="flex flex-col items-center gap-3">
                        <img
                            src={source.url}
                            alt=""
                            className="max-h-44 rounded-xl border border-white/[0.12] object-contain"
                        />
                        <button
                            type="button"
                            onClick={() => onPickSource(null)}
                            className="text-[13px] text-white/50 transition hover:text-white"
                        >
                            Choose another
                        </button>
                    </div>
                ) : (
                    <>
                        <Wand2
                            className="mx-auto h-7 w-7 text-white/25"
                            strokeWidth={1.5}
                        />
                        <p className="mt-3 text-[15px] font-medium text-white">
                            Edit Studio
                        </p>
                        <p className="mt-1 text-sm text-white/45">
                            Select an image to start editing
                        </p>

                        <div className="mt-4 flex flex-wrap justify-center gap-2">
                            <button
                                type="button"
                                onClick={() => fileRef.current?.click()}
                                className="flex h-9 items-center gap-2 rounded-full bg-white px-4 text-[13px] font-semibold text-black transition hover:bg-white/90"
                            >
                                <Upload className="h-4 w-4" strokeWidth={2} />
                                Upload New
                            </button>

                            <button
                                type="button"
                                onClick={() => onPickSource('browse')}
                                className="flex h-9 items-center gap-2 rounded-full border border-white/[0.12] px-4 text-[13px] text-white/70 transition hover:bg-white/10 hover:text-white"
                            >
                                <FolderOpen className="h-4 w-4" strokeWidth={1.75} />
                                Select Asset
                            </button>
                        </div>
                    </>
                )}
            </div>

            {/* Инструменты: кнопки, а не список — так видно все сразу. */}
            <div className="mb-3 flex flex-wrap justify-center gap-2">
                {TOOLS.map(({ key, label, icon: Icon }) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setTool(key)}
                        className={`flex h-9 items-center gap-2 rounded-full px-4 text-[13px] transition ${
                            tool === key
                                ? 'bg-white font-semibold text-black'
                                : 'border border-white/[0.12] text-white/65 hover:bg-white/10 hover:text-white'
                        }`}
                    >
                        <Icon className="h-4 w-4" strokeWidth={1.75} />
                        {label}
                    </button>
                ))}
            </div>

            <Panel onSubmit={submit}>
                {!available && <NotReady />}

                <input
                    ref={fileRef}
                    type="file"
                    multiple={tool === 'combine'}
                    accept="image/*"
                    className="hidden"
                    onChange={(event) => {
                        addFiles(event.target.files);
                        // Сброс: иначе повторный выбор того же файла
                        // не вызовет событие.
                        event.target.value = '';
                    }}
                />

                <Thumbnails
                    files={files}
                    onRemove={(index) =>
                        setFiles(files.filter((_, position) => position !== index))
                    }
                />

                {current.needsPrompt ? (
                    <textarea
                        value={prompt}
                        onChange={(event) => setPrompt(event.target.value)}
                        placeholder={
                            tool === 'combine'
                                ? 'Describe how to combine them…'
                                : 'Describe the changes you want…'
                        }
                        rows={2}
                        className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
                    />
                ) : (
                    <p className="py-1 text-[15px] text-white/50">
                        {tool === 'upscale'
                            ? 'Increase resolution without losing detail.'
                            : 'Cut the subject out of its background.'}
                    </p>
                )}

                <div className="mt-4 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        onClick={() => fileRef.current?.click()}
                        disabled={files.length >= maxCombine}
                        className="flex h-9 items-center gap-2 rounded-full border border-white/[0.12] px-3.5 text-[13px] text-white/60 transition hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <Upload className="h-4 w-4" strokeWidth={1.75} />
                        {tool === 'combine' ? 'Add images' : 'Upload'}
                    </button>

                    {tool === 'upscale' && (
                        <Select
                            value={scale}
                            onChange={setScale}
                            title="Scale"
                            options={upscaleScales.map((value) => ({
                                key: String(value),
                                label: `${value}x`,
                            }))}
                        />
                    )}

                    {(tool === 'edit' || tool === 'combine') && (
                        <Select
                            value={ratio}
                            onChange={setRatio}
                            options={aspectRatios}
                            title="Aspect ratio"
                        />
                    )}

                    <div className="ml-auto flex items-center gap-3">
                        <Remaining limit={limit} />

                        <button
                            type="submit"
                            disabled={!canSubmit}
                            className={`flex h-10 items-center gap-2 rounded-full px-5 text-[14px] font-semibold transition ${
                                canSubmit
                                    ? 'bg-white text-black hover:bg-white/90'
                                    : 'cursor-not-allowed border border-white/[0.12] text-white/35'
                            }`}
                        >
                            <current.icon className="h-4 w-4" strokeWidth={2} />
                            {busy ? 'Working…' : 'Apply'}
                        </button>
                    </div>
                </div>

                {!enoughImages && (
                    <p className="mt-3 text-[13px] text-white/40">
                        {tool === 'combine'
                            ? 'Add at least two images to combine.'
                            : 'Choose an image first.'}
                    </p>
                )}
            </Panel>
        </div>
    );
}
