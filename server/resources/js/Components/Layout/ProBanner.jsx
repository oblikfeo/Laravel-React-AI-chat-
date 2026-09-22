import { Rocket } from 'lucide-react';

export default function ProBanner() {
    return (
        <div className="relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-slate-800/80 via-slate-900/60 to-sky-950/60 p-4 backdrop-blur-md">
            <div className="relative z-10 pr-16">
                <p className="text-sm font-semibold text-white">
                    Become Pro Access
                </p>
                <p className="mt-1 text-xs leading-relaxed text-white/55">
                    Try your experience
                    <br />
                    for using more features
                </p>
            </div>

            <Rocket
                className="absolute right-3 top-6 h-14 w-14 -rotate-45 text-white/80"
                strokeWidth={1}
            />

            <button
                type="button"
                className="relative z-10 mt-4 w-full rounded-full bg-white py-2.5 text-sm font-semibold text-black transition hover:bg-white/90"
            >
                Upgrade Now
            </button>
        </div>
    );
}
