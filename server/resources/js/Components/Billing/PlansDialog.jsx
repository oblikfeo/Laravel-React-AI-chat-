import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { plans as allPlans } from '@/Constants/plans';

/**
 * Баннер выбора тарифа (figma/5.png).
 *
 * Показывается поверх любой страницы, поэтому не знает, откуда вызван:
 * состоянием управляет usePlans, см. Contexts/PlansContext.
 */
export default function PlansDialog({ open, onClose }) {
    const { auth } = usePage().props;
    const [yearly, setYearly] = useState(false);

    if (!open) {
        return null;
    }

    const current = auth?.user?.plan ?? 'Free';

    return (
        <div
            className="fixed inset-0 z-50 overflow-y-auto bg-[#06060b]"
            role="dialog"
            aria-modal="true"
            aria-label="Upgrade your plan"
            onClick={(event) => {
                // Закрываем только по клику мимо карточек.
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            {/* Туманность (figma/2.png): собственный фон баннера.
                Снизу гасим её в цвет подложки, чтобы карточки не
                спорили с картинкой, а текст оставался читаемым. */}
            <div
                aria-hidden
                className="pointer-events-none fixed inset-0 bg-cover bg-center opacity-[0.55]"
                style={{ backgroundImage: "url('/images/plans-bg.png')" }}
            />
            <div
                aria-hidden
                className="pointer-events-none fixed inset-0 bg-gradient-to-b from-[#06060b]/40 via-[#06060b]/75 to-[#06060b]"
            />

            <div className="relative flex min-h-full flex-col justify-center px-4 py-8 sm:px-6 sm:py-10">
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Close"
                    className="fixed right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full text-white/60 transition hover:bg-white/10 hover:text-white sm:right-6 sm:top-6"
                >
                    <X className="h-6 w-6" strokeWidth={1.75} />
                </button>

                <h2 className="text-center text-[24px] font-semibold tracking-tight text-white sm:text-[30px]">
                    Choose your plan
                </h2>

                <p className="mx-auto mt-2.5 max-w-[420px] text-center text-sm leading-relaxed text-white/50">
                    No limits, no filters. Upgrade any time, cancel whenever
                    you like.
                </p>

                <PeriodSwitch yearly={yearly} onChange={setYearly} />

                <div className="mx-auto mt-7 grid w-full max-w-[1200px] grid-cols-1 items-stretch gap-4 sm:grid-cols-4">
                    {allPlans.map((plan) => (
                        <PlanCard
                            key={plan.key}
                            plan={plan}
                            yearly={yearly}
                            current={plan.name === current}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}

/** Переключатель периода оплаты. */
function PeriodSwitch({ yearly, onChange }) {
    return (
        <div className="mt-5 flex justify-center">
            <div className="flex items-center gap-1 rounded-full border border-white/[0.12] bg-white/[0.04] p-1">
                {[
                    { label: 'Monthly', value: false },
                    { label: 'Yearly', value: true },
                ].map((option) => (
                    <button
                        key={option.label}
                        type="button"
                        onClick={() => onChange(option.value)}
                        className={`flex h-9 items-center gap-2 rounded-full px-5 text-sm transition ${
                            yearly === option.value
                                ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                                : 'text-white/60 hover:text-white'
                        }`}
                    >
                        {option.label}
                        {option.value && (
                            <span className="rounded-full bg-emerald-400/15 px-2 py-0.5 text-[11px] font-medium text-emerald-300">
                                Save 10%
                            </span>
                        )}
                    </button>
                ))}
            </div>
        </div>
    );
}

/** Карточка одного тарифа. */
function PlanCard({ plan, yearly, current }) {
    // При годовой оплате показываем цену за месяц: так тарифы
    // сравнимы между собой, а полная сумма идёт отдельной строкой.
    const price = yearly ? Math.round(plan.priceYearly / 12) : plan.priceMonthly;
    const free = plan.priceMonthly === 0;

    return (
        <div
            className={`group relative flex flex-col rounded-[18px] border p-5 backdrop-blur-xl transition duration-300 ${
                plan.popular
                    ? 'border-sky-400/50 bg-gradient-to-b from-sky-500/[0.12] to-white/[0.02] ring-1 ring-inset ring-sky-400/20'
                    : 'border-white/[0.09] bg-white/[0.025] hover:border-white/20'
            }`}
        >
            {plan.popular && (
                <span className="absolute -top-2.5 left-6 rounded-full bg-sky-500 px-2.5 py-1 text-[11px] font-semibold text-white shadow-lg shadow-sky-500/30">
                    Most popular
                </span>
            )}

            <p className="text-[15px] font-semibold text-white">{plan.name}</p>

            <p className="mt-2.5 flex items-baseline gap-1.5">
                <span className="text-[32px] font-semibold leading-none tracking-tight text-white">
                    ${price}
                </span>
                <span className="text-sm text-white/45">/mo</span>
            </p>

            {/* Строка держит высоту и когда пуста: иначе карточки
                разъезжаются при переключении периода. */}
            <p className="mt-1.5 h-4 text-xs text-white/40">
                {yearly && !free && `$${plan.priceYearly} billed yearly`}
            </p>

            <p className="mt-2.5 h-[38px] text-[13px] leading-relaxed text-white/55">
                {plan.tagline}
            </p>

            <button
                type="button"
                disabled={current}
                className={`mt-4 h-10 w-full rounded-full text-[13px] font-semibold transition ${
                    current
                        ? 'cursor-default border border-white/[0.12] text-white/40'
                        : plan.popular
                          ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/25 hover:bg-sky-400'
                          : 'bg-white/10 text-white ring-1 ring-inset ring-white/15 hover:bg-white/[0.16]'
                }`}
            >
                {current ? 'Current plan' : plan.cta}
            </button>

            <ul className="mt-5 flex-1 space-y-2.5 border-t border-white/[0.07] pt-4">
                {plan.features.map((feature) => (
                    <li key={feature} className="flex gap-2">
                        <Check
                            className={`mt-[3px] h-3.5 w-3.5 shrink-0 ${
                                plan.popular ? 'text-sky-300' : 'text-white/35'
                            }`}
                            strokeWidth={2.5}
                        />
                        <span className="text-[12.5px] leading-snug text-white/70">
                            {feature}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
