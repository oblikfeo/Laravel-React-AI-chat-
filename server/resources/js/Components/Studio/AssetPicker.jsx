import { useState } from 'react';
import { X, Check, Maximize2 } from 'lucide-react';

/**
 * Выбор готовой работы для правки.
 *
 * Клик по миниатюре отмечает её, а не открывает: открыть во весь
 * экран можно отдельной кнопкой. Иначе выбор и просмотр спорят друг
 * с другом — непонятно, что произойдёт по нажатию.
 */
export default function AssetPicker({ items, onPick, onClose }) {
    const [preview, setPreview] = useState(null);
    const [chosen, setChosen] = useState(null);

    const images = items.filter((item) => item.url && item.kind !== 'audio');

    return (
        <div
            className="fixed inset-0 z-[70] flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
        >
            <div
                className="flex max-h-[82vh] w-full max-w-[780px] flex-col overflow-hidden rounded-2xl border border-white/[0.12] bg-slate-950/95"
                onClick={(event) => event.stopPropagation()}
            >
                <div className="flex items-center justify-between border-b border-white/[0.08] px-5 py-3.5">
                    <div>
                        <h2 className="text-[15px] font-medium text-white">
                            Your work
                        </h2>
                        <p className="mt-0.5 text-[12px] text-white/40">
                            Pick the image you want to edit
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close"
                        className="rounded-lg p-1.5 text-white/55 transition hover:bg-white/10 hover:text-white"
                    >
                        <X className="h-[18px] w-[18px]" strokeWidth={1.75} />
                    </button>
                </div>

                <div className="scrollbar-thin flex-1 overflow-y-auto p-4">
                    {images.length ? (
                        <div className="grid grid-cols-3 gap-3 sm:grid-cols-4">
                            {images.map((item) => (
                                <div
                                    key={item.id}
                                    className={`group relative aspect-square overflow-hidden rounded-xl border transition ${
                                        chosen?.id === item.id
                                            ? 'border-sky-300/80 ring-2 ring-sky-300/40'
                                            : 'border-white/[0.1] hover:border-white/35'
                                    }`}
                                >
                                    <button
                                        type="button"
                                        onClick={() => setChosen(item)}
                                        className="h-full w-full"
                                        aria-label="Choose this image"
                                    >
                                        <img
                                            src={item.url}
                                            alt={item.prompt}
                                            loading="lazy"
                                            className="h-full w-full object-cover"
                                        />
                                    </button>

                                    {/* Посмотреть целиком, не выбирая. */}
                                    <button
                                        type="button"
                                        onClick={() => setPreview(item)}
                                        aria-label="View full size"
                                        title="View full size"
                                        className="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-lg border border-white/20 bg-black/60 text-white/80 opacity-0 backdrop-blur transition hover:bg-white/15 hover:text-white group-hover:opacity-100"
                                    >
                                        <Maximize2
                                            className="h-3.5 w-3.5"
                                            strokeWidth={1.75}
                                        />
                                    </button>

                                    {chosen?.id === item.id && (
                                        <span className="absolute left-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-sky-400 text-black">
                                            <Check
                                                className="h-3.5 w-3.5"
                                                strokeWidth={3}
                                            />
                                        </span>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="py-12 text-center text-sm text-white/45">
                            Nothing to edit yet. Create an image first, or
                            upload one from your device.
                        </p>
                    )}
                </div>

                {images.length > 0 && (
                    <div className="flex items-center justify-between gap-3 border-t border-white/[0.08] px-5 py-3.5">
                        <span className="text-[13px] text-white/40">
                            {chosen ? 'Image selected' : 'Nothing selected yet'}
                        </span>

                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={onClose}
                                className="h-9 rounded-full border border-white/[0.14] px-4 text-[13px] text-white/70 transition hover:bg-white/10 hover:text-white"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                disabled={!chosen}
                                onClick={() => onPick(chosen)}
                                className={`h-9 rounded-full px-5 text-[13px] font-semibold transition ${
                                    chosen
                                        ? 'bg-white text-black hover:bg-white/90'
                                        : 'cursor-not-allowed border border-white/[0.12] text-white/35'
                                }`}
                            >
                                Use this image
                            </button>
                        </div>
                    </div>
                )}
            </div>

            {preview && (
                <div
                    className="fixed inset-0 z-[80] flex items-center justify-center bg-black/95 p-4"
                    onClick={(event) => {
                        event.stopPropagation();
                        setPreview(null);
                    }}
                >
                    <img
                        src={preview.url}
                        alt={preview.prompt}
                        className="max-h-[85vh] w-auto rounded-2xl"
                    />
                </div>
            )}
        </div>
    );
}
