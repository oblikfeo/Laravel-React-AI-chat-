import AdminLayout from '@/Layouts/AdminLayout';
import { Panel, Stat, StatusBadge, Table, UsageCell } from '@/Components/Admin/Ui';
import { CATEGORY_LABELS, money, usd } from '@/Components/Admin/format';

const GROUPS = [
    ['chat', 'Chat'],
    ['images', 'Images'],
    ['audio', 'Audio'],
    ['video', 'Video'],
];

/** Подробности одной траты: токены, секунды, варианты. */
function details(meta) {
    if (!meta) {
        return '—';
    }

    return [
        meta.input_tokens != null && `${meta.input_tokens} in`,
        meta.output_tokens != null && `${meta.output_tokens} out`,
        meta.operation,
        meta.variants && `×${meta.variants}`,
        meta.seconds && `${meta.seconds}s`,
        meta.resolution,
    ]
        .filter(Boolean)
        .join(' · ') || '—';
}

/**
 * Всё о пользователе для поддержки: тариф, расход бюджета, подписки,
 * платежи и последние траты.
 */
export default function User({ user, usage, subscriptions, payments, records }) {
    return (
        <AdminLayout title={user.name}>
            <p className="-mt-3 mb-5 text-[13px] text-white/50">
                {user.email} · joined {user.joinedAt}
                {user.google && ' · Google sign-in'}
                {user.isAdmin && ' · admin'}
            </p>

            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <Stat label="Plan" value={user.plan} hint={`Period ${usage.periodStart} → ${usage.renewsAt}`} />
                <Stat label="Used this period" value={`${usage.percentUsed}%`} hint={`${usd(usage.spent)} of ${usd(usage.available)}`} />
                <Stat label="Carried over" value={usd(usage.carried)} hint="From previous months" />
                <Stat label="Payments" value={payments.length} hint={payments.length ? `Last: ${payments[0].paidAt ?? payments[0].createdAt}` : 'None yet'} />
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-[320px_1fr]">
                <Panel title="This period" className="self-start">
                    <div className="space-y-3 px-4 py-4">
                        <UsageCell usage={usage} />
                        <ul className="space-y-1.5 pt-1 text-[13px]">
                            {GROUPS.map(([key, label]) => (
                                <li key={key} className="flex justify-between">
                                    <span className="text-white/60">{label}</span>
                                    <span className="tabular-nums text-white">{usd(usage.byGroup[key])}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </Panel>

                <div className="min-w-0 space-y-4">
                    <Panel title="Subscriptions">
                        <Table
                            columns={[
                                { key: 'plan', label: 'Plan', render: (row) => `${row.plan} · ${row.period}` },
                                { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                                { key: 'dates', label: 'Period', render: (row) => `${row.startsAt ?? '—'} → ${row.endsAt ?? '—'}` },
                                { key: 'provider', label: 'Source' },
                                { key: 'amount', label: 'Price', align: 'right', render: (row) => money(row.amount, row.currency) },
                            ]}
                            rows={subscriptions}
                            empty="No subscriptions."
                        />
                    </Panel>

                    <Panel title="Payments">
                        <Table
                            columns={[
                                { key: 'date', label: 'Date', render: (row) => row.paidAt ?? row.createdAt },
                                { key: 'plan', label: 'Plan', render: (row) => `${row.plan} · ${row.period}` },
                                { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                                { key: 'amount', label: 'Amount', align: 'right', render: (row) => money(row.amount, row.currency) },
                            ]}
                            rows={payments}
                            empty="No payments."
                        />
                    </Panel>
                </div>
            </div>

            <Panel title="Recent requests" className="mt-4" aside={<span className="text-[12px] text-white/40">Last 50</span>}>
                <Table
                    columns={[
                        { key: 'createdAt', label: 'When', render: (row) => <span className="whitespace-nowrap text-white/60">{row.createdAt}</span> },
                        { key: 'category', label: 'Kind', render: (row) => CATEGORY_LABELS[row.category] ?? row.category },
                        {
                            key: 'model',
                            label: 'Model',
                            render: (row) => (
                                <>
                                    <span className="block text-white">{row.model ?? '—'}</span>
                                    <code className="block max-w-[260px] truncate text-[11px] text-white/40">{row.providerModel}</code>
                                </>
                            ),
                        },
                        { key: 'meta', label: 'Details', render: (row) => <span className="text-white/55">{details(row.meta)}</span> },
                        { key: 'cost', label: 'Cost', align: 'right', render: (row) => usd(row.cost) },
                    ]}
                    rows={records}
                    empty="No requests yet."
                />
            </Panel>
        </AdminLayout>
    );
}
