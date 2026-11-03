import AdminLayout from '@/Layouts/AdminLayout';
import { FilterChips, Pagination, Panel, StatusBadge, Table, UsageCell, UserLink } from '@/Components/Admin/Ui';
import { money } from '@/Components/Admin/format';

/**
 * Подписки и сколько бюджета тарифа израсходовано в текущем месяце.
 */
export default function Subscriptions({ subscriptions, filters, statuses, plans }) {
    return (
        <AdminLayout title="Subscriptions">
            <div className="mb-3 flex flex-wrap items-center gap-x-6 gap-y-2">
                <FilterChips
                    path="/admin/subscriptions"
                    name="status"
                    value={filters.status}
                    params={{ plan: filters.plan }}
                    options={statuses.map((status) => ({ key: status, label: status }))}
                />
                <FilterChips
                    path="/admin/subscriptions"
                    name="plan"
                    value={filters.plan}
                    params={{ status: filters.status }}
                    options={plans}
                />
            </div>

            <Panel>
                <Table
                    columns={[
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
                        {
                            key: 'dates',
                            label: 'Period',
                            render: (row) => (
                                <span className="whitespace-nowrap text-white/60">
                                    {row.startsAt ?? '—'} → {row.endsAt ?? '—'}
                                </span>
                            ),
                        },
                        { key: 'amount', label: 'Price', align: 'right', render: (row) => money(row.amount, row.currency) },
                        { key: 'usage', label: 'Usage this month', render: (row) => <UsageCell usage={row.usage} /> },
                    ]}
                    rows={subscriptions.data}
                    empty="No subscriptions match these filters."
                />
                <Pagination page={subscriptions} />
            </Panel>
        </AdminLayout>
    );
}
