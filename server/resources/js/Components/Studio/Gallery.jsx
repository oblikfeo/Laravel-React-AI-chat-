import { useEffect, useState } from 'react';
import { Wand2, Sparkles, Images, Music, Video, Plus } from 'lucide-react';
import AudioCard from '@/Components/Studio/AudioCard';
import VideoCard from '@/Components/Studio/VideoCard';
import ImageCard from '@/Components/Studio/ImageCard';
import ImageViewer from '@/Components/Studio/ImageViewer';

/** Как подписана галерея каждого раздела. */
const LOOKS = {
    image: {
        icon: Images,
        title: 'Images',
        one: 'image',
        many: 'images',
        empty: 'Images you create will appear here.',
        action: 'Create an image',
    },
    edit: {
        icon: Wand2,
        title: 'Edits',
        one: 'edit',
        many: 'edits',
        empty: 'Edited, combined and upscaled images will appear here.',
        action: 'Edit an image',
    },
    audio: {
        icon: Music,
        title: 'Audio',
        one: 'track',
        many: 'tracks',
        empty: 'Music, voiceovers and sound effects will appear here.',
        action: 'Create audio',
    },
    video: {
        icon: Video,
        title: 'Videos',
        one: 'video',
        many: 'videos',
        empty: 'Videos you create will appear here.',
        action: 'Create a video',
    },
};

/** Виды звука: в галерее их можно смотреть вместе и по отдельности. */
const AUDIO_KINDS = [
    { key: 'all', label: 'All' },
    { key: 'music', label: 'Music' },
    { key: 'speech', label: 'Voice' },
    { key: 'voice_change', label: 'Voice Changer' },
    { key: 'effect', label: 'Sound Effects' },
];

/**
 * Галерея раздела: готовые работы.
 *
 * Какую галерею показывать, выбирает переключатель разделов наверху
 * страницы. Только что досчитанные работы подсвечены ярлыком «New».
 */
export default function Gallery({ section, items, highlighted = [], onCreate, onReuse, onEdit }) {
    const [preview, setPreview] = useState(null);
    const [audioKind, setAudioKind] = useState('all');

    const look = LOOKS[section] ?? LOOKS.image;
    const audio = section === 'audio';

    // Свежая работа видна при любом выбранном виде звука.
    useEffect(() => {
        if (highlighted.length) {
            setAudioKind('all');
        }
    }, [highlighted.length]);

    const shown =
        audio && audioKind !== 'all' ? items.filter((item) => item.operation === audioKind) : items;

    const columns = audio
        ? 'lg:grid-cols-2'
        : section === 'video'
          ? 'sm:grid-cols-2'
          : 'sm:grid-cols-2 xl:grid-cols-3';

    return (
        <div className="mt-6">
            <div className="flex flex-wrap items-center gap-3 rounded-2xl border border-white/[0.09] bg-slate-950/45 px-4 py-3 backdrop-blur-md">
                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/[0.07] text-white/60">
                    <look.icon className="h-[18px] w-[18px]" strokeWidth={1.75} />
                </span>

                <span className="min-w-0 flex-1">
                    <span className="block text-[15px] font-medium text-white">{look.title}</span>
                    <span className="block text-[12px] text-white/40">
                        {items.length
                            ? `${items.length} ${items.length === 1 ? look.one : look.many} saved`
                            : 'Nothing saved yet'}
                    </span>
                </span>

                <button
                    type="button"
                    onClick={onCreate}
                    className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-4 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                >
                    <Plus className="h-4 w-4" strokeWidth={1.75} />
                    {look.action}
                </button>
            </div>

            {audio && items.length > 0 && (
                <div className="mt-3 flex w-fit max-w-full flex-wrap gap-1 rounded-2xl border border-white/[0.09] bg-slate-950/45 p-1 backdrop-blur-md">
                    {AUDIO_KINDS.map(({ key, label }) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setAudioKind(key)}
                            className={`h-8 rounded-xl px-3.5 text-[13px] transition ${
                                audioKind === key
                                    ? 'bg-white/10 font-medium text-white ring-1 ring-white/15'
                                    : 'text-white/55 hover:text-white'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>
            )}

            <div className="mt-4">
                {shown.length ? (
                    <div className={`grid gap-3 ${columns}`}>
                        {shown.map((item) => {
                            const fresh = highlighted.includes(item.id);

                            return (
                                <div key={item.id} className="relative">
                                    {item.kind === 'video' ? (
                                        <VideoCard item={item} fresh={fresh} />
                                    ) : item.kind === 'audio' ? (
                                        <AudioCard item={item} fresh={fresh} />
                                    ) : (
                                        <ImageCard
                                            item={item}
                                            fresh={fresh}
                                            onOpen={setPreview}
                                            onReuse={onReuse}
                                            onEdit={onEdit}
                                        />
                                    )}

                                    {fresh && (
                                        <span className="keep-light pointer-events-none absolute -top-2 left-3 z-10 flex h-5 items-center gap-1 rounded-full bg-sky-400 px-2 text-[11px] font-semibold text-slate-950 shadow-lg shadow-sky-500/30">
                                            <Sparkles className="h-3 w-3" strokeWidth={2.25} />
                                            New
                                        </span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="rounded-2xl border border-white/[0.07] bg-slate-950/40 px-6 py-14 text-center">
                        <look.icon className="mx-auto h-7 w-7 text-white/20" strokeWidth={1.5} />
                        <p className="mt-3 text-[15px] text-white/60">Nothing here yet</p>
                        <p className="mt-1 text-sm text-white/35">{look.empty}</p>
                    </div>
                )}
            </div>

            {preview && <ImageViewer item={preview} onClose={() => setPreview(null)} />}
        </div>
    );
}
