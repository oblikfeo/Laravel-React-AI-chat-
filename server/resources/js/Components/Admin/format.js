/**
 * Деньги в админке.
 *
 * Платежи лежат в базе в копейках (центах) и в своей валюте, расходы
 * на провайдеров — в долларах себестоимости. Это разные деньги, и
 * подписаны они по-разному.
 */

/** Сумма платежа: `1 800.00 RUB`. */
export function money(minor, currency) {
    const value = (Number(minor) || 0) / 100;

    return `${value.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ${currency ?? ''}`.trim();
}

/** Несколько сумм в разных валютах через запятую, или прочерк. */
export function moneyList(lines) {
    if (!lines?.length) {
        return '—';
    }

    return lines.map((line) => money(line.amount, line.currency)).join(', ');
}

/** Себестоимость: `$1.24`, доли цента — `$0.0031`. */
export function usd(value) {
    const number = Number(value) || 0;

    if (number === 0) {
        return '$0';
    }

    return number < 0.01 ? `$${number.toFixed(4)}` : `$${number.toFixed(2)}`;
}

/** Целое с разделителями: `12,480`. */
export function count(value) {
    return (Number(value) || 0).toLocaleString('en-US');
}

/** Названия разделов журнала расходов. */
export const CATEGORY_LABELS = {
    chat: 'Chat',
    image: 'Images',
    edit: 'Image edits',
    audio: 'Audio',
    video: 'Video',
    voice: 'Voice input',
    service: 'Service calls',
};
