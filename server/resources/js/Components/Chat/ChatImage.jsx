import { Download, Maximize2 } from 'lucide-react';

/**
 * Картинка, которой ответил чат.
 *
 * Ответ — сама картинка, без слов. Скачать и открыть целиком можно
 * кнопками при наведении; на телефоне они видны всегда.
 */
export default function ChatImage({ image }) {
    return (
        <div className="group relative w-fit max-w-full overflow-hidden rounded-2xl border border-white/10 bg-black/20">
            <img
                src={image.url}
                alt={image.prompt}
                className="block max-h-[420px] w-auto max-w-full"
            />

            <div className="on-media absolute bottom-2 right-2 flex gap-1.5 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                <a
                    href={image.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Open full size"
                    title="Open full size"
                    className="flex h-9 w-9 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-md transition hover:bg-black/80"
                >
                    <Maximize2 className="h-4 w-4" strokeWidth={2} />
                </a>

                <a
                    href={`${image.url}?download=1`}
                    aria-label="Download"
                    title="Download"
                    className="flex h-9 w-9 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-md transition hover:bg-black/80"
                >
                    <Download className="h-4 w-4" strokeWidth={2} />
                </a>
            </div>
        </div>
    );
}
