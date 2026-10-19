/**
 * Кнопка действия в карточке работы: значок и подпись.
 *
 * Со ссылкой — скачивание, без неё — обычная кнопка.
 */
export default function CardAction({ label, icon: Icon, onClick, href, danger = false }) {
    const className = `flex h-8 items-center gap-1.5 rounded-lg border border-white/[0.1] bg-white/[0.03] px-2.5 text-[12px] transition hover:bg-white/[0.12] ${
        danger
            ? 'text-white/55 hover:border-rose-300/30 hover:text-rose-300'
            : 'text-white/70 hover:text-white'
    }`;

    const inner = (
        <>
            <Icon className="h-3.5 w-3.5 shrink-0" strokeWidth={1.75} />
            <span className="truncate">{label}</span>
        </>
    );

    if (href) {
        return (
            <a href={href} aria-label={label} className={className}>
                {inner}
            </a>
        );
    }

    return (
        <button type="button" onClick={onClick} aria-label={label} className={className}>
            {inner}
        </button>
    );
}
