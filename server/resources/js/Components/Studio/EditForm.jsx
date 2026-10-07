import { useRef, useState } from 'react';
import {
    Wand2,
    Layers,
    Maximize2,
    Scissors,
    Upload,
    Images,
    X,
    Crop,
} from 'lucide-react';
import {
    Panel,
    NotReady,
    Remaining,
    Visibility,
} from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';

/**
 * Инструменты правки.
 *
 * Каждый берёт готовую картинку и делает с ней одно понятное
 * действие, поэтому они стоят крупно: это главное на вкладке.
 */
const TOOLS = [
    {
        key: 'edit',
        label: 'Edit Image',
        hint: 'Change it by description',
        icon: Wand2,
        needsPrompt: true,
        minimum: 1,
    },
    {
        key: 'combine',
        label: 'Combine',
        hint: 'Merge several into one',
        icon: Layers,
        needsPrompt: true,
        minimum: 2,
    },
    {
        key: 'upscale',
        label: 'Upscale',
        hint: 'More resolution, same picture',
        icon: Maximize2,
        needsPrompt: false,
        minimum: 1,
    },
    {
        key: 'background_remove',
        label: 'Remove Background',
        hint: 'Cut the subject out',
        icon: Scissors,
        needsPrompt: false,
        minimum: 1,
    },
];

/**
 * Правка готового изображения.
 *
 * Выбор картинки и настройки живут в одной форме: два отдельных блока
 * дублировали загрузку и оставляли непонятным, где именно выбирать.
 */
export default function EditForm({
    aspectRatios,
    upscaleScales = [2, 4],
    maxCombine = 4,
    available,
    busy,
    limit,
    sources = [],
    onRemoveSource,
    onBrowse,
    onSubmit,
}) {
    const [tool, setTool] = useState('edit');
    const [prompt, setPrompt] = useState('');
    const [ratio, setRatio] = useState('1:1');
    const [scale, setScale] = useState(String(upscaleScales[0] ?? 2));
    const [files, setFiles] = useState([]);
    const [isPublic, setIsPublic] = useState(true);
    const fileRef = useRef(null);

    const current = TOOLS.find((item) => item.key === tool) ?? TOOLS[0];

    const picked = [
        ...sources.map((item) => ({ kind: 'asset', item })),
        ...files.map((file, index) => ({ kind: 'file', file, index })),
    ];

    const enough = picked.length >= current.minimum;
    const exhausted = limit?.remaining === 0;
    const canSubmit =
        enough &&
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
            source_ids: sources.map((item) => item.id),
            images: files,
            aspect_ratio:
                tool === 'upscale' || tool === 'background_remove' ? null : ratio,
            scale: tool === 'upscale' ? Number(scale) : null,
            is_public: isPublic,
        });
    };

    return (
        <div>
            {/* Инструменты крупно: ради них на вкладку и заходят. */}
            <div className="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                {TOOLS.map(({ key, label, hint, icon: Icon }) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setTool(key)}
                        className={`flex flex-col items-start gap-2 rounded-2xl border p-3.5 text-left transition ${
                            tool === key
                                ? 'border-white/30 bg-white/[0.09] shadow-lg shadow-black/30'
                                : 'border-white/[0.09] bg-slate-950/40 hover:border-white/20 hover:bg-white/[0.05]'
                        }`}
                    >
                        <span
                            className={`flex h-9 w-9 items-center justify-center rounded-xl transition ${
                                tool === key
                                    ? 'bg-white text-black'
                                    : 'bg-white/[0.07] text-white/60'
                            }`}
                        >
                            <Icon className="h-[18px] w-[18px]" strokeWidth={1.75} />
                        </span>

                        <span className="min-w-0">
                            <span
                                className={`block text-[13px] font-medium ${
                                    tool === key ? 'text-white' : 'text-white/75'
                                }`}
                            >
                                {label}
                            </span>
                            <span className="mt-0.5 block text-[11px] leading-snug text-white/35">
                                {hint}
                            </span>
                        </span>
                    </button>
                ))}
            </div>

            <Panel onSubmit={submit}>
                {!available && <NotReady />}

                <input
                    ref={fileRef}
                    type="file"
                    multiple={current.minimum > 1}
                    accept="image/*"
                    className="hidden"
                    onChange={(event) => {
                        addFiles(event.target.files);
                        // Сброс: иначе повторный выбор того же файла
                        // не вызовет событие.
                        event.target.value = '';
                    }}
                />

                {/* Что правим. Пока ничего не выбрано — приглашение,
                    дальше миниатюры выбранного. */}
                {picked.length ? (
                    <div className="mb-4 flex flex-wrap gap-2">
                        {picked.map((entry) =>
                            entry.kind === 'asset' ? (
                                <Thumb
                                    key={`asset-${entry.item.id}`}
                                    src={entry.item.thumbnail ?? entry.item.url}
                                    caption="From your work"
                                    onRemove={() => onRemoveSource(entry.item.id)}
                                />
                            ) : (
                                <Thumb
                                    key={`file-${entry.index}`}
                                    src={URL.createObjectURL(entry.file)}
                                    caption="Uploaded"
                                    onRemove={() =>
                                        setFiles(
                                            files.filter(
                                                (_, position) =>
                                                    position !== entry.index,
                                            ),
                                        )
                                    }
                                />
                            ),
                        )}

                        {picked.length < maxCombine && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => fileRef.current?.click()}
                                    title="Upload from your device"
                                    className="flex h-[84px] w-[84px] shrink-0 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-white/20 text-white/45 transition hover:border-white/40 hover:text-white"
                                >
                                    <Upload className="h-4 w-4" strokeWidth={1.75} />
                                    <span className="text-[11px]">Upload</span>
                                </button>

                                {/* Вторую картинку тоже можно взять из
                                    своих работ, а не только с диска. */}
                                <button
                                    type="button"
                                    onClick={onBrowse}
                                    title="Pick from your work"
                                    className="flex h-[84px] w-[84px] shrink-0 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-white/20 text-white/45 transition hover:border-white/40 hover:text-white"
                                >
                                    <Images className="h-4 w-4" strokeWidth={1.75} />
                                    <span className="text-[11px]">My work</span>
                                </button>
                            </>
                        )}
                    </div>
                ) : (
                    <div className="mb-4 flex flex-wrap items-center gap-2.5 rounded-2xl border border-dashed border-white/[0.14] px-4 py-3.5">
                        <span className="mr-auto text-[13px] text-white/50">
                            {current.minimum > 1
                                ? 'Pick at least two images'
                                : 'Pick an image to work with'}
                        </span>

                        <button
                            type="button"
                            onClick={() => fileRef.current?.click()}
                            className="flex h-9 items-center gap-2 rounded-full bg-white px-4 text-[13px] font-semibold text-black transition hover:bg-white/90"
                        >
                            <Upload className="h-4 w-4" strokeWidth={2} />
                            Upload
                        </button>

                        <button
                            type="button"
                            onClick={onBrowse}
                            className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-4 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                        >
                            <Images className="h-4 w-4" strokeWidth={1.75} />
                            From my work
                        </button>
                    </div>
                )}

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
                    <p className="py-1 text-[15px] text-white/45">
                        {current.hint}. No description needed.
                    </p>
                )}

                <div className="mt-4 flex flex-wrap items-center gap-2">
                    {tool === 'upscale' && (
                        <Dropdown
                            value={scale}
                            onChange={setScale}
                            label="Scale"
                            icon={Maximize2}
                            options={upscaleScales.map((value) => ({
                                key: String(value),
                                label: `${value}x`,
                                description:
                                    value === 2
                                        ? 'Twice the resolution'
                                        : 'Four times, slower',
                            }))}
                        />
                    )}

                    {(tool === 'edit' || tool === 'combine') && (
                        <Dropdown
                            value={ratio}
                            onChange={setRatio}
                            options={aspectRatios}
                            label="Size"
                            icon={Crop}
                        />
                    )}

                    <Visibility value={isPublic} onChange={setIsPublic} />

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
            </Panel>
        </div>
    );
}

/** Выбранная картинка: видно, что именно пойдёт в работу. */
function Thumb({ src, caption, onRemove }) {
    return (
        <div className="group relative h-[84px] w-[84px] shrink-0 overflow-hidden rounded-xl border border-white/25">
            <img src={src} alt="" className="h-full w-full object-cover" />

            <span className="on-media absolute inset-x-0 bottom-0 bg-black/70 px-1 py-0.5 text-center text-[9px] uppercase tracking-wide text-white/65">
                {caption}
            </span>

            <button
                type="button"
                onClick={onRemove}
                aria-label="Remove"
                className="on-media absolute inset-0 flex items-center justify-center bg-black/65 opacity-0 transition group-hover:opacity-100"
            >
                <X className="h-4 w-4 text-white" strokeWidth={2} />
            </button>
        </div>
    );
}
