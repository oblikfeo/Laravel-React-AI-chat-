import { useEffect, useState } from 'react';
import { Image as ImageIcon, Wand2 } from 'lucide-react';
import { LogoMark } from '@/Components/Layout/Logo';
import Stars from '@/Components/Studio/Stars';

/**
 * Ожидание картинки в чате: та же анимация, что в очереди Студии.
 *
 * Квадрат будущей картинки с лучом по рамке и мерцающими звёздами, под
 * ним — что делается и сколько уже ждём.
 */
export default function ImageWorking({ editing = false }) {
    const [seconds, setSeconds] = useState(0);

    useEffect(() => {
        const timer = setInterval(() => setSeconds((value) => value + 1), 1000);

        return () => clearInterval(timer);
    }, []);

    const Icon = editing ? Wand2 : ImageIcon;

    return (
        <div className="flex animate-[message-in_0.3s_ease-out] gap-3.5">
            <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.06]">
                <LogoMark className="h-5 w-5 animate-[logo-pulse_2s_ease-in-out_infinite]" />
            </span>

            <div className="pt-1">
                <div className="work-border relative flex h-[260px] w-[260px] max-w-full items-center justify-center overflow-hidden rounded-2xl border border-white/[0.06] bg-slate-950/55">
                    <Stars />
                    <Icon
                        className="relative h-9 w-9 text-sky-300/70"
                        strokeWidth={1.5}
                        style={{ animation: 'work-pulse 1.8s ease-in-out infinite' }}
                    />
                </div>

                <p className="mt-2 flex items-center gap-2 text-[13px]">
                    <span className="text-sky-200/75">
                        {editing ? 'Editing your image…' : 'Creating your image…'}
                    </span>
                    <span className="tabular-nums text-white/35">
                        {Math.floor(seconds / 60)}:{String(seconds % 60).padStart(2, '0')}
                    </span>
                </p>
            </div>
        </div>
    );
}
