/**
 * Поле формы авторизации в стеклянном стиле дизайн-системы.
 */
export default function AuthField({
    id,
    label,
    type = 'text',
    value,
    onChange,
    error,
    autoComplete,
    autoFocus = false,
    placeholder,
}) {
    return (
        <div>
            <label
                htmlFor={id}
                className="mb-2 block text-sm font-medium text-white/70"
            >
                {label}
            </label>

            <input
                id={id}
                name={id}
                type={type}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                autoComplete={autoComplete}
                autoFocus={autoFocus}
                placeholder={placeholder}
                className={`h-12 w-full rounded-xl border bg-slate-900/60 px-4 text-[15px] text-white transition placeholder:text-white/35 focus:outline-none focus:ring-0 ${
                    error
                        ? 'border-red-400/60 focus:border-red-400'
                        : 'border-white/[0.12] focus:border-white/30'
                }`}
            />

            {error && <p className="mt-2 text-sm text-red-400">{error}</p>}
        </div>
    );
}
