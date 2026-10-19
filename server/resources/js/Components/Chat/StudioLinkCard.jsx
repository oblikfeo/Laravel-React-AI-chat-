import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, Crown, Play, Sparkles } from 'lucide-react';
import { usePlans } from '@/Contexts/PlansContext';

/** Что обещаем по каждому виду работы. */
const LOOKS = {
    music: {
        kind: 'audio',
        title: 'Turn this into a real track',
        text: 'Studio composes original music from your words — instrumental or with vocals.',
        features: ['Instrumental & vocals', 'Up to 2 minutes', 'Ready in about a minute'],
        button: 'Create the track',
    },
    speech: {
        kind: 'audio',
        title: 'Hear it in a natural voice',
        text: 'Studio reads your text aloud with lifelike voices — for videos, ads and podcasts.',
        features: ['Lifelike voices', 'Adjustable speed', 'Ready in seconds'],
        button: 'Create the voiceover',
    },
    effect: {
        kind: 'audio',
        title: 'Create this sound',
        text: 'Studio generates any sound effect you can describe — for videos, games and apps.',
        features: ['Any sound', 'Up to 3 minutes', 'Seamless loops'],
        button: 'Create the sound',
    },
    video: {
        kind: 'video',
        title: 'Bring this idea to life on video',
        text: 'Studio shoots a short clip from your description, with sound.',
        features: ['HD clips', 'Sound included', '3 to 10 seconds'],
        button: 'Create the video',
    },
    animate: {
        kind: 'video',
        title: 'Make this picture move',
        text: 'Studio turns your still image into a short living clip.',
        features: ['From any picture', 'Sound included', '3 to 10 seconds'],
        button: 'Animate the picture',
    },
};

/** Эквалайзер в значке звука: высоты столбиков заданы заранее. */
const BARS = [0.55, 0.9, 0.65, 1, 0.7, 0.85, 0.5];

/**
 * Приглашение сделать работу в Студии.
 *
 * Звук и видео чат не делает — их делает Студия, это платная и самая
 * сильная часть продукта. Карточка рассказывает, что получится, и
 * открывает нужный раздел с уже заполненной формой. Видео доступно на
 * платных тарифах: тем, у кого его нет, рядом предложение тарифа.
 */
export default function StudioLinkCard({ link }) {
    const { auth } = usePage().props;
    const { openPlans } = usePlans();

    const look = LOOKS[link.operation] ?? LOOKS.music;
    const locked = look.kind === 'video' && (!auth?.user || auth.user.canUpgrade);

    return (
        <div className="promo-card promo-border relative w-full max-w-[560px] animate-[message-in_0.4s_ease-out] rounded-[22px] p-5">
            <span className="promo-sheen" aria-hidden="true" />

            <div className="relative flex items-start gap-4">
                <Visual kind={look.kind} />

                <div className="min-w-0 flex-1">
                    <span className="promo-badge inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider">
                        <Sparkles className="h-3 w-3" strokeWidth={2.25} />
                        {look.kind === 'video' ? 'Video Studio' : 'Audio Studio'}
                    </span>

                    <p className="mt-2 text-[18px] font-semibold leading-snug">{look.title}</p>
                    <p className="promo-muted mt-1 text-[13px] leading-relaxed">{look.text}</p>
                </div>
            </div>

            <div className="relative mt-4 flex flex-wrap gap-1.5">
                {look.features.map((feature) => (
                    <span
                        key={feature}
                        className="promo-chip rounded-full px-2.5 py-1 text-[12px]"
                    >
                        {feature}
                    </span>
                ))}
            </div>

            {link.prompt && (
                <div className="promo-quote relative mt-4 rounded-xl px-3.5 py-2.5">
                    <p className="promo-faint text-[11px] uppercase tracking-wider">Your request</p>
                    <p className="mt-0.5 line-clamp-2 text-[14px] leading-snug">{link.prompt}</p>
                </div>
            )}

            <div className="relative mt-4 flex flex-wrap items-center gap-3">
                <Link
                    href={link.url}
                    className="group inline-flex h-11 items-center gap-2 rounded-full bg-gradient-to-r from-violet-500 via-indigo-500 to-sky-500 px-6 text-[14px] font-semibold transition hover:brightness-110"
                    style={{ animation: 'promo-glow 3s ease-in-out infinite' }}
                >
                    {look.button}
                    <ArrowRight className="h-4 w-4 transition group-hover:translate-x-0.5" strokeWidth={2.25} />
                </Link>

                {locked ? (
                    <button
                        type="button"
                        onClick={openPlans}
                        className="promo-pro inline-flex h-11 items-center gap-2 rounded-full px-4 text-[13px] font-medium transition"
                    >
                        <Crown className="h-4 w-4" strokeWidth={2} />
                        Pro feature — see plans
                    </button>
                ) : (
                    <span className="promo-faint text-[12px]">Your request is filled in — just press Create.</span>
                )}
            </div>
        </div>
    );
}

/** Живой значок: эквалайзер для звука, пульсирующий «плей» для видео. */
function Visual({ kind }) {
    if (kind === 'video') {
        return (
            <span className="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400/30 to-violet-500/30 ring-1 ring-sky-200/20">
                <span
                    className="absolute h-9 w-9 rounded-full bg-sky-300/40"
                    style={{ animation: 'promo-ring 1.8s ease-out infinite' }}
                />
                <span className="relative flex h-9 w-9 items-center justify-center rounded-full shadow-lg shadow-sky-500/40" style={{ background: '#fff', color: '#4f46e5' }}>
                    <Play className="ml-0.5 h-4 w-4" strokeWidth={2.5} fill="currentColor" />
                </span>
            </span>
        );
    }

    return (
        <span className="flex h-16 w-16 shrink-0 items-end justify-center gap-[3px] rounded-2xl bg-gradient-to-br from-violet-500/30 to-sky-400/30 px-3 pb-4 pt-4 ring-1 ring-sky-200/20">
            {BARS.map((height, index) => (
                <span
                    key={index}
                    className="w-[4px] origin-bottom rounded-full bg-gradient-to-t from-sky-300 to-violet-200"
                    style={{
                        height: `${height * 100}%`,
                        animation: `eq-bar ${0.9 + (index % 3) * 0.25}s ease-in-out ${index * 0.12}s infinite`,
                    }}
                />
            ))}
        </span>
    );
}
