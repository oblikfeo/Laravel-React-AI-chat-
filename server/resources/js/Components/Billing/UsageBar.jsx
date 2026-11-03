/**
 * Сколько бюджета тарифа израсходовано: полоска и подпись.
 *
 * Человек видит только проценты и дату обновления — ни долларов, ни
 * токенов. Когда остаётся мало, полоска желтеет, исчерпан — краснеет.
 */
export default function UsageBar({ usage, compact = false }) {
    if (!usage) {
        return null;
    }

    const daily = usage.daily;
    const dailyBlocked = daily?.exhausted && !usage.exhausted;
    const used = dailyBlocked ? daily.percentUsed : usage.percentUsed;

    const tone =
        usage.exhausted || dailyBlocked
            ? 'bg-rose-400'
            : used >= 80
              ? 'bg-amber-400'
              : 'bg-sky-400';

    const caption = usage.exhausted
        ? `Used up · renews ${usage.renewsAt}`
        : dailyBlocked
          ? "Today's allowance used · renews tomorrow"
          : `${usage.percentLeft}% left · renews ${usage.renewsAt}`;

    return (
        <div>
            <div className="flex items-baseline justify-between gap-3">
                <span className={`${compact ? 'text-[12px]' : 'text-[13px]'} font-medium text-white/70`}>
                    Usage
                </span>
                <span className="text-[12px] tabular-nums text-white/45">{used}%</span>
            </div>

            <div
                className={`${compact ? 'mt-1.5 h-1' : 'mt-2 h-1.5'} overflow-hidden rounded-full bg-white/10`}
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={used}
                aria-label="Plan usage"
            >
                <div
                    className={`keep-light h-full rounded-full transition-[width] duration-500 ${tone}`}
                    style={{ width: `${Math.max(used, used > 0 ? 2 : 0)}%` }}
                />
            </div>

            <p className={`${compact ? 'mt-1 text-[11px]' : 'mt-1.5 text-[12px]'} text-white/40`}>
                {caption}
            </p>
        </div>
    );
}
