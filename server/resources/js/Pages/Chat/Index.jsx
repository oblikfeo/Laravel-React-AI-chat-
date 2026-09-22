import { Head, Link, router } from '@inertiajs/react';
import { MessageSquare, Trash2 } from 'lucide-react';
import MainLayout from '@/Layouts/MainLayout';

export default function ChatIndex({ chats }) {
    const remove = (id) => {
        router.delete(`/chats/${id}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Chats — Uncensia" />

            <div className="flex-1 overflow-y-auto scrollbar-thin">
                <div className="mx-auto w-full max-w-[760px] px-4 py-8 sm:px-6">
                    <h1 className="text-outline text-2xl font-light tracking-tight text-white">
                        Your chats
                    </h1>

                    {chats.length === 0 ? (
                        <p className="mt-6 rounded-2xl border border-white/[0.08] bg-slate-950/70 px-4 py-3.5 text-[15px] text-white/60 backdrop-blur-xl">
                            Nothing here yet. Start a conversation from the home
                            page.
                        </p>
                    ) : (
                        <div className="mt-6 space-y-2">
                            {chats.map((chat) => (
                                <div
                                    key={chat.id}
                                    className="group flex items-center gap-3 rounded-2xl border border-white/[0.08] bg-slate-950/70 px-4 py-3.5 backdrop-blur-xl transition hover:bg-slate-950/85"
                                >
                                    <MessageSquare
                                        className="h-5 w-5 shrink-0 text-white/45"
                                        strokeWidth={1.75}
                                    />

                                    <Link
                                        href={`/chats/${chat.id}`}
                                        className="min-w-0 flex-1 truncate text-[15px] text-white"
                                    >
                                        {chat.title}
                                    </Link>

                                    <button
                                        type="button"
                                        onClick={() => remove(chat.id)}
                                        aria-label="Delete chat"
                                        className="shrink-0 rounded-lg p-2 text-white/40 opacity-0 transition hover:bg-white/10 hover:text-red-400 group-hover:opacity-100"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

// Постоянный макет: не пересоздаётся при переходах,
// поэтому боковое меню сохраняет состояние.
ChatIndex.layout = (page) => <MainLayout>{page}</MainLayout>;
