/** Раздел «Insights»: сколько людей и разговоров у персонажа. */
export default function Insights({ insights }) {
    const last = insights?.lastChatAt
        ? new Date(insights.lastChatAt.replace(' ', 'T')).toLocaleDateString('en-US', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : '—';

    const tiles = [
        { label: 'Conversations', value: insights?.chats ?? 0 },
        { label: 'People', value: insights?.people ?? 0 },
        { label: 'Messages sent', value: insights?.messages ?? 0 },
        { label: 'Last conversation', value: last },
    ];

    return (
        <div className="space-y-4">
            <p className="text-[13px] leading-relaxed text-white/50">
                How people use this character. Only the numbers: other people's
                conversations stay private.
            </p>

            <div className="grid grid-cols-2 gap-3">
                {tiles.map((tile) => (
                    <div
                        key={tile.label}
                        className="rounded-2xl border border-white/[0.08] bg-white/[0.04] p-4"
                    >
                        <p className="text-[12px] text-white/45">{tile.label}</p>
                        <p className="mt-1.5 text-[22px] font-semibold tabular-nums text-white">
                            {tile.value}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}
