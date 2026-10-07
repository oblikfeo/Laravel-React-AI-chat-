/**
 * Аватар персонажа.
 *
 * Если картинки нет, показываем первую букву имени на цветном фоне:
 * пустой серый круг выглядел бы как ошибка загрузки. Цвет берётся из
 * имени, поэтому у одного персонажа он всегда один и тот же.
 */
const TINTS = [
    'from-violet-500 to-sky-400',
    'from-sky-500 to-emerald-400',
    'from-rose-500 to-amber-400',
    'from-indigo-500 to-fuchsia-400',
    'from-emerald-500 to-teal-300',
    'from-amber-500 to-rose-400',
];

function tintOf(name) {
    let sum = 0;

    for (const char of name ?? '') {
        sum += char.codePointAt(0);
    }

    return TINTS[sum % TINTS.length];
}

export default function CharacterAvatar({
    name,
    src,
    className = 'h-12 w-12 rounded-2xl text-lg',
}) {
    if (src) {
        return (
            <img
                src={src}
                alt={name}
                loading="lazy"
                decoding="async"
                className={`shrink-0 object-cover ${className}`}
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            // Буква на цветном фоне белая в обеих темах.
            className={`keep-light flex shrink-0 items-center justify-center bg-gradient-to-br font-semibold ${tintOf(
                name,
            )} ${className}`}
        >
            {(name ?? '?').trim().charAt(0).toUpperCase() || '?'}
        </span>
    );
}
