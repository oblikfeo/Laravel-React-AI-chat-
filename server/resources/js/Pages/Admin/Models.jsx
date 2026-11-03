import AdminLayout from '@/Layouts/AdminLayout';
import { Panel, Table } from '@/Components/Admin/Ui';
import { count, usd } from '@/Components/Admin/format';

/**
 * Какие модели где подключены и сколько стоят.
 *
 * Служебная страница: здесь настоящие названия моделей у провайдеров,
 * пользователи их не видят.
 */
export default function Models({ month, sections }) {
    const columns = [
        {
            key: 'label',
            label: 'Model',
            render: (row) => (
                <>
                    <span className="block text-white">{row.label}</span>
                    <span className="block text-[11px] text-white/40">{row.key}</span>
                </>
            ),
        },
        {
            key: 'providerModel',
            label: 'Provider model',
            render: (row) => <code className="break-all text-[12px] text-white/70">{row.providerModel ?? '—'}</code>,
        },
        {
            key: 'price',
            label: 'Price',
            render: (row) => (
                <span className="whitespace-nowrap">
                    {row.price}
                    {row.estimate && (
                        <span className="ml-1.5 text-[11px] text-amber-200/80" title="Not listed by the provider: our estimate">
                            est.
                        </span>
                    )}
                </span>
            ),
        },
        { key: 'usedIn', label: 'Used in', render: (row) => <span className="text-white/60">{row.usedIn}</span> },
        { key: 'requests', label: 'Requests', align: 'right', render: (row) => count(row.requests) },
        { key: 'spend', label: `Cost · ${month}`, align: 'right', render: (row) => usd(row.spend) },
    ];

    return (
        <AdminLayout title="Models">
            <div className="space-y-4">
                {sections.map((section) => (
                    <Panel
                        key={section.title}
                        title={section.title}
                        aside={
                            <span className="flex items-center gap-2 text-[12px] text-white/45">
                                {section.provider}
                                <span
                                    className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] ${
                                        section.configured
                                            ? 'border-emerald-400/30 text-emerald-300'
                                            : 'border-rose-400/30 text-rose-300'
                                    }`}
                                >
                                    {section.configured ? 'Connected' : 'No API key'}
                                </span>
                            </span>
                        }
                    >
                        <Table columns={columns} rows={section.rows} rowKey={(row) => row.key} />
                    </Panel>
                ))}
            </div>

            <p className="mt-4 text-[12px] leading-relaxed text-white/40">
                Prices are what the provider charges us. Chat replies are
                billed by the tokens each answer used at these prices.
                Prices are set in config/models.php and config/studio.php.
            </p>
        </AdminLayout>
    );
}
