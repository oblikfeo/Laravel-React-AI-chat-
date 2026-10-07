import { router } from '@inertiajs/react';
import { MessageSquare, Pencil, Globe, Lock } from 'lucide-react';
import CharacterAvatar from '@/Components/Characters/CharacterAvatar';

/**
 * Карточка персонажа.
 *
 * Нажатие открывает диалог с ним — тот же, что был в прошлый раз:
 * переписка с персонажем одна и продолжается с того же места.
 */
export default function CharacterCard({ character, onEdit }) {
    const open = () =>
        router.post(`/characters/${character.id}/chat`, {}, { showProgress: true });

    return (
        <div
            role="button"
            tabIndex={0}
            onClick={open}
            onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    open();
                }
            }}
            className="group flex cursor-pointer flex-col rounded-2xl border border-white/[0.08] bg-slate-950/55 p-4 text-left backdrop-blur-xl transition hover:border-white/25 hover:bg-slate-950/70"
        >
            <div className="flex gap-3.5">
                <CharacterAvatar
                    name={character.name}
                    src={character.avatar}
                    className="h-[72px] w-[72px] rounded-2xl text-2xl"
                />

                <div className="min-w-0 flex-1">
                    <div className="flex items-start gap-2">
                        <p className="min-w-0 flex-1 truncate text-[16px] font-semibold text-white">
                            {character.name}
                        </p>

                        {onEdit && (
                            <button
                                type="button"
                                onClick={(event) => {
                                    event.stopPropagation();
                                    onEdit(character);
                                }}
                                aria-label={`Edit ${character.name}`}
                                title="Edit"
                                className="-mr-1 -mt-1 shrink-0 rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white"
                            >
                                <Pencil className="h-4 w-4" strokeWidth={1.75} />
                            </button>
                        )}
                    </div>

                    {character.author && !character.isMine && (
                        <p className="truncate text-[12px] text-white/40">
                            by {character.author}
                        </p>
                    )}

                    <p className="mt-1 line-clamp-2 text-[13px] leading-snug text-white/60">
                        {character.description || 'No description yet.'}
                    </p>
                </div>
            </div>

            {character.tags?.length > 0 && (
                <div className="mt-3 flex flex-wrap gap-1.5">
                    {character.tags.map((tag) => (
                        <span
                            key={tag}
                            className="rounded-full border border-white/[0.1] px-2 py-0.5 text-[11px] text-white/55"
                        >
                            {tag}
                        </span>
                    ))}
                </div>
            )}

            <div className="mt-auto flex items-center gap-3 pt-3.5 text-[12px] text-white/40">
                {typeof character.chats === 'number' && (
                    <span className="flex items-center gap-1.5">
                        <MessageSquare className="h-3.5 w-3.5" strokeWidth={1.75} />
                        {character.chats}
                    </span>
                )}

                {character.isMine && (
                    <span className="flex items-center gap-1.5">
                        {character.isPublic ? (
                            <Globe className="h-3.5 w-3.5" strokeWidth={1.75} />
                        ) : (
                            <Lock className="h-3.5 w-3.5" strokeWidth={1.75} />
                        )}
                        {character.isPublic ? 'Public' : 'Private'}
                    </span>
                )}

                <span className="ml-auto rounded-full border border-white/[0.14] px-3 py-1 text-[12px] font-medium text-white/80 transition group-hover:bg-white/10 group-hover:text-white">
                    Chat
                </span>
            </div>
        </div>
    );
}
