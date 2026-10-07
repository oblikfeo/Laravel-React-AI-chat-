import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Sparkles, Images, Users, Plus } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import FeedTile from '@/Components/Feed/FeedTile';
import FeedViewer from '@/Components/Feed/FeedViewer';
import CharacterCard from '@/Components/Characters/CharacterCard';

/** Что показывает лента. */
const TABS = [
    { key: 'images', label: 'Images', icon: Images },
    { key: 'characters', label: 'Characters', icon: Users },
];

/**
 * Узор раскладки.
 *
 * Повторяется каждые двенадцать работ: крупные, широкие, высокие и
 * обычные плитки чередуются так, чтобы в сетке не оставалось дыр.
 * Числа — сколько колонок и строк занимает плитка.
 */
const PATTERN = [
    { w: 2, h: 2 },
    { w: 1, h: 1 },
    { w: 1, h: 1 },
    { w: 1, h: 2 },
    { w: 2, h: 1 },
    { w: 1, h: 1 },
    { w: 1, h: 1 },
    { w: 1, h: 2 },
    { w: 2, h: 2 },
    { w: 1, h: 1 },
    { w: 1, h: 1 },
    { w: 2, h: 1 },
];

/**
 * Общая лента работ.
 *
 * Плитки разного размера складываются как мозаика: ровная сетка из
 * одинаковых квадратов выглядит каталогом, а не лентой.
 */
export default function FeedIndex({ works, tab = 'images', characters }) {
    const people = characters ?? [];

    const switchTab = (next) =>
        router.get(
            '/feed',
            next === 'characters' ? { tab: 'characters' } : {},
            { preserveState: true, showProgress: false },
        );

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
                <div className="mx-auto w-full max-w-[1320px] px-4 py-6 sm:px-6">
                    <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-light tracking-tight text-ink">
                                Feed
                            </h1>
                            <p className="mt-1 text-sm text-ink-faint">
                                What people are making right now
                            </p>
                        </div>

                        <div className="flex items-center gap-1 rounded-full border border-white/[0.12] bg-slate-950/50 p-1 backdrop-blur-xl">
                            {TABS.map(({ key, label, icon: Icon }) => (
                                <button
                                    key={key}
                                    type="button"
                                    onClick={() => switchTab(key)}
                                    className={`flex h-9 items-center gap-2 rounded-full px-4 text-sm transition ${
                                        tab === key
                                            ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                                            : 'text-white/55 hover:text-white'
                                    }`}
                                >
                                    <Icon className="h-4 w-4" strokeWidth={1.75} />
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {tab === 'characters' ? (
                        people.length ? (
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {people.map((character) => (
                                    <CharacterCard
                                        key={character.id}
                                        character={character}
                                    />
                                ))}
                            </div>
                        ) : (
                            <NoCharacters />
                        )
                    ) : items.length ? (
                        <div
                            className="grid gap-3"
                            style={{
                                gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))`,
                                // Строка — половина колонки: из таких
                                // кирпичиков собираются и квадраты, и
                                // вытянутые плитки.
                                gridAutoRows: 'minmax(0, 11rem)',
                                gridAutoFlow: 'dense',
                            }}
                        >
                            {items.map((item, index) => (
                                <FeedTile
                                    key={item.id}
                                    item={item}
                                    span={{
                                        ...sizeFor(index, columns),
                                        onOpen: () => setOpen(item),
                                    }}
                                />
                            ))}
                        </div>
                    ) : (
                        <Empty />
                    )}

                    <div ref={tailRef} className="h-12" />

                    {loading && (
                        <p className="pb-6 text-center text-[13px] text-ink-faint">
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

function NoCharacters() {
    return (
        <div className="rounded-2xl border border-line bg-surface px-6 py-16 text-center backdrop-blur-md">
            <Users className="mx-auto h-8 w-8 text-ink-faint" strokeWidth={1.5} />

            <p className="mt-4 text-[15px] text-ink-soft">No public characters yet</p>
            <p className="mt-1 text-sm text-ink-faint">
                Characters people share will show up here.
            </p>

            <button
                type="button"
                onClick={() => router.visit('/characters?create=1')}
                className="mx-auto mt-5 flex h-10 items-center gap-2 rounded-full bg-accent px-5 text-[14px] font-semibold text-accent-ink transition hover:opacity-90"
            >
                <Plus className="h-4 w-4" strokeWidth={2} />
                Create a character
            </button>
        </div>
    );
}

function Empty() {
    return (
        <div className="rounded-2xl border border-line bg-surface px-6 py-16 text-center backdrop-blur-md">
            <Images className="mx-auto h-8 w-8 text-ink-faint" strokeWidth={1.5} />

            <p className="mt-4 text-[15px] text-ink-soft">Nothing here yet</p>
            <p className="mt-1 text-sm text-ink-faint">
                Work people share will show up here.
            </p>

            <button
                type="button"
                onClick={() => router.visit('/studio')}
                className="mx-auto mt-5 flex h-10 items-center gap-2 rounded-full bg-accent px-5 text-[14px] font-semibold text-accent-ink transition hover:opacity-90"
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
        return 4;
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
 * Размер плитки по её месту в ленте.
 *
 * На узких экранах крупные плитки не помещаются, поэтому широкие
 * куски ужимаются до ширины сетки.
 */
function sizeFor(index, columns) {
    const shape = PATTERN[index % PATTERN.length];
    const width = Math.min(shape.w, columns);

    return {
        column: `span ${width}`,
        row: `span ${shape.h}`,
    };
}

FeedIndex.layout = (page) => <MainLayout>{page}</MainLayout>;
