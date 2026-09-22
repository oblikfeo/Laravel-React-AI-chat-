/**
 * Поле формы настроек: подпись, ввод и текст ошибки.
 *
 * Вынесено отдельно, чтобы новые разделы не повторяли разметку.
 */
export default function Field({ label, error, hint, ...props }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-[13px] font-medium text-white/70">
                {label}
            </span>

            <input
                {...props}
                className="h-11 w-full rounded-xl border border-white/[0.12] bg-white/[0.04] px-3.5 text-[15px] text-white outline-none transition placeholder:text-white/30 focus:border-white/25 focus:bg-white/[0.07]"
            />

            {hint && !error && (
                <span className="mt-1.5 block text-xs text-white/40">
                    {hint}
                </span>
            )}

            {error && (
                <span className="mt-1.5 block text-xs text-rose-300">
                    {error}
                </span>
            )}
        </label>
    );
}
