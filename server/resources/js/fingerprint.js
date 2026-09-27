/**
 * Отпечаток браузера.
 *
 * Нужен, чтобы узнать посетителя без учётной записи, если он очистил
 * куки. Набор признаков устойчив у одного человека и редко совпадает
 * у разных, но это подсказка, а не удостоверение личности: на нём
 * держатся только дневные лимиты, ничего более.
 *
 * Ничего личного не собираем: ни имени, ни истории, ни точного
 * местоположения. Только настройки самого браузера.
 */
function collect() {
    const screenSize = `${window.screen.width}x${window.screen.height}x${window.screen.colorDepth}`;
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone ?? '';
    const languages = (navigator.languages ?? [navigator.language]).join(',');
    const cores = navigator.hardwareConcurrency ?? 0;
    const memory = navigator.deviceMemory ?? 0;
    const touch = navigator.maxTouchPoints ?? 0;

    return [
        screenSize,
        timezone,
        languages,
        cores,
        memory,
        touch,
        navigator.platform ?? '',
    ].join('|');
}

/**
 * Отпечаток уходит заголовком с каждым запросом.
 *
 * Значение не хранится: пересчитывается на месте, поэтому его нельзя
 * подделать, просто подправив хранилище браузера.
 */
export function guestFingerprint() {
    try {
        return collect();
    } catch {
        // Строгие настройки приватности могут закрыть часть свойств.
        // Тогда остаётся только кука — это нормально.
        return '';
    }
}
