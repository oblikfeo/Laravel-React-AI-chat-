import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { FilterChips, Pagination, Panel, StatusBadge, Table, UserLink } from '@/Components/Admin/Ui';
import { count, money } from '@/Components/Admin/format';

/**
 * История платежей и кто сколько заплатил за всё время.
 */
export default function Payments({ payments, filters, statuses, topPayers }) {
    return (
        <AdminLayout title="Payments">
            <div className="grid gap-4 lg:grid-cols-[1fr_320px]">
                <div className="min-w-0">
                    <div className="mb-3">
                        <FilterChips
                            path="/admin/payments"
                            name="status"
                            value={filters.status}
                            options={statuses.map((status) => ({ key: status, label: status }))}
                        />
                    </div>

                    <Panel>
                        <Table
                            columns={[
                                {
                                    key: 'date',
                                    label: 'Date',
                                    render: (row) => (
                                        <span className="whitespace-nowrap text-white/60">{row.paidAt ?? row.createdAt}</span>
                                    ),
                                },
                                { key: 'user', label: 'User', render: (row) => <UserLink user={row.user} /> },
                                {
                                    key: 'plan',
                                    label: 'Plan',
                                    render: (row) => (
                                        <>
                                            <span className="block text-white">{row.plan}</span>
                                            <span className="block text-[12px] capitalize text-white/45">{row.period}</span>
                                        </>
                                    ),
                                },
                                { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                                { key: 'amount', label: 'Amount', align: 'right', render: (row) => money(row.amount, row.currency) },
                                {
                                    key: 'providerId',
                                    label: 'Provider ID',
                                    render: (row) => (
                                        <code className="block max-w-[180px] truncate text-[11px] text-white/45" title={row.providerId ?? ''}>
                                            {row.provider} {row.providerId ?? ''}
                                        </code>
                                    ),
                                },
                            ]}
                            rows={payments.data}
                            empty="No payments yet."
                        />
                        <Pagination page={payments} />
                    </Panel>
                </div>

                <Panel title="Top payers" className="self-start">
                    {topPayers.length ? (
                        <ul className="divide-y divide-white/[0.05]">
                            {topPayers.map((payer) => (
                                <li key={`${payer.id}-${payer.currency}`} className="flex items-center justify-between gap-3 px-4 py-2.5">
                                    <Link href={`/admin/users/${payer.id}`} className="min-w-0 hover:underline">
                                        <span className="block truncate text-[13px] text-white">{payer.name}</span>
                                        <span className="block truncate text-[12px] text-white/45">
                                            {count(payer.payments)} payments
                                        </span>
                                    </Link>
                                    <span className="shrink-0 text-[13px] tabular-nums text-white">
                                        {money(payer.amount, payer.currency)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="px-4 py-8 text-center text-[13px] text-white/40">No paid subscriptions yet.</p>
                    )}
                </Panel>
            </div>
        </AdminLayout>
    );
}
