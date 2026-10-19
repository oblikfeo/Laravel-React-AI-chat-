import { X } from 'lucide-react';

/**
 * Картинка во весь экран с описанием и условиями создания.
 */
export default function ImageViewer({ item, onClose }) {
    return (
        <div
            className="on-media media-viewer fixed inset-0 z-[70] flex items-center justify-center bg-black/92 p-4 backdrop-blur-sm"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                onClick={onClose}
                aria-label="Close"
                className="absolute right-4 top-4 rounded-lg p-2 text-white/60 transition hover:bg-white/10 hover:text-white"
            >
                <X className="h-5 w-5" strokeWidth={1.75} />
            </button>

            <div className="max-h-full w-auto" onClick={(event) => event.stopPropagation()}>
                <img src={item.url} alt={item.prompt} className="max-h-[80vh] w-auto rounded-2xl" />

                <div className="mx-auto mt-4 max-w-[640px] text-center">
                    <p className="text-[14px] leading-relaxed text-white/80">{item.prompt}</p>

                    <p className="mt-2 text-[12px] text-white/35">
                        {[item.model, item.aspectRatio, item.seed ? `seed ${item.seed}` : null]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </div>
            </div>
        </div>
    );
}
