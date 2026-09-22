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
            className="fixed inset-0 z-50 overflow-y-auto bg-[#07070c]/[0.97] backdrop-blur-xl"
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
            <div className="min-h-full px-4 py-10 sm:px-6 sm:py-14">
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Close"
                    className="fixed right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full text-white/60 transition hover:bg-white/10 hover:text-white sm:right-6 sm:top-6"
                >
                    <X className="h-6 w-6" strokeWidth={1.75} />
                </button>

                <h2 className="text-center text-2xl font-semibold text-white sm:text-[28px]">
                    Upgrade Your Plan
                </h2>

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
    const price = yearly ? plan.priceYearly : plan.priceMonthly;
    const period = yearly ? '/yr' : '/mo';

    return (
        <div
            className={`relative flex flex-col rounded-2xl border p-6 backdrop-blur-xl ${
                plan.popular
                    ? 'border-sky-400/40 bg-white/[0.04]'
                    : 'border-white/[0.09] bg-white/[0.02]'
            }`}
        >
            {plan.popular && (
                <span className="absolute right-6 top-6 text-xs font-medium text-sky-300">
                    Most Popular
                </span>
            )}

            <p className="text-[19px] font-semibold text-white">{plan.name}</p>

            <p className="mt-3 flex items-baseline gap-1">
                <span className="text-[40px] font-semibold leading-none text-white">
                    ${price}
                </span>
                <span className="text-sm text-white/50">{period}</span>
            </p>

            <p className="mt-3 min-h-[40px] text-sm leading-relaxed text-white/55">
                {plan.tagline}
            </p>

            <button
                type="button"
                disabled={current}
                className={`mt-5 h-11 w-full rounded-full text-sm font-semibold transition ${
                    current
                        ? 'cursor-default border border-white/[0.12] text-white/45'
                        : 'bg-sky-500 text-white hover:bg-sky-400'
                }`}
            >
                {current ? 'Current plan' : plan.cta}
            </button>

            <ul className="mt-6 flex-1 space-y-3">
                {plan.features.map((feature) => (
                    <li key={feature} className="flex gap-2.5">
                        <Check
                            className="mt-0.5 h-4 w-4 shrink-0 text-emerald-400"
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
