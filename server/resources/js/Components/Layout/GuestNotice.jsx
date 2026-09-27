import { Link, usePage } from '@inertiajs/react';

/**
 * Остаток бесплатных сообщений для посетителя без учётной записи.
 *
 * Показывается всегда: человек должен понимать, что пользуется
 * пробным доступом, и видеть, сколько осталось.
 */
export default function GuestNotice() {
    const { guest } = usePage().props;

    if (!guest) {
        return null;
    }

    const { remaining, limit } = guest;
    const exhausted = remaining <= 0;

    return (
        <div
            className={`mx-auto mb-3 flex w-full max-w-[820px] items-center justify-center gap-2 rounded-full border px-4 py-2 text-[13px] ${
                exhausted
                    ? 'border-amber-400/25 bg-amber-400/[0.08] text-amber-100/90'
                    : 'border-white/[0.09] bg-slate-950/50 text-white/60'
            }`}
        >
            {exhausted ? (
                <>
                    <span>You have used today&apos;s free messages.</span>
                    <Link
                        href="/auth?mode=register"
                        className="font-semibold text-white underline underline-offset-2"
                    >
                        Sign up to continue
                    </Link>
                </>
            ) : (
                <>
                    <span>
                        {remaining} of {limit} free messages left today.
                    </span>
                    <Link
                        href="/auth?mode=register"
                        className="font-medium text-white/80 underline underline-offset-2 hover:text-white"
                    >
                        Sign up for more
                    </Link>
                </>
            )}
        </div>
    );
}
