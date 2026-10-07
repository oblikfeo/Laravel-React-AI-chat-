import { X, Globe, Lock } from 'lucide-react';

/**
 * Панель формы.
 *
 * Все вкладки Студии выглядят одинаково: стеклянная карточка с полем
 * ввода и рядом настроек под ним.
 */
export function Panel({ children, onSubmit }) {
    return (
        <form
            onSubmit={onSubmit}
            className="rounded-3xl border border-white/[0.12] bg-slate-950/55 p-4 shadow-2xl shadow-black/40 backdrop-blur-2xl sm:p-5"
        >
            {children}
        </form>
    );
}

/** Сообщение о том, что раздел ещё не открыт. */
export function NotReady() {
    return (
        <p className="mb-4 rounded-xl border border-amber-400/20 bg-amber-400/[0.07] px-4 py-2.5 text-[13px] text-amber-100/85">
            Studio is coming soon. Everything is ready and will start working
            as soon as it opens.
        </p>
    );
}

/**
 * Показывать ли работу в общей ленте.
 *
 * По умолчанию показываем — лента без работ никому не интересна, —
 * но человек должен видеть этот выбор до того, как нажмёт кнопку.
 */
export function Visibility({ value, onChange }) {
    return (
        <button
            type="button"
            onClick={() => onChange(!value)}
            title={
                value
                    ? 'Visible in the public feed'
                    : 'Only you can see this'
            }
            className={`flex h-9 items-center gap-2 rounded-full border px-3.5 text-[13px] transition ${
                value
                    ? 'border-white/[0.12] text-white/70 hover:bg-white/10 hover:text-white'
                    : 'border-amber-300/25 bg-amber-300/[0.07] text-amber-100/80 hover:bg-amber-300/[0.12]'
            }`}
        >
            {value ? (
                <Globe className="h-4 w-4 shrink-0" strokeWidth={1.75} />
            ) : (
                <Lock className="h-4 w-4 shrink-0" strokeWidth={1.75} />
            )}
            {value ? 'Public' : 'Private'}
        </button>
    );
}

/** Остаток дневного лимита. */
export function Remaining({ limit }) {
    if (limit?.remaining === null || limit?.remaining === undefined) {
        return null;
    }

    return (
        <span className="text-[13px] text-white/40">
            {limit.remaining} of {limit.total} left today
        </span>
    );
}

/**
 * Выбранные файлы.
 *
 * Показываем миниатюры: по именам файлов человек не узнает свою
 * картинку, а по виду — сразу.
 */
export function Thumbnails({ files, onRemove }) {
    if (!files.length) {
        return null;
    }

    return (
        <div className="mb-3 flex flex-wrap gap-2">
            {files.map((file, index) => (
                <div
                    key={`${file.name}-${index}`}
                    className="group relative h-16 w-16 overflow-hidden rounded-xl border border-white/[0.12]"
                >
                    <img
                        src={URL.createObjectURL(file)}
                        alt=""
                        className="h-full w-full object-cover"
                    />

                    <button
                        type="button"
                        onClick={() => onRemove(index)}
                        aria-label="Remove"
                        className="on-media absolute inset-0 flex items-center justify-center bg-black/65 opacity-0 transition group-hover:opacity-100"
                    >
                        <X className="h-4 w-4 text-white" strokeWidth={2} />
                    </button>
                </div>
            ))}
        </div>
    );
}
