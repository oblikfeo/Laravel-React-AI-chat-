/**
 * Переключатель «включено / выключено» с подписью.
 */
export default function Toggle({
    label,
    hint,
    checked,
    onChange,
    disabled = false,
}) {
    return (
        <div className="flex items-start justify-between gap-4 py-3">
            <div className="min-w-0">
                <p
                    className={`text-[14px] font-medium ${
                        disabled ? 'text-white/40' : 'text-white'
                    }`}
                >
                    {label}
                </p>

                {hint && (
                    <p className="mt-0.5 text-[12px] leading-snug text-white/45">
                        {hint}
                    </p>
                )}
            </div>

            <button
                type="button"
                role="switch"
                aria-checked={checked}
                aria-label={label}
                disabled={disabled}
                onClick={() => onChange(!checked)}
                className={`relative mt-0.5 h-6 w-11 shrink-0 rounded-full transition ${
                    checked ? 'switch-on' : 'switch'
                } ${disabled ? 'cursor-not-allowed opacity-50' : ''}`}
            >
                <span
                    className={`switch-knob absolute top-0.5 h-5 w-5 rounded-full transition-[left] ${
                        checked ? 'left-[22px]' : 'left-0.5'
                    }`}
                />
            </button>
        </div>
    );
}
