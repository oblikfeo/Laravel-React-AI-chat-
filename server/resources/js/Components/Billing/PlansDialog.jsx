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
            className="fixed inset-0 z-50 overflow-y-auto bg-[#06060b]/[0.94] backdrop-blur-2xl"
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
            {/* Мягкое свечение за карточками: оно даёт глубину вместо
                плоской заливки и перекликается с планетой на фоне. */}
            <div
                aria-hidden
                className="pointer-events-none fixed inset-x-0 top-0 h-[560px] bg-[radial-gradient(ellipse_70%_100%_at_50%_0%,rgba(56,189,248,0.16),rgba(99,102,241,0.08)_45%,transparent_75%)]"
            />

            <div className="relative min-h-full px-4 py-10 sm:px-6 sm:py-14">
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Close"
                    className="fixed right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full text-white/60 transition hover:bg-white/10 hover:text-white sm:right-6 sm:top-6"
                >
                    <X className="h-6 w-6" strokeWidth={1.75} />
                </button>

                <h2 className="text-center text-[28px] font-semibold tracking-tight text-white sm:text-[34px]">
                    Choose your plan
                </h2>

                <p className="mx-auto mt-3 max-w-[420px] text-center text-[15px] leading-relaxed text-white/50">
                    No limits, no filters. Upgrade any time, cancel whenever
                    you like.
                </p>

                <PeriodSwitch yearly={yearly} onChange={setYearly} />

                <div className="mx-auto mt-10 grid w-full max-w-[1280px] items-stretch gap-5 sm:grid-cols-2 xl:grid-cols-4">
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
        <div className="mt-7 flex justify-center">
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
            className={`group relative flex flex-col rounded-[20px] border p-6 backdrop-blur-xl transition duration-300 hover:-translate-y-1 ${
                plan.popular
                    ? 'border-sky-400/45 bg-gradient-to-b from-sky-500/[0.10] to-white/[0.02] shadow-xl shadow-sky-500/10'
                    : 'border-white/[0.09] bg-white/[0.025] hover:border-white/20'
            }`}
        >
            {plan.popular && (
                <span className="absolute -top-2.5 left-6 rounded-full bg-sky-500 px-2.5 py-1 text-[11px] font-semibold text-white shadow-lg shadow-sky-500/30">
                    Most popular
                </span>
            )}

            <p className="text-[17px] font-semibold text-white">{plan.name}</p>

            <p className="mt-3 flex items-baseline gap-1.5">
                <span className="text-[38px] font-semibold leading-none tracking-tight text-white">
                    ${price}
                </span>
                <span className="text-sm text-white/45">/mo</span>
            </p>

            {/* Строка держит высоту и когда пуста: иначе карточки
                разъезжаются при переключении периода. */}
            <p className="mt-2 h-4 text-xs text-white/40">
                {yearly && !free && `$${plan.priceYearly} billed yearly`}
            </p>

            <p className="mt-3 h-[44px] text-sm leading-relaxed text-white/55">
                {plan.tagline}
            </p>

            <button
                type="button"
                disabled={current}
                className={`mt-5 h-11 w-full rounded-full text-sm font-semibold transition ${
                    current
                        ? 'cursor-default border border-white/[0.12] text-white/40'
                        : plan.popular
                          ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/25 hover:bg-sky-400'
                          : 'bg-white/10 text-white ring-1 ring-inset ring-white/15 hover:bg-white/[0.16]'
                }`}
            >
                {current ? 'Current plan' : plan.cta}
            </button>

            <ul className="mt-6 flex-1 space-y-3 border-t border-white/[0.07] pt-5">
                {plan.features.map((feature) => (
                    <li key={feature} className="flex gap-2.5">
                        <Check
                            className={`mt-0.5 h-4 w-4 shrink-0 ${
                                plan.popular ? 'text-sky-300' : 'text-white/35'
                            }`}
                            strokeWidth={2.5}
                        />
                        <span className="text-[13px] leading-relaxed text-white/75">
                            {feature}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
