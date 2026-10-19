import { FileText, X } from 'lucide-react';

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

/** Расширение файла для ярлыка: PDF, TXT, CSV. */
function extensionOf(name) {
    const part = name.includes('.') ? name.split('.').pop() : '';

    return part.slice(0, 4).toUpperCase() || 'FILE';
}

/**
 * Приложенные файлы над полем ввода.
 *
 * Картинка — квадратной миниатюрой, документ — карточкой с типом,
 * именем и размером. Убрать файл можно крестиком в углу: на телефоне
 * он виден всегда, на компьютере — при наведении.
 */
export default function AttachedFiles({ files, onRemove }) {
    if (!files.length) {
        return null;
    }

    return (
        <div className="mb-3 flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
            {files.map((file, index) => {
                const remove = (
                    <button
                        type="button"
                        onClick={() => onRemove(index)}
                        aria-label={`Remove ${file.name}`}
                        className="on-media absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/65 text-white opacity-100 backdrop-blur-sm transition hover:bg-black/85 sm:opacity-0 sm:group-hover:opacity-100"
                    >
                        <X className="h-3.5 w-3.5" strokeWidth={2.25} />
                    </button>
                );

                if (file.type.startsWith('image/')) {
                    return (
                        <div
                            key={`${file.name}-${index}`}
                            title={file.name}
                            className="group relative h-[72px] w-[72px] shrink-0 overflow-hidden rounded-2xl border border-white/[0.14] bg-white/[0.05]"
                        >
                            <img
                                src={URL.createObjectURL(file)}
                                alt={file.name}
                                className="h-full w-full object-cover"
                            />
                            {remove}
                        </div>
                    );
                }

                return (
                    <div
                        key={`${file.name}-${index}`}
                        title={file.name}
                        className="group relative flex h-[72px] w-[210px] shrink-0 items-center gap-3 rounded-2xl border border-white/[0.14] bg-white/[0.05] px-3"
                    >
                        <span className="relative flex h-11 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white/60">
                            <FileText className="h-5 w-5" strokeWidth={1.75} />
                            <span className="keep-light absolute -bottom-1.5 left-1/2 -translate-x-1/2 rounded bg-sky-400 px-1 text-[9px] font-bold leading-[14px] text-slate-950">
                                {extensionOf(file.name)}
                            </span>
                        </span>

                        <span className="min-w-0 pr-5">
                            <span className="block truncate text-[13px] font-medium text-white">
                                {file.name}
                            </span>
                            <span className="block text-[11px] text-white/45">
                                {sizeOf(file.size)}
                            </span>
                        </span>

                        {remove}
                    </div>
                );
            })}
        </div>
    );
}
