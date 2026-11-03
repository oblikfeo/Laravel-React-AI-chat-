import { usePlans } from '@/Contexts/PlansContext';
import UsageBar from '@/Components/Billing/UsageBar';

/** На что ушёл бюджет — в тех же словах, что разделы приложения. */
const GROUPS = [
    { key: 'chat', label: 'Chat' },
    { key: 'images', label: 'Images' },
    { key: 'audio', label: 'Audio' },
    { key: 'video', label: 'Video' },
];

/**
 * Текущая подписка и расход бюджета.
 *
 * Управление тарифом живёт в баннере тарифов, поэтому здесь только
 * состояние и переход к нему.
 */
export default function BillingSection({ user, onClose }) {
    const { openPlans } = usePlans();
    const usage = user.usage;

    return (
        <div className="space-y-5">
            <div className="rounded-2xl border border-white/[0.09] bg-white/[0.03] p-4">
                <p className="text-[13px] text-white/45">Current plan</p>
                <p className="mt-1 text-[19px] font-semibold text-white">
                    {user.plan}
                </p>

                {user.subscription?.endsAt && (
                    <p className="mt-2 text-[13px] text-white/50">
                        {user.subscription.cancelled
                            ? `Access until ${user.subscription.endsAt}`
                            : `Renews on ${user.subscription.endsAt}`}
                    </p>
                )}
            </div>

            {usage && (
                <div className="rounded-2xl border border-white/[0.09] bg-white/[0.03] p-4">
                    <UsageBar usage={usage} />

                    {usage.daily && (
                        <p className="mt-3 text-[12px] leading-snug text-white/45">
                            Today: {usage.daily.percentUsed}% of the daily
                            allowance. It renews every morning.
                        </p>
                    )}

                    {usage.carriedPercent > 0 && (
                        <p className="mt-3 text-[12px] leading-snug text-white/45">
                            Includes {usage.carriedPercent}% carried over from
                            last month.
                        </p>
                    )}

                    <div className="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        {GROUPS.map((group) => (
                            <div
                                key={group.key}
                                className="rounded-xl border border-white/[0.07] px-3 py-2.5"
                            >
                                <p className="text-[12px] text-white/45">{group.label}</p>
                                <p className="mt-0.5 text-[15px] font-semibold tabular-nums text-white">
                                    {usage.breakdown?.[group.key] ?? 0}%
                                </p>
                            </div>
                        ))}
                    </div>

                    <p className="mt-3 text-[12px] leading-snug text-white/40">
                        Every request uses a small share of your plan: a chat
                        reply very little, a video the most.
                    </p>
                </div>
            )}

            {user.canUpgrade && (
                <button
                    type="button"
                    onClick={() => {
                        onClose();
                        openPlans();
                    }}
                    className="h-10 rounded-full bg-white px-5 text-[13px] font-semibold text-black transition hover:bg-white/90"
                >
                    View plans
                </button>
            )}
        </div>
    );
}
