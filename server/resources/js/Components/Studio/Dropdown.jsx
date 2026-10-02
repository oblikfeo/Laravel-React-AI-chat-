import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Check, Lock, ChevronDown } from 'lucide-react';

/**
 * Выпадающий список Студии.
 *
 * Системный select выглядит чужеродно и в свёрнутом виде показывает
 * одно слово без намёка, что это за настройка. Здесь у кнопки есть
 * иконка и подпись параметра, а у пунктов — описания.
 *
 * Список рисуется поверх страницы, а не внутри формы: размытие фона
 * создаёт свой слой, и список оказывался под лентой работ.
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
    const [box, setBox] = useState(null);
    const ref = useRef(null);
    const listRef = useRef(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event) => {
            const insideButton = ref.current?.contains(event.target);
            const insideList = listRef.current?.contains(event.target);

            if (!insideButton && !insideList) {
                setOpen(false);
            }
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        // Список привязан к кнопке, поэтому при прокрутке и смене
        // размера окна его проще закрыть, чем пересчитывать.
        const close = () => setOpen(false);

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        window.addEventListener('resize', close);
        window.addEventListener('scroll', close, true);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
            window.removeEventListener('resize', close);
            window.removeEventListener('scroll', close, true);
        };
    }, [open]);

    // Положение считаем до отрисовки: иначе список успевает мигнуть
    // в левом верхнем углу.
    useLayoutEffect(() => {
        if (!open || !ref.current) {
            return;
        }

        setBox(ref.current.getBoundingClientRect());
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
    const width = detailed ? 280 : 200;

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

                {/* Пропорция рядом с названием: по одному «Square»
                    не понять, какой получится кадр. */}
                {active.badge && (
                    <span className="text-white/45">{active.badge}</span>
                )}

                <ChevronDown
                    className={`h-3.5 w-3.5 shrink-0 text-white/40 transition ${
                        open ? 'rotate-180' : ''
                    }`}
                    strokeWidth={2}
                />
            </button>

            {open && box && createPortal(
                <div
                    ref={listRef}
                    role="listbox"
                    style={position(box, width, up, align)}
                    className={`fixed z-[60] ${
                        detailed ? 'w-[280px]' : 'w-[200px]'
                    } overflow-hidden rounded-2xl border border-white/10 bg-slate-950/95 p-1.5 shadow-2xl shadow-black/60`}
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
                </div>,
                document.body,
            )}
        </div>
    );
}

/**
 * Куда поставить список.
 *
 * Если снизу не хватает места, разворачиваем вверх; по горизонтали
 * держим в пределах окна, чтобы край не уезжал за экран.
 */
function position(box, width, up, align) {
    const margin = 8;
    const openUp = up || box.bottom + 260 > window.innerHeight;

    let left = align === 'right' ? box.right - width : box.left;
    left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));

    return openUp
        ? { left, bottom: window.innerHeight - box.top + margin }
        : { left, top: box.bottom + margin };
}
