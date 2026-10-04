import { useState } from 'react';

/**
 * Работа в ленте.
 *
 * Высота плитки задаётся пропорциями самой работы: у людей разные
 * форматы, и подгонять их под квадрат — значит обрезать половину.
 */
export default function FeedTile({ item, onOpen }) {
    const [loaded, setLoaded] = useState(false);

    const ratio = item.width && item.height
        ? `${item.width} / ${item.height}`
        : '1 / 1';

    return (
        <button
            type="button"
            onClick={onOpen}
            style={{ aspectRatio: ratio }}
            className="group relative w-full overflow-hidden rounded-xl border border-white/[0.08] bg-slate-950/50 text-left transition hover:border-white/25"
        >
            {/* Место под работу держим заранее: иначе лента прыгает,
                пока картинки подгружаются. */}
            {!loaded && (
                <span className="absolute inset-0 animate-pulse bg-white/[0.04]" />
            )}

            <img
                src={item.thumbnail ?? item.url}
                alt={item.prompt}
                loading="lazy"
                decoding="async"
                onLoad={() => setLoaded(true)}
                className={`h-full w-full object-cover transition duration-500 group-hover:scale-[1.03] ${
                    loaded ? 'opacity-100' : 'opacity-0'
                }`}
            />

            <span className="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent p-3 opacity-0 transition group-hover:opacity-100">
                <span className="line-clamp-2 block text-[12px] leading-snug text-white/90">
                    {item.prompt}
                </span>

                {item.author && (
                    <span className="mt-1 block text-[11px] text-white/45">
                        {item.author}
                    </span>
                )}
            </span>
        </button>
    );
}
