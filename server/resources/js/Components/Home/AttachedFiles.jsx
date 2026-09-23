import { FileText, Image as ImageIcon, X } from 'lucide-react';

/** Человекочитаемый размер файла. */
function sizeOf(bytes) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

/**
 * Приложенные файлы над полем ввода.
 *
 * Картинки показываем миниатюрой, документы — значком с именем:
 * так видно, что именно приложено, до отправки.
 */
export default function AttachedFiles({ files, onRemove }) {
    if (!files.length) {
        return null;
    }

    return (
        <div className="mb-3 flex flex-wrap gap-2">
            {files.map((file, index) => {
                const isImage = file.type.startsWith('image/');

                return (
                    <div
                        key={`${file.name}-${index}`}
                        className="group relative flex items-center gap-2.5 rounded-xl border border-white/[0.12] bg-white/[0.05] py-2 pl-2 pr-8"
                    >
                        {isImage ? (
                            <img
                                src={URL.createObjectURL(file)}
                                alt=""
                                className="h-9 w-9 shrink-0 rounded-lg object-cover"
                            />
                        ) : (
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                {isImage ? (
                                    <ImageIcon className="h-4 w-4 text-white/60" />
                                ) : (
                                    <FileText className="h-4 w-4 text-white/60" />
                                )}
                            </span>
                        )}

                        <span className="min-w-0">
                            <span className="block max-w-[150px] truncate text-[13px] text-white">
                                {file.name}
                            </span>
                            <span className="block text-[11px] text-white/40">
                                {sizeOf(file.size)}
                            </span>
                        </span>

                        <button
                            type="button"
                            onClick={() => onRemove(index)}
                            aria-label={`Remove ${file.name}`}
                            className="absolute right-1.5 top-1.5 rounded-md p-1 text-white/40 transition hover:bg-white/10 hover:text-white"
                        >
                            <X className="h-3.5 w-3.5" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
