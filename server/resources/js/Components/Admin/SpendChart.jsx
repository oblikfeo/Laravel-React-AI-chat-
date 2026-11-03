import { useState } from 'react';
import { usd } from '@/Components/Admin/format';

/**
 * Расходы на провайдеров по дням.
 *
 * Один ряд — один цвет, поэтому легенды нет: заголовок называет, что
 * на графике. Подсказка по наведению на день, шкала — две тонкие
 * линии: ноль и максимум.
 */
export default function SpendChart({ days }) {
    const [active, setActive] = useState(null);
    const max = Math.max(...days.map((day) => day.spend), 0);
    const hovered = active === null ? null : days[active];

    return (
        <div className="px-4 pb-4 pt-3">
            <div className="flex h-5 items-center justify-between text-[12px]">
                <span className="text-white/40">Max {usd(max)} / day</span>
                <span className="tabular-nums text-white/70" aria-live="polite">
                    {hovered ? `${hovered.date}: ${usd(hovered.spend)}` : ''}
                </span>
            </div>

            <div className="relative mt-2 h-40 border-b border-t border-white/[0.08]" onMouseLeave={() => setActive(null)}>
                <div className="absolute inset-0 flex items-end gap-[2px]">
                    {days.map((day, index) => (
                        <button
                            key={day.date}
                            type="button"
                            onMouseEnter={() => setActive(index)}
                            onFocus={() => setActive(index)}
                            onBlur={() => setActive(null)}
                            aria-label={`${day.date}: ${usd(day.spend)}`}
                            className="group flex h-full flex-1 items-end"
                        >
                            <span
                                className={`keep-light block w-full rounded-t-[4px] transition-colors ${
                                    active === index ? 'bg-sky-300' : 'bg-sky-400/80 group-hover:bg-sky-300'
                                }`}
                                style={{ height: max > 0 ? `${Math.max((day.spend / max) * 100, day.spend > 0 ? 2 : 0)}%` : 0 }}
                            />
                        </button>
                    ))}
                </div>
            </div>

            <div className="mt-1.5 flex justify-between text-[11px] text-white/35">
                <span>{days[0]?.date}</span>
                <span>{days[days.length - 1]?.date}</span>
            </div>

            <details className="mt-3 text-[12px] text-white/50">
                <summary className="cursor-pointer select-none hover:text-white/80">Show as table</summary>
                <div className="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 sm:grid-cols-3 lg:grid-cols-5">
                    {days.map((day) => (
                        <span key={day.date} className="flex justify-between tabular-nums">
                            <span>{day.date}</span>
                            <span className="text-white/75">{usd(day.spend)}</span>
                        </span>
                    ))}
                </div>
            </details>
        </div>
    );
}
