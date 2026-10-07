import { useEffect, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Search, Users, Compass, UserRound, X } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';
import CharacterCard from '@/Components/Characters/CharacterCard';
import CharacterDialog from '@/Components/Characters/CharacterDialog';

/**
 * Раздел персонажей: общий каталог и свои.
 */
export default function CharactersIndex({
    tab,
    filters,
    catalog,
    popularTags,
    mine,
    form,
    studioWorks,
}) {
    const { auth } = usePage().props;
    const signedIn = Boolean(auth?.user);

    const catalogItems = catalog ?? [];
    const myItems = mine ?? [];
    const works = studioWorks ?? [];

    // Окно: null — закрыто, 'new' — создание, иначе правка персонажа.
    const [editing, setEditing] = useState(null);
    const [search, setSearch] = useState(filters.q ?? '');
    const firstRun = useRef(true);

    // Ссылка «создать» из ленты и других мест открывает окно сразу.
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);

        if (params.get('create') === '1' && signedIn) {
            setEditing('new');
        }
    }, [signedIn]);

    // Поиск уходит на сервер с задержкой, а не на каждую букву.
    useEffect(() => {
        if (firstRun.current) {
            firstRun.current = false;

            return undefined;
        }

        const timer = setTimeout(() => {
            visit({ q: search || undefined, tag: filters.tag || undefined });
        }, 350);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const visit = (params) =>
        router.get('/characters', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            showProgress: false,
        });

    const switchTab = (next) =>
        router.get(
            '/characters',
            next === 'mine' ? { tab: 'mine' } : {},
            { preserveState: true, showProgress: false },
        );

    const create = () => {
        if (!signedIn) {
            router.visit('/auth?mode=register');

            return;
        }

        setEditing('new');
    };

    const pickTag = (tag) =>
        visit({
            q: search || undefined,
            tag: filters.tag === tag ? undefined : tag,
        });

    const filtered = Boolean(filters.q || filters.tag);

    return (
        <>
            <Head title="Characters — Uncensia" />

            <div className="flex-1 overflow-y-auto scrollbar-thin">
                <div className="mx-auto w-full max-w-[1180px] px-4 py-6 sm:px-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-light tracking-tight text-white">
                                Characters
                            </h1>
                            <p className="mt-1 text-sm text-white/45">
                                Talk to characters people made, or create your own
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={create}
                            disabled={signedIn && !form.canCreateMore}
                            title={
                                signedIn && !form.canCreateMore
                                    ? 'You have reached the limit of characters'
                                    : undefined
                            }
                            className="flex h-10 items-center gap-2 rounded-full bg-white px-5 text-[14px] font-semibold text-black transition hover:bg-white/90 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Plus className="h-4 w-4" strokeWidth={2.25} />
                            Create character
                        </button>
                    </div>

                    <div className="mt-6 flex flex-wrap items-center gap-3">
                        {signedIn && (
                            <div className="flex items-center gap-1 rounded-full border border-white/[0.12] bg-slate-950/50 p-1 backdrop-blur-xl">
                                <TabButton
                                    active={tab === 'explore'}
                                    onClick={() => switchTab('explore')}
                                    icon={Compass}
                                    label="Explore"
                                />
                                <TabButton
                                    active={tab === 'mine'}
                                    onClick={() => switchTab('mine')}
                                    icon={UserRound}
                                    label="My characters"
                                    count={myItems.length}
                                />
                            </div>
                        )}

                        {tab === 'explore' && (
                            <label className="relative ml-auto w-full sm:w-[280px]">
                                <Search
                                    className="pointer-events-none absolute left-3.5 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-white/40"
                                    strokeWidth={1.75}
                                />
                                <input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search characters…"
                                    aria-label="Search characters"
                                    className="h-10 w-full rounded-full border border-white/[0.12] bg-slate-950/55 pl-10 pr-9 text-[14px] text-white outline-none backdrop-blur-xl transition placeholder:text-white/35 focus:border-white/25 focus:ring-0"
                                />
                                {search && (
                                    <button
                                        type="button"
                                        onClick={() => setSearch('')}
                                        aria-label="Clear search"
                                        className="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full p-1 text-white/45 transition hover:bg-white/10 hover:text-white"
                                    >
                                        <X className="h-3.5 w-3.5" strokeWidth={2} />
                                    </button>
                                )}
                            </label>
                        )}
                    </div>

                    {tab === 'explore' && popularTags.length > 0 && (
                        <div className="mt-3 flex flex-wrap gap-1.5">
                            {popularTags.map((tag) => (
                                <button
                                    key={tag}
                                    type="button"
                                    onClick={() => pickTag(tag)}
                                    className={`rounded-full border px-3 py-1 text-[12px] backdrop-blur-xl transition ${
                                        filters.tag === tag
                                            ? 'border-white/30 bg-white/10 font-medium text-white'
                                            : 'border-white/[0.12] bg-slate-950/45 text-white/60 hover:text-white'
                                    }`}
                                >
                                    {tag}
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="mt-5">
                        {tab === 'explore' &&
                            (catalogItems.length ? (
                                <Grid>
                                    {catalogItems.map((character) => (
                                        <CharacterCard
                                            key={character.id}
                                            character={character}
                                        />
                                    ))}
                                </Grid>
                            ) : (
                                <Empty
                                    icon={Users}
                                    title={
                                        filtered
                                            ? 'Nothing matches'
                                            : 'No public characters yet'
                                    }
                                    text={
                                        filtered
                                            ? 'Try another word or clear the filter.'
                                            : 'Be the first: create a character and make it public.'
                                    }
                                    action={filtered ? null : create}
                                />
                            ))}

                        {tab === 'mine' &&
                            (myItems.length ? (
                                <Grid>
                                    {myItems.map((character) => (
                                        <CharacterCard
                                            key={character.id}
                                            character={character}
                                            onEdit={setEditing}
                                        />
                                    ))}
                                </Grid>
                            ) : (
                                <Empty
                                    icon={UserRound}
                                    title="You have no characters yet"
                                    text="Give one a name and a personality — it takes a minute."
                                    action={create}
                                />
                            ))}
                    </div>
                </div>
            </div>

            {editing && (
                <CharacterDialog
                    // Ключ пересоздаёт окно при смене персонажа: иначе
                    // в форме остались бы поля предыдущего.
                    key={editing === 'new' ? 'new' : editing.id}
                    character={editing === 'new' ? null : editing}
                    form={form}
                    studioWorks={works}
                    onClose={() => setEditing(null)}
                />
            )}
        </>
    );
}

function Grid({ children }) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{children}</div>
    );
}

function TabButton({ active, onClick, icon: Icon, label, count }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex h-9 items-center gap-2 rounded-full px-4 text-sm transition ${
                active
                    ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                    : 'text-white/55 hover:text-white'
            }`}
        >
            <Icon className="h-4 w-4" strokeWidth={1.75} />
            {label}
            {typeof count === 'number' && count > 0 && (
                <span className="text-[12px] text-white/45">{count}</span>
            )}
        </button>
    );
}

function Empty({ icon: Icon, title, text, action }) {
    return (
        <div className="rounded-2xl border border-white/[0.07] bg-slate-950/45 px-6 py-14 text-center backdrop-blur-md">
            <Icon className="mx-auto h-8 w-8 text-white/25" strokeWidth={1.5} />
            <p className="mt-4 text-[15px] text-white/70">{title}</p>
            <p className="mt-1 text-sm text-white/40">{text}</p>

            {action && (
                <button
                    type="button"
                    onClick={action}
                    className="mx-auto mt-5 flex h-10 items-center gap-2 rounded-full bg-white px-5 text-[14px] font-semibold text-black transition hover:bg-white/90"
                >
                    <Plus className="h-4 w-4" strokeWidth={2.25} />
                    Create character
                </button>
            )}
        </div>
    );
}

CharactersIndex.layout = (page) => <MainLayout>{page}</MainLayout>;
