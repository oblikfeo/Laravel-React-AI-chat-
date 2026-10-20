/** Длительность словами: 90 секунд понятнее как «1:30». */
export function formatSeconds(total) {
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;

    return minutes
        ? `${minutes}:${String(seconds).padStart(2, '0')}`
        : `${seconds}s`;
}

/**
 * Набор длительностей в заданных границах.
 *
 * Показываем круглые значения, а не каждую секунду: выбирать из
 * сотни пунктов невозможно.
 */
export function stepsBetween(min, max) {
    if (!min || !max) {
        return [];
    }

    const candidates = [5, 10, 15, 20, 30, 45, 60, 90, 120, 180, 240, 300, 600];

    return candidates
        .filter((value) => value >= min && value <= max)
        .map((value) => ({ key: String(value), label: formatSeconds(value) }));
}
