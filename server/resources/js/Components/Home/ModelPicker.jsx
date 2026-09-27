import { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Sparkles, Check, Image as ImageIcon, Lock } from 'lucide-react';

/**
 * Выбор модели в поле ввода.
 *
 * Названия моделей у провайдера сюда не попадают: пользователь видит
 * только наши ярлыки, см. дизайн-систему, §12.
 */
export default function ModelPicker({ value, onChange }) {
    const { models = [], guest } = usePage().props;
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

    const active = models.find((m) => m.key === value) ?? models[0];

    // Гость видит весь список, но платные модели под замком: скрывать
    // их бессмысленно — человек должен понимать, что получит после
    // регистрации.
    const lockedFor = (model) => Boolean(guest) && model.key !== guest.model;

    const pick = (model) => {
        setOpen(false);

        if (lockedFor(model)) {
            router.visit('/auth?mode=register');

            return;
        }

        onChange(model.key);
    };

    if (!active) {
        return null;
    }

    return (
        <div ref={ref} className="relative">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                aria-haspopup="listbox"
                aria-expanded={open}
                aria-label="Choose model"
                className="flex h-10 items-center gap-2 rounded-full border border-white/[0.12] px-4 text-[15px] text-white/75 transition hover:bg-white/10 hover:text-white"
            >
                <Sparkles className="h-[18px] w-[18px]" strokeWidth={1.75} />
                {active.label}
            </button>

            {open && (
                <div
                    role="listbox"
                    className="absolute bottom-full left-0 z-30 mb-2 w-[290px] overflow-hidden rounded-2xl border border-white/10 bg-slate-950/95 p-1.5 shadow-2xl shadow-black/60 backdrop-blur-2xl"
                >
                    {models.map((model) => (
                        <button
                            key={model.key}
                            type="button"
                            role="option"
                            aria-selected={model.key === value}
                            onClick={() => pick(model)}
                            className={`flex w-full items-start gap-2.5 rounded-xl px-3 py-2.5 text-left transition hover:bg-white/10 ${
                                // Наша особенность: выделяем рамкой.
                                model.signature
                                    ? 'bg-sky-400/[0.07] ring-1 ring-inset ring-sky-400/45'
                                    : ''
                            }`}
                        >
                            <span className="min-w-0 flex-1">
                                <span className="flex items-center gap-2">
                                    <span
                                        className={`text-sm font-medium ${
                                            model.signature
                                                ? 'text-white'
                                                : 'text-white/70'
                                        }`}
                                    >
                                        {model.label}
                                    </span>

                                    {model.vision && (
                                        <ImageIcon
                                            className="h-3.5 w-3.5 text-white/40"
                                            strokeWidth={1.75}
                                        />
                                    )}
                                </span>

                                <span
                                    className={`mt-0.5 block text-xs leading-snug ${
                                        model.signature
                                            ? 'text-sky-100/60'
                                            : 'text-white/35'
                                    }`}
                                >
                                    {model.description}
                                </span>
                            </span>

                            {lockedFor(model) ? (
                                <Lock
                                    className="mt-1 h-3.5 w-3.5 shrink-0 text-white/30"
                                    strokeWidth={2}
                                />
                            ) : (
                                model.key === value && (
                                    <Check
                                        className="mt-1 h-4 w-4 shrink-0 text-sky-300"
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
