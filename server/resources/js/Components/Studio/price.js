/**
 * Цена работы до запуска.
 *
 * Провайдер берёт деньги по-разному: за работу, за секунду результата,
 * за символы текста или по таблице длительностей. Сервер отдаёт цену
 * в том виде, в каком она устроена, а итог считается здесь — он
 * зависит от длины и количества, которые человек меняет на лету.
 *
 * Пока своей системы кредитов нет, показываем себестоимость как есть.
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

/** Цена в долларах: `$0.12`, а доли цента — `<$0.01`. */
export function formatPrice(value) {
    if (value == null || value <= 0) {
        return null;
    }

    return value < 0.01 ? '<$0.01' : `$${value.toFixed(2)}`;
}
