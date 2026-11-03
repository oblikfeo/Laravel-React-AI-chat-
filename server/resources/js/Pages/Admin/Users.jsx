import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Pagination, Panel, Table, UsageCell, UserLink } from '@/Components/Admin/Ui';
import { money } from '@/Components/Admin/format';

/**
 * Пользователи: тариф, сколько заплатили, сколько израсходовали.
 */
export default function Users({ users, filters, currency }) {
    const [query, setQuery] = useState(filters.q ?? '');

    const search = (event) => {
        event.preventDefault();
        router.get('/admin/users', query.trim() ? { q: query.trim() } : {}, { preserveState: true });
    };

    return (
        <AdminLayout title="Users">
            <form onSubmit={search} className="mb-3 flex max-w-[420px] items-center gap-2 rounded-xl border border-white/[0.12] bg-white/[0.04] px-3">
                <Search className="h-4 w-4 shrink-0 text-white/40" strokeWidth={1.75} />
                <input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Search by name or email"
                    aria-label="Search users"
                    className="h-10 w-full border-0 bg-transparent p-0 text-[14px] text-white outline-none placeholder:text-white/30 focus:ring-0"
                />
            </form>

            <Panel>
                <Table
                    columns={[
                        {
                            key: 'user',
                            label: 'User',
                            render: (row) => (
                                <span className="flex items-center gap-2">
                                    <UserLink user={row} />
                                    {row.isAdmin && (
                                        <span className="rounded-full border border-sky-400/30 px-1.5 text-[10px] text-sky-200">admin</span>
                                    )}
                                </span>
                            ),
                        },
                        { key: 'plan', label: 'Plan' },
                        { key: 'joinedAt', label: 'Joined', render: (row) => <span className="whitespace-nowrap text-white/60">{row.joinedAt}</span> },
                        { key: 'paid', label: 'Paid in total', align: 'right', render: (row) => (row.paid ? money(row.paid, currency) : '—') },
                        { key: 'usage', label: 'Usage this month', render: (row) => <UsageCell usage={row.usage} /> },
                    ]}
                    rows={users.data}
                    empty="No users found."
                />
                <Pagination page={users} />
            </Panel>
        </AdminLayout>
    );
}
