import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SpendChart from '@/Components/Admin/SpendChart';
import { Panel, Stat, Table } from '@/Components/Admin/Ui';
import { CATEGORY_LABELS, count, moneyList, usd } from '@/Components/Admin/format';

/**
 * Главная админки: деньги клиентов, подписки и во что обходятся
 * провайдеры в этом месяце.
 */
export default function Overview({ overview }) {
    const { revenue, subscriptions, users, spend } = overview;
    const topCategory = Math.max(...spend.byCategory.map((line) => line.spend), 0);

    return (
        <AdminLayout title="Overview">
            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <Stat label={`Revenue · ${overview.month}`} value={moneyList(revenue.month)} hint={`${count(revenue.payments)} payments`} />
                <Stat label="Monthly recurring" value={moneyList(subscriptions.monthly)} hint={`${count(subscriptions.active)} active subscriptions`} />
                <Stat label={`Provider costs · ${overview.month}`} value={usd(spend.month)} hint="All requests, at cost" />
                <Stat label="Users" value={count(users.total)} hint={`${count(users.new)} new in 30 days · ${count(users.paying)} paying`} />
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-[1fr_340px]">
                <Panel title="Provider costs by day" aside={<span className="text-[12px] text-white/40">Last 30 days</span>}>
                    <SpendChart days={spend.daily} />
                </Panel>

                <Panel title="Where the money goes">
                    {spend.byCategory.length ? (
                        <ul className="space-y-3 px-4 py-4">
                            {spend.byCategory.map((line) => (
                                <li key={line.category}>
                                    <div className="flex items-baseline justify-between gap-3 text-[13px]">
                                        <span className="text-white/80">{CATEGORY_LABELS[line.category] ?? line.category}</span>
                                        <span className="tabular-nums text-white">{usd(line.spend)}</span>
                                    </div>
                                    <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-white/[0.06]">
                                        <div
                                            className="keep-light h-full rounded-full bg-sky-400/80"
                                            style={{ width: `${topCategory > 0 ? Math.max((line.spend / topCategory) * 100, 2) : 0}%` }}
                                        />
                                    </div>
                                    <p className="mt-0.5 text-[11px] text-white/35">{count(line.requests)} requests</p>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="px-4 py-10 text-center text-[13px] text-white/40">No requests this month yet.</p>
                    )}
                </Panel>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Panel title="Active subscriptions by plan" aside={<Link href="/admin/subscriptions?status=active" className="text-[12px] text-sky-300 hover:underline">All</Link>}>
                    <Table
                        rowKey={(row) => row.plan}
                        columns={[
                            { key: 'plan', label: 'Plan' },
                            { key: 'count', label: 'Subscriptions', align: 'right' },
                        ]}
                        rows={subscriptions.byPlan}
                        empty="No active subscriptions yet."
                    />
                </Panel>

                <Panel title={`Top spenders · ${overview.month}`} aside={<Link href="/admin/users" className="text-[12px] text-sky-300 hover:underline">All users</Link>}>
                    <Table
                        columns={[
                            {
                                key: 'name',
                                label: 'User',
                                render: (row) => (
                                    <Link href={`/admin/users/${row.id}`} className="hover:underline">
                                        <span className="block text-white">{row.name}</span>
                                        <span className="block text-[12px] text-white/45">{row.email}</span>
                                    </Link>
                                ),
                            },
                            { key: 'plan', label: 'Plan' },
                            { key: 'spend', label: 'Cost', align: 'right', render: (row) => usd(row.spend) },
                        ]}
                        rows={spend.topUsers}
                        empty="Nobody has used anything this month yet."
                    />
                </Panel>
            </div>

            <p className="mt-4 text-[12px] leading-relaxed text-white/40">
                Revenue is shown in the currency of each payment. Provider
                costs are in US dollars at cost price, so the two are not
                added up.
            </p>
        </AdminLayout>
    );
}
