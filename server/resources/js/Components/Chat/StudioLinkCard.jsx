import { Link } from '@inertiajs/react';
import { Music, Mic, AudioWaveform, Clapperboard, ImagePlay, ArrowRight } from 'lucide-react';

/** Что сказать о каждом виде работы. */
const LOOKS = {
    music: { icon: Music, title: 'Create this track in the Studio', button: 'Open Audio Studio' },
    speech: { icon: Mic, title: 'Voice this text in the Studio', button: 'Open Audio Studio' },
    effect: { icon: AudioWaveform, title: 'Create this sound in the Studio', button: 'Open Audio Studio' },
    video: { icon: Clapperboard, title: 'Create this video in the Studio', button: 'Open Video Studio' },
    animate: { icon: ImagePlay, title: 'Animate this picture in the Studio', button: 'Open Video Studio' },
};

/**
 * Карточка «сделать это в Студии».
 *
 * Звук и видео чат не делает: у них свои настройки, а ролик снимается
 * минутами. Карточка открывает нужный раздел, и просьба там уже
 * вписана в форму — остаётся нажать Create.
 */
export default function StudioLinkCard({ link }) {
    const look = LOOKS[link.operation] ?? LOOKS.music;

    return (
        <div className="relative w-full max-w-[520px] overflow-hidden rounded-2xl border border-sky-300/25 bg-gradient-to-br from-sky-400/[0.12] via-white/[0.04] to-violet-400/[0.10] p-4">
            <div className="flex items-start gap-3.5">
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10 text-sky-200 ring-1 ring-white/15">
                    <look.icon className="h-5 w-5" strokeWidth={1.75} />
                </span>

                <span className="min-w-0 flex-1">
                    <span className="block text-[15px] font-medium text-white">{look.title}</span>
                    <span className="mt-0.5 block text-[13px] leading-snug text-white/55">
                        Your request is already filled in there — just press Create.
                    </span>
                </span>
            </div>

            {link.prompt && (
                <p className="mt-3 line-clamp-2 rounded-xl border border-white/[0.08] bg-black/20 px-3 py-2 text-[13px] italic leading-snug text-white/70">
                    “{link.prompt}”
                </p>
            )}

            <Link
                href={link.url}
                className="mt-3 inline-flex h-10 items-center gap-2 rounded-full bg-white px-5 text-[14px] font-semibold text-black transition hover:bg-white/90"
            >
                {look.button}
                <ArrowRight className="h-4 w-4" strokeWidth={2} />
            </Link>
        </div>
    );
}
