import { ChevronDown, X } from 'lucide-react';

/** Компактный выпадающий список. */
export function Select({ value, onChange, options, title }) {
    return (
        <div className="relative">
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                title={title}
                className="h-9 cursor-pointer appearance-none rounded-full border border-white/[0.12] bg-slate-950/70 py-0 pl-3.5 pr-8 text-[13px] text-white/75 outline-none transition hover:text-white focus:border-white/25 focus:ring-0"
            >
                {options.map((option) => (
                    <option
                        key={option.key}
                        value={option.key}
                        className="bg-slate-900 text-white"
                    >
                        {option.label}
                    </option>
                ))}
            </select>

            <ChevronDown
                className="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-white/40"
                strokeWidth={2}
            />
        </div>
    );
}

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
                        className="absolute inset-0 flex items-center justify-center bg-black/65 opacity-0 transition group-hover:opacity-100"
                    >
                        <X className="h-4 w-4 text-white" strokeWidth={2} />
                    </button>
                </div>
            ))}
        </div>
    );
}
