import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Image as ImageIcon,
    Wand2,
    Music,
    Video,
    AlertCircle,
    RotateCw,
    X,
} from 'lucide-react';
import { Stars } from '@/Components/Studio/AudioCard';

/** Значок и название по разделу. */
const LOOKS = {
    image: { icon: ImageIcon, label: 'Image' },
    edit: { icon: Wand2, label: 'Edit' },
    audio: { icon: Music, label: 'Audio' },
    video: { icon: Video, label: 'Video' },
};

/**
 * Очередь раздела: то, что сейчас создаётся.
 *
 * Стоит под формой вместо галереи. Показывает только работы, которых
 * ждём: человек нажал кнопку и видит, что запрос принят и считается.
 * Запустить можно несколько подряд — они встают друг за другом.
 *
 * Несостоявшаяся работа остаётся здесь, пока её не повторят или не
 * уберут: иначе человек так и не узнал бы, что она не получилась.
 */
export default function WorkQueue({ items, section }) {
    if (!items.length) {
        return null;
    }

    const waiting = items.filter((item) => item.status !== 'failed').length;

    return (
        <div className="mt-4">
            <p className="mb-2 px-1 text-[12px] font-medium uppercase tracking-wide text-white/40">
                {waiting
                    ? `In progress · ${waiting}`
                    : 'Did not work out'}
            </p>

            <div className="grid gap-3 sm:grid-cols-2">
                {items.map((item) => (
                    <QueueCard key={item.id} item={item} section={section} />
                ))}
            </div>
        </div>
    );
}

function QueueCard({ item, section }) {
    const failed = item.status === 'failed';
    const look = LOOKS[section] ?? LOOKS.image;

    const remove = () =>
        router.delete(`/studio/${item.id}`, {
            async: true,
            preserveScroll: true,
        });

    const retry = () =>
        router.post(
            `/studio/${item.id}/retry`,
            {},
            { async: true, preserveScroll: true },
        );

    return (
        <div
            className={`relative flex items-center gap-3 rounded-2xl border bg-slate-950/55 p-3 ${
                failed
                    ? 'border-rose-400/20'
                    : 'work-border border-white/[0.06]'
            }`}
        >
            <div className="relative flex h-[72px] w-[72px] shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white/[0.06]">
                {failed ? (
                    <AlertCircle
                        className="h-6 w-6 text-rose-300/70"
                        strokeWidth={1.5}
                    />
                ) : (
                    <>
                        <Stars />
                        <look.icon
                            className="relative h-7 w-7 text-sky-300/70"
                            strokeWidth={1.5}
                            style={{
                                animation: 'work-pulse 1.8s ease-in-out infinite',
                            }}
                        />
                    </>
                )}
            </div>

            <div className="min-w-0 flex-1">
                <p className="text-[12px] text-white/40">
                    {[look.label, item.model].filter(Boolean).join(' · ')}
                </p>

                <p className="mt-0.5 line-clamp-2 text-[14px] leading-snug text-white/85">
                    {item.prompt}
                </p>

                {failed ? (
                    <div className="mt-2 flex flex-wrap items-center gap-1.5">
                        <span className="mr-1 w-full text-[12px] leading-snug text-rose-200/80">
                            {item.failureNote ?? 'Could not create this one.'}
                        </span>

                        <Small label="Try again" icon={RotateCw} onClick={retry} />
                        <Small label="Dismiss" icon={X} onClick={remove} />
                    </div>
                ) : (
                    <p className="mt-1.5 flex items-center gap-2 text-[12px]">
                        <span className="text-sky-200/70">Working on it…</span>
                        <Elapsed since={item.createdAt} />
                    </p>
                )}
            </div>
        </div>
    );
}

/**
 * Сколько уже ждём.
 *
 * Полосы прогресса нет: провайдер не сообщает, как далеко продвинулся,
 * и полоса либо врёт, либо замирает у края. Счётчик времени честнее.
 */
function Elapsed({ since }) {
    const started = since ? new Date(since).getTime() : Date.now();
    const [now, setNow] = useState(Date.now());

    useEffect(() => {
        const timer = setInterval(() => setNow(Date.now()), 1000);

        return () => clearInterval(timer);
    }, []);

    const seconds = Math.max(0, Math.floor((now - started) / 1000));

    return (
        <span className="tabular-nums text-white/35">
            {Math.floor(seconds / 60)}:{String(seconds % 60).padStart(2, '0')}
        </span>
    );
}

function Small({ label, icon: Icon, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="flex h-7 items-center gap-1.5 rounded-lg border border-white/[0.1] bg-white/[0.03] px-2 text-[12px] text-white/70 transition hover:bg-white/[0.12] hover:text-white"
        >
            <Icon className="h-3.5 w-3.5 shrink-0" strokeWidth={1.75} />
            {label}
        </button>
    );
}
