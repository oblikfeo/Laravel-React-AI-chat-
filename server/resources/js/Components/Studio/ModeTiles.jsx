/**
 * Режимы раздела крупными плитками: значок, название, подсказка.
 *
 * Это главное на вкладке, и по одному названию не всегда понятно,
 * что внутри, поэтому у каждого режима есть короткое пояснение.
 */
export default function ModeTiles({ items, value, onChange, wide = false }) {
    return (
        <div className={`mb-4 grid grid-cols-2 gap-2 ${wide ? '' : 'sm:grid-cols-4'}`}>
            {items.map(({ key, label, hint, icon: Icon }) => {
                const active = value === key;

                return (
                    <button
                        key={key}
                        type="button"
                        onClick={() => onChange(key)}
                        className={`flex flex-col items-start gap-2 rounded-2xl border p-3.5 text-left transition ${
                            active
                                ? 'border-white/30 bg-white/[0.09] shadow-lg shadow-black/30'
                                : 'border-white/[0.09] bg-slate-950/40 hover:border-white/20 hover:bg-white/[0.05]'
                        }`}
                    >
                        <span
                            className={`flex h-9 w-9 items-center justify-center rounded-xl transition ${
                                active ? 'bg-white text-black' : 'bg-white/[0.07] text-white/60'
                            }`}
                        >
                            <Icon className="h-[18px] w-[18px]" strokeWidth={1.75} />
                        </span>

                        <span className="min-w-0">
                            <span
                                className={`block text-[13px] font-medium ${
                                    active ? 'text-white' : 'text-white/75'
                                }`}
                            >
                                {label}
                            </span>
                            <span className="mt-0.5 block text-[11px] leading-snug text-white/35">
                                {hint}
                            </span>
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
