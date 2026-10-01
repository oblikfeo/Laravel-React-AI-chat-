import { X } from 'lucide-react';

/**
 * Выбор готовой работы как исходника для правки.
 *
 * Показываем только изображения: править звук этими инструментами
 * нельзя.
 */
export default function AssetPicker({ items, onPick, onClose }) {
    const images = items.filter(
        (item) => item.url && item.kind !== 'audio',
    );

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
        >
            <div
                className="max-h-[80vh] w-full max-w-[760px] overflow-hidden rounded-2xl border border-white/[0.12] bg-slate-950/95"
                onClick={(event) => event.stopPropagation()}
            >
                <div className="flex items-center justify-between border-b border-white/[0.08] px-5 py-3.5">
                    <h2 className="text-[15px] font-medium text-white">
                        Select an image
                    </h2>

                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close"
                        className="rounded-lg p-1 text-white/55 transition hover:bg-white/10 hover:text-white"
                    >
                        <X className="h-4.5 w-4.5" strokeWidth={1.75} />
                    </button>
                </div>

                <div className="max-h-[calc(80vh-60px)] overflow-y-auto p-4 scrollbar-thin">
                    {images.length ? (
                        <div className="grid grid-cols-3 gap-3 sm:grid-cols-4">
                            {images.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => onPick(item)}
                                    className="group aspect-square overflow-hidden rounded-xl border border-white/[0.1] transition hover:border-white/40"
                                >
                                    <img
                                        src={item.url}
                                        alt={item.prompt}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition group-hover:scale-105"
                                    />
                                </button>
                            ))}
                        </div>
                    ) : (
                        <p className="py-10 text-center text-sm text-white/45">
                            Nothing to edit yet. Create an image first, or
                            upload one from your device.
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
