import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Sparkles, Images } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import FeedTile from '@/Components/Feed/FeedTile';
import FeedViewer from '@/Components/Feed/FeedViewer';

/**
 * Общая лента работ.
 *
 * Кладём плитки в колонки по высоте, а не ровной сеткой: у работ
 * разные пропорции, и сетка из квадратов обрезала бы половину.
 */
export default function FeedIndex({ works }) {
    const [open, setOpen] = useState(null);
    const [columns, setColumns] = useState(() => columnCount());
    const [items, setItems] = useState(works.data);
    const [loading, setLoading] = useState(false);
    const tailRef = useRef(null);
    const nextRef = useRef(works.links?.next ?? null);

    // Пришла новая страница — дописываем к тому, что уже показано.
    useEffect(() => {
        setItems((previous) => {
            const known = new Set(previous.map((item) => item.id));
            const fresh = works.data.filter((item) => !known.has(item.id));

            return fresh.length ? [...previous, ...fresh] : previous;
        });

        nextRef.current = works.links?.next ?? null;
        setLoading(false);
    }, [works]);

    useEffect(() => {
        const onResize = () => setColumns(columnCount());

        window.addEventListener('resize', onResize);

        return () => window.removeEventListener('resize', onResize);
    }, []);

    // Докладываем, когда человек долистал до конца: кнопка «ещё»
    // в ленте только мешает.
    useEffect(() => {
        const node = tailRef.current;

        if (!node) {
            return;
        }

        const watcher = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting || loading || !nextRef.current) {
                    return;
                }

                setLoading(true);

                router.visit(nextRef.current, {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['works'],
                    showProgress: false,
                });
            },
            { rootMargin: '600px' },
        );

        watcher.observe(node);

        return () => watcher.disconnect();
    }, [loading]);

    return (
        <>
            <Head title="Feed — Uncensia" />

            <div className="flex-1 overflow-y-auto scrollbar-thin">
                <div className="mx-auto w-full max-w-[1280px] px-4 py-6 sm:px-6">
                    <div className="mb-6">
                        <h1 className="text-2xl font-light tracking-tight text-white">
                            Feed
                        </h1>
                        <p className="mt-1 text-sm text-white/40">
                            What people are making right now
                        </p>
                    </div>

                    {items.length ? (
                        <div className="flex gap-3">
                            {spread(items, columns).map((column, index) => (
                                <div
                                    key={index}
                                    className="flex min-w-0 flex-1 flex-col gap-3"
                                >
                                    {column.map((item) => (
                                        <FeedTile
                                            key={item.id}
                                            item={item}
                                            onOpen={() => setOpen(item)}
                                        />
                                    ))}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <Empty />
                    )}

                    <div ref={tailRef} className="h-12" />

                    {loading && (
                        <p className="pb-6 text-center text-[13px] text-white/35">
                            Loading more…
                        </p>
                    )}
                </div>
            </div>

            {open && (
                <FeedViewer item={open} onClose={() => setOpen(null)} />
            )}
        </>
    );
}

function Empty() {
    return (
        <div className="rounded-2xl border border-white/[0.07] bg-slate-950/40 px-6 py-16 text-center backdrop-blur-md">
            <Images className="mx-auto h-8 w-8 text-white/20" strokeWidth={1.5} />

            <p className="mt-4 text-[15px] text-white/60">Nothing here yet</p>
            <p className="mt-1 text-sm text-white/35">
                Work people share will show up here.
            </p>

            <button
                type="button"
                onClick={() => router.visit('/studio')}
                className="mx-auto mt-5 flex h-10 items-center gap-2 rounded-full bg-white px-5 text-[14px] font-semibold text-black transition hover:bg-white/90"
            >
                <Sparkles className="h-4 w-4" strokeWidth={2} />
                Create something
            </button>
        </div>
    );
}

/** Сколько колонок помещается по ширине окна. */
function columnCount() {
    if (typeof window === 'undefined') {
        return 3;
    }

    const width = window.innerWidth;

    if (width < 640) {
        return 2;
    }

    if (width < 1024) {
        return 3;
    }

    return width < 1440 ? 4 : 5;
}

/**
 * Раскладывает работы по колонкам.
 *
 * Каждая следующая плитка уходит в самую короткую колонку: так низ
 * ленты получается ровным, а не лесенкой.
 */
function spread(items, columns) {
    const buckets = Array.from({ length: columns }, () => []);
    const heights = Array(columns).fill(0);

    items.forEach((item) => {
        let shortest = 0;

        heights.forEach((height, index) => {
            if (height < heights[shortest]) {
                shortest = index;
            }
        });

        buckets[shortest].push(item);

        // Высота в долях ширины колонки: точные размеры не нужны,
        // важно лишь соотношение.
        heights[shortest] += item.width && item.height
            ? item.height / item.width
            : 1;
    });

    return buckets;
}

FeedIndex.layout = (page) => <MainLayout>{page}</MainLayout>;
