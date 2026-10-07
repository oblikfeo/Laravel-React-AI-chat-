import { useState } from 'react';

/**
 * Работа в ленте.
 *
 * Размер плитки задаёт раскладка: лента складывается из квадратов,
 * вытянутых и широких кусков, как мозаика. Внутри плитки работа
 * обрезается по центру, поэтому её форма на раскладку не влияет.
 */
export default function FeedTile({ item, span }) {
    const [loaded, setLoaded] = useState(false);

    return (
        <button
            type="button"
            onClick={span.onOpen}
            style={{ gridColumn: span.column, gridRow: span.row }}
            className="group relative overflow-hidden rounded-xl border border-white/[0.08] bg-slate-950/50 text-left transition hover:z-10 hover:border-white/25 hover:shadow-xl hover:shadow-black/40"
        >
            {/* Место держим заранее: иначе лента прыгает, пока
                картинки подгружаются. */}
            {!loaded && (
                <span className="absolute inset-0 animate-pulse bg-white/[0.04]" />
            )}

            <img
                src={item.thumbnail ?? item.url}
                alt={item.prompt}
                loading="lazy"
                decoding="async"
                onLoad={() => setLoaded(true)}
                className={`h-full w-full object-cover transition duration-500 group-hover:scale-[1.04] ${
                    loaded ? 'opacity-100' : 'opacity-0'
                }`}
            />

            <span className="on-media pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent p-3 opacity-0 transition group-hover:opacity-100">
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
