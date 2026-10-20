/**
 * Переключатель: режимы сверху, разделы под ними.
 *
 * Голубая точка на пункте значит, что там ждёт готовая работа,
 * которую человек ещё не открывал. Вращающееся кольцо — что там
 * сейчас что-то создаётся.
 */
export default function Switch({ items, value, onChange, badges = {}, working = {}, compact = false }) {
    return (
        <div className="flex items-center gap-1 rounded-full border border-white/[0.12] bg-slate-950/50 p-1 backdrop-blur-xl">
            {items.map(({ key, label, icon: Icon }) => {
                const fresh = badges[key] > 0;

                return (
                    <button
                        key={key}
                        type="button"
                        onClick={() => onChange(key)}
                        title={label}
                        className={`relative flex h-9 items-center gap-2 rounded-full px-4 text-sm transition ${
                            value === key
                                ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                                : fresh
                                  ? 'text-sky-200 hover:text-white'
                                  : 'text-white/55 hover:text-white'
                        }`}
                    >
                        <Icon className="h-4 w-4" strokeWidth={1.75} />

                        <span className={compact ? 'hidden sm:inline' : ''}>
                            {label}
                        </span>

                        {fresh && !compact && (
                            <span className="keep-light flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-sky-400 px-1 text-[11px] font-semibold text-slate-950">
                                {badges[key]}
                            </span>
                        )}

                        {!fresh && working[key] && (
                            <span className="absolute right-1 top-1 h-2 w-2 animate-spin rounded-full border border-sky-300/80 border-t-transparent" />
                        )}

                        {fresh && (
                            <span className="absolute right-1 top-1 flex h-2 w-2">
                                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75" />
                                <span className="relative inline-flex h-2 w-2 rounded-full bg-sky-400" />
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
