import { FileText } from 'lucide-react';

/**
 * Файлы, приложенные к сообщению.
 *
 * Картинку показываем целиком: человек должен видеть, что именно
 * отправил модели. Документ — строкой со значком и именем.
 */
export default function MessageAttachments({ items }) {
    if (!items?.length) {
        return null;
    }

    return (
        <div className="mb-2.5 space-y-2">
            {items.map((item) =>
                item.isImage ? (
                    <a
                        key={item.id}
                        href={item.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="block"
                    >
                        <img
                            src={item.url}
                            alt={item.name}
                            className="max-h-[260px] w-auto rounded-xl border border-white/10"
                        />
                    </a>
                ) : (
                    <a
                        key={item.id}
                        href={item.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2.5 rounded-xl border border-white/[0.12] bg-white/[0.06] px-3 py-2 transition hover:bg-white/10"
                    >
                        <FileText
                            className="h-4 w-4 shrink-0 text-white/60"
                            strokeWidth={1.75}
                        />
                        <span className="truncate text-[13px] text-white/85">
                            {item.name}
                        </span>
                    </a>
                ),
            )}
        </div>
    );
}
