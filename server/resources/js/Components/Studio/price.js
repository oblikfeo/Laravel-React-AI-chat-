/**
 * Цена работы до запуска — в процентах бюджета тарифа.
 *
 * Провайдер берёт деньги по-разному: за работу, за секунду результата,
 * за символы текста или по таблице длительностей. Сервер отдаёт цену
 * в том виде, в каком она устроена, уже пересчитанную в проценты, а
 * итог считается здесь — он зависит от длины и количества, которые
 * человек меняет на лету.
 */
export function priceOf(price, { seconds = 0, count = 1, characters = 0 } = {}) {
    if (!price) {
        return null;
    }

    if (price.flat != null) {
        return price.flat * count;
    }

    if (price.perSecond != null) {
        return price.perSecond * seconds;
    }

    if (price.perMillion != null) {
        return (price.perMillion * characters) / 1_000_000;
    }

    if (price.table) {
        return price.table[seconds] ?? null;
    }

    return null;
}

/** Доля бюджета словами: `≈2%`, `≈0.4%`, а совсем малая — `<0.1%`. */
export function formatShare(value) {
    if (value == null || value <= 0) {
        return null;
    }

    if (value < 0.1) {
        return '<0.1%';
    }

    return value < 10 ? `≈${Number(value.toFixed(1))}%` : `≈${Math.round(value)}%`;
}
