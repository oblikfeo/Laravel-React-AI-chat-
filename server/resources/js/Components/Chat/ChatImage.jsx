import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { MoreHorizontal, Maximize2, Download, Wand2 } from 'lucide-react';

/**
 * Картинка, которой ответил чат.
 *
 * Ответ — сама картинка, без слов. Действия собраны в маленькое меню
 * в углу: открыть целиком, скачать, продолжить работу в Студии.
 */
export default function ChatImage({ image }) {
    const [open, setOpen] = useState(false);
    const menuRef = useRef(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const close = (event) => {
            if (!menuRef.current?.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', close);

        return () => document.removeEventListener('mousedown', close);
    }, [open]);

    const item =
        'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-[13px] text-white/80 transition hover:bg-white/10 hover:text-white';

    return (
        <div className="group relative w-fit max-w-full">
            <div className="overflow-hidden rounded-2xl border border-white/10 bg-black/20">
                <img src={image.url} alt={image.prompt} className="block max-h-[420px] w-auto max-w-full" />
            </div>

            <div ref={menuRef} className="absolute right-2 top-2">
                <button
                    type="button"
                    onClick={() => setOpen((value) => !value)}
                    aria-label="Image actions"
                    aria-expanded={open}
                    className={`on-media flex h-8 w-8 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-md transition hover:bg-black/80 ${
                        open ? '' : 'sm:opacity-0 sm:group-hover:opacity-100'
                    }`}
                >
                    <MoreHorizontal className="h-4 w-4" strokeWidth={2} />
                </button>

                {open && (
                    <div className="popover absolute right-0 top-10 z-20 w-48 rounded-xl border border-white/10 bg-slate-950/95 p-1 shadow-2xl shadow-black/50 backdrop-blur-xl">
                        <a href={image.url} target="_blank" rel="noopener noreferrer" className={item}>
                            <Maximize2 className="h-4 w-4 shrink-0" strokeWidth={1.75} />
                            Open full size
                        </a>

                        <a href={`${image.url}?download=1`} className={item}>
                            <Download className="h-4 w-4 shrink-0" strokeWidth={1.75} />
                            Download
                        </a>

                        <Link href={`/studio?compose=edit&work=${image.id}`} className={item}>
                            <Wand2 className="h-4 w-4 shrink-0" strokeWidth={1.75} />
                            Edit in Studio
                        </Link>
                    </div>
                )}
            </div>
        </div>
    );
}
