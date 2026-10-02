import { useEffect, useRef, useState } from 'react';
import { Check, Lock, ChevronDown } from 'lucide-react';

/**
 * Выпадающий список Студии.
 *
 * Системный select выглядит чужеродно и в свёрнутом виде показывает
 * одно слово без намёка, что это за настройка. Здесь у кнопки есть
 * иконка и подпись параметра, а у пунктов — описания.
 */
export default function Dropdown({
    value,
    onChange,
    options,
    label,
    icon: Icon,
    // Куда раскрывать: в форме снизу места может не быть.
    align = 'left',
    up = false,
}) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) {
                setOpen(false);
            }
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    const active = options.find((option) => option.key === value) ?? options[0];

    if (!active) {
        return null;
    }

    const pick = (option) => {
        setOpen(false);

        if (option.locked) {
            return;
        }

        onChange(option.key);
    };

    // Описания есть не у всех списков: у соотношений сторон хватает
    // названия, и тогда пункты делаем компактнее.
    const detailed = options.some((option) => option.description);

    return (
        <div ref={ref} className="relative">
            <button
                type="button"
                onClick={() => setOpen((previous) => !previous)}
                aria-haspopup="listbox"
                aria-expanded={open}
                aria-label={label}
                className={`flex h-9 items-center gap-2 rounded-full border px-3.5 text-[13px] transition ${
                    open
                        ? 'border-white/30 bg-white/10 text-white'
                        : 'border-white/[0.12] text-white/70 hover:bg-white/10 hover:text-white'
                }`}
            >
                {Icon && (
                    <Icon
                        className="h-4 w-4 shrink-0 text-white/50"
                        strokeWidth={1.75}
                    />
                )}

                {/* Подпись параметра: без неё «Square» ни о чём не говорит. */}
                <span className="hidden text-white/40 sm:inline">{label}</span>

                <span className="font-medium">{active.label}</span>

                <ChevronDown
                    className={`h-3.5 w-3.5 shrink-0 text-white/40 transition ${
                        open ? 'rotate-180' : ''
                    }`}
                    strokeWidth={2}
                />
            </button>

            {open && (
                <div
                    role="listbox"
                    className={`absolute z-40 ${up ? 'bottom-full mb-2' : 'top-full mt-2'} ${
                        align === 'right' ? 'right-0' : 'left-0'
                    } ${
                        detailed ? 'w-[280px]' : 'w-[200px]'
                    } overflow-hidden rounded-2xl border border-white/10 bg-slate-950/95 p-1.5 shadow-2xl shadow-black/60 backdrop-blur-2xl`}
                >
                    {options.map((option) => (
                        <button
                            key={option.key}
                            type="button"
                            role="option"
                            aria-selected={option.key === value}
                            onClick={() => pick(option)}
                            className={`flex w-full items-start gap-2.5 rounded-xl px-3 ${
                                detailed ? 'py-2.5' : 'py-2'
                            } text-left transition hover:bg-white/10 ${
                                // Наша особенность: выделяем мерцающими
                                // звёздами, как и в чате.
                                option.signature ? 'signature-stars' : ''
                            } ${option.locked ? 'opacity-55' : ''}`}
                        >
                            <span className="min-w-0 flex-1">
                                <span className="flex items-center gap-2">
                                    <span
                                        className={`text-sm font-medium ${
                                            option.key === value || option.signature
                                                ? 'text-white'
                                                : 'text-white/70'
                                        }`}
                                    >
                                        {option.label}
                                    </span>

                                    {option.badge && (
                                        <span className="rounded-full border border-white/15 px-1.5 py-px text-[10px] uppercase tracking-wide text-white/45">
                                            {option.badge}
                                        </span>
                                    )}
                                </span>

                                {option.description && (
                                    <span
                                        className={`mt-0.5 block text-xs leading-snug ${
                                            option.signature
                                                ? 'text-sky-100/60'
                                                : 'text-white/35'
                                        }`}
                                    >
                                        {option.description}
                                    </span>
                                )}
                            </span>

                            {option.locked ? (
                                <Lock
                                    className="mt-0.5 h-3.5 w-3.5 shrink-0 text-white/30"
                                    strokeWidth={2}
                                />
                            ) : (
                                option.key === value && (
                                    <Check
                                        className="mt-0.5 h-4 w-4 shrink-0 text-sky-300"
                                        strokeWidth={2.5}
                                    />
                                )
                            )}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
