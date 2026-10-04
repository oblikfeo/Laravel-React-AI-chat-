import { router } from '@inertiajs/react';
import { X, Wand2, MessageSquare, Download } from 'lucide-react';

/**
 * Работа во весь экран.
 *
 * Отсюда её можно забрать себе: открыть в Студии для правки или
 * отправить в чат, чтобы обсудить с моделью.
 */
export default function FeedViewer({ item, onClose }) {
    const edit = () =>
        router.visit('/studio', {
            data: { from_feed: item.id },
        });

    const toChat = () =>
        router.post(
            '/chats/from-feed',
            { generation_id: item.id },
            { preserveScroll: false },
        );

    return (
        <div
            className="fixed inset-0 z-[70] flex items-center justify-center bg-black/92 p-4 backdrop-blur-sm"
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

            <div
                className="flex max-h-full w-full max-w-[920px] flex-col items-center"
                onClick={(event) => event.stopPropagation()}
            >
                <img
                    src={item.url}
                    alt={item.prompt}
                    className="max-h-[66vh] w-auto rounded-2xl"
                />

                <div className="mt-4 w-full max-w-[640px] text-center">
                    <p className="text-[14px] leading-relaxed text-white/80">
                        {item.prompt}
                    </p>

                    <p className="mt-1.5 text-[12px] text-white/35">
                        {[item.author, item.model].filter(Boolean).join(' · ')}
                    </p>

                    <div className="mt-5 flex flex-wrap items-center justify-center gap-2">
                        <button
                            type="button"
                            onClick={edit}
                            className="flex h-10 items-center gap-2 rounded-full bg-white px-5 text-[14px] font-semibold text-black transition hover:bg-white/90"
                        >
                            <Wand2 className="h-4 w-4" strokeWidth={2} />
                            Edit in Studio
                        </button>

                        <button
                            type="button"
                            onClick={toChat}
                            className="flex h-10 items-center gap-2 rounded-full border border-white/[0.14] px-5 text-[14px] text-white/80 transition hover:bg-white/10 hover:text-white"
                        >
                            <MessageSquare className="h-4 w-4" strokeWidth={1.75} />
                            Send to chat
                        </button>

                        <a
                            href={`${item.url}?download=1`}
                            className="flex h-10 items-center gap-2 rounded-full border border-white/[0.14] px-5 text-[14px] text-white/80 transition hover:bg-white/10 hover:text-white"
                        >
                            <Download className="h-4 w-4" strokeWidth={1.75} />
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
}
