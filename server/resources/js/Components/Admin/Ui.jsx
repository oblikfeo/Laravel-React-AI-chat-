import { Link } from '@inertiajs/react';
import { usd } from '@/Components/Admin/format';

/** Карточка раздела с заголовком. */
export function Panel({ title, aside = null, children, className = '' }) {
    return (
        <section className={`rounded-2xl border border-white/[0.09] bg-slate-950/60 ${className}`}>
            {title && (
                <div className="flex items-center justify-between gap-3 border-b border-white/[0.07] px-4 py-3">
                    <h2 className="text-[14px] font-semibold text-white">{title}</h2>
                    {aside}
                </div>
            )}
            {children}
        </section>
    );
}

/** Один показатель: подпись, крупное число и пояснение. */
export function Stat({ label, value, hint = null }) {
    return (
        <div className="rounded-2xl border border-white/[0.09] bg-slate-950/60 px-4 py-3.5">
            <p className="text-[12px] text-white/45">{label}</p>
            <p className="mt-1 truncate text-[22px] font-semibold tabular-nums text-white" title={String(value)}>
                {value}
            </p>
            {hint && <p className="mt-0.5 truncate text-[12px] text-white/40">{hint}</p>}
        </div>
    );
}

/**
 * Таблица.
 *
 * columns: [{ key, label, align, render(row) }]. На узком экране
 * прокручивается вбок внутри карточки, а не всей страницей.
 */
export function Table({ columns, rows, empty = 'Nothing here yet.', rowKey = (row) => row.id }) {
    if (!rows?.length) {
        return <p className="px-4 py-10 text-center text-[13px] text-white/40">{empty}</p>;
    }

    return (
        <div className="scrollbar-thin overflow-x-auto">
            <table className="w-full min-w-[640px] text-left text-[13px]">
                <thead>
                    <tr className="border-b border-white/[0.07] text-[12px] text-white/45">
                        {columns.map((column) => (
                            <th
                                key={column.key}
                                className={`whitespace-nowrap px-4 py-2.5 font-medium ${column.align === 'right' ? 'text-right' : ''}`}
                            >
                                {column.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={rowKey(row)} className="border-b border-white/[0.05] last:border-0 hover:bg-white/[0.03]">
                            {columns.map((column) => (
                                <td
                                    key={column.key}
                                    className={`px-4 py-2.5 align-top text-white/80 ${
                                        column.align === 'right' ? 'text-right tabular-nums' : ''
                                    }`}
                                >
                                    {column.render ? column.render(row) : (row[column.key] ?? '—')}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

/**
 * Листание страниц.
 *
 * Принимает то, что отдаёт пагинатор Laravel: напрямую или внутри
 * meta/links у коллекции ресурсов.
 */
export function Pagination({ page }) {
    const meta = page?.meta ?? page;
    const links = page?.links && !Array.isArray(page.links) ? page.links : null;

    const previous = links?.prev ?? meta?.prev_page_url;
    const next = links?.next ?? meta?.next_page_url;

    if (!meta || meta.last_page <= 1) {
        return null;
    }

    const button = 'rounded-lg border border-white/[0.12] px-3 py-1.5 text-[13px] transition';

    return (
        <div className="flex items-center justify-between gap-3 border-t border-white/[0.07] px-4 py-3 text-[12px] text-white/45">
            <span>
                Page {meta.current_page} of {meta.last_page} · {meta.total} total
            </span>

            <span className="flex gap-2">
                {previous ? (
                    <Link href={previous} preserveScroll className={`${button} text-white/75 hover:bg-white/10`}>
                        Previous
                    </Link>
                ) : (
                    <span className={`${button} text-white/25`}>Previous</span>
                )}
                {next ? (
                    <Link href={next} preserveScroll className={`${button} text-white/75 hover:bg-white/10`}>
                        Next
                    </Link>
                ) : (
                    <span className={`${button} text-white/25`}>Next</span>
                )}
            </span>
        </div>
    );
}

/**
 * Фильтр-переключатель ссылками: состояние фильтра живёт в адресе,
 * его можно сохранить в закладки и переслать.
 */
export function FilterChips({ path, name, value, options, params = {} }) {
    const href = (key) => {
        const query = new URLSearchParams(
            Object.entries({ ...params, [name]: key }).filter(([, v]) => v),
        ).toString();

        return query ? `${path}?${query}` : path;
    };

    return (
        <div className="flex flex-wrap gap-1.5">
            {[{ key: null, label: 'All' }, ...options].map((option) => (
                <Link
                    key={option.key ?? 'all'}
                    href={href(option.key)}
                    preserveScroll
                    className={`rounded-full border px-3 py-1 text-[12px] capitalize transition ${
                        (value ?? null) === option.key
                            ? 'border-white/25 bg-white/10 font-medium text-white'
                            : 'border-white/[0.1] text-white/55 hover:text-white'
                    }`}
                >
                    {option.label}
                </Link>
            ))}
        </div>
    );
}

const STATUS_TONES = {
    active: 'border-emerald-400/30 text-emerald-300',
    succeeded: 'border-emerald-400/30 text-emerald-300',
    cancelling: 'border-amber-400/30 text-amber-200',
    pending: 'border-sky-400/30 text-sky-200',
    cancelled: 'border-white/15 text-white/50',
    expired: 'border-white/15 text-white/50',
    failed: 'border-rose-400/30 text-rose-300',
};

/** Состояние подписки или платежа: всегда словом, цвет — подсказка. */
export function StatusBadge({ status }) {
    return (
        <span
            className={`inline-flex rounded-full border px-2 py-0.5 text-[11px] font-medium capitalize ${
                STATUS_TONES[status] ?? 'border-white/15 text-white/60'
            }`}
        >
            {status}
        </span>
    );
}

/** Расход бюджета: процент, полоска и доллары себестоимости. */
export function UsageCell({ usage }) {
    if (!usage) {
        return <span className="text-white/30">—</span>;
    }

    const tone = usage.exhausted ? 'bg-rose-400' : usage.percentUsed >= 80 ? 'bg-amber-400' : 'bg-sky-400';

    return (
        <div className="min-w-[120px]">
            <div className="flex items-baseline justify-between gap-2">
                <span className="font-medium tabular-nums text-white">{usage.percentUsed}%</span>
                <span className="text-[11px] tabular-nums text-white/40">
                    {usd(usage.spent)} / {usd(usage.available)}
                </span>
            </div>
            <div className="mt-1 h-1 overflow-hidden rounded-full bg-white/10">
                <div className={`keep-light h-full rounded-full ${tone}`} style={{ width: `${usage.percentUsed}%` }} />
            </div>
        </div>
    );
}

/** Ссылка на карточку пользователя: имя и почта. */
export function UserLink({ user }) {
    if (!user) {
        return <span className="text-white/30">—</span>;
    }

    return (
        <Link href={`/admin/users/${user.id}`} className="group block max-w-[240px]">
            <span className="block truncate text-white group-hover:underline">{user.name}</span>
            <span className="block truncate text-[12px] text-white/45">{user.email}</span>
        </Link>
    );
}
