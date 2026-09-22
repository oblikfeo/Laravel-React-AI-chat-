import { usePlans } from '@/Contexts/PlansContext';

/**
 * Текущая подписка.
 *
 * Управление тарифом живёт в баннере тарифов, поэтому здесь только
 * состояние и переход к нему.
 */
export default function BillingSection({ user, onClose }) {
    const { openPlans } = usePlans();

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
