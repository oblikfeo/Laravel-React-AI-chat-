import { useState } from 'react';
import { X } from 'lucide-react';

/**
 * Теги персонажа.
 *
 * Тег можно выбрать из подсказок или вписать свой: закрытый список
 * не угадал бы всего, о чём люди делают персонажей.
 */
export default function TagInput({ value, onChange, suggestions = [], max = 5 }) {
    const [draft, setDraft] = useState('');

    const has = (tag) =>
        value.some((item) => item.toLowerCase() === tag.toLowerCase());

    const add = (raw) => {
        const tag = raw.trim().replace(/,+$/, '').slice(0, 24);

        if (!tag || has(tag) || value.length >= max) {
            setDraft('');

            return;
        }

        onChange([...value, tag]);
        setDraft('');
    };

    const remove = (tag) => onChange(value.filter((item) => item !== tag));

    const onKeyDown = (event) => {
        // Запятая и Enter завершают тег, как в привычных полях тегов.
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            add(draft);
        }

        if (event.key === 'Backspace' && !draft && value.length) {
            remove(value[value.length - 1]);
        }
    };

    const full = value.length >= max;
    const free = suggestions.filter((tag) => !has(tag));

    return (
        <div>
            <div className="flex min-h-[44px] flex-wrap items-center gap-1.5 rounded-xl border border-white/[0.12] bg-white/[0.04] px-2.5 py-2 transition focus-within:border-white/25">
                {value.map((tag) => (
                    <span
                        key={tag}
                        className="flex items-center gap-1 rounded-full border border-white/[0.14] bg-white/10 py-0.5 pl-2.5 pr-1 text-[12px] text-white"
                    >
                        {tag}
                        <button
                            type="button"
                            onClick={() => remove(tag)}
                            aria-label={`Remove ${tag}`}
                            className="rounded-full p-0.5 text-white/50 transition hover:bg-white/10 hover:text-white"
                        >
                            <X className="h-3 w-3" strokeWidth={2} />
                        </button>
                    </span>
                ))}

                {!full && (
                    <input
                        value={draft}
                        onChange={(event) => setDraft(event.target.value)}
                        onKeyDown={onKeyDown}
                        onBlur={() => add(draft)}
                        placeholder={
                            value.length ? 'Add another…' : 'Select or create tags…'
                        }
                        maxLength={24}
                        className="min-w-[120px] flex-1 border-0 bg-transparent p-0 px-1 text-[14px] text-white placeholder:text-white/30 focus:outline-none focus:ring-0"
                    />
                )}
            </div>

            {!full && free.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-1.5">
                    {free.map((tag) => (
                        <button
                            key={tag}
                            type="button"
                            onClick={() => add(tag)}
                            className="rounded-full border border-white/[0.12] px-2.5 py-1 text-[12px] text-white/60 transition hover:bg-white/10 hover:text-white"
                        >
                            {tag}
                        </button>
                    ))}
                </div>
            )}

            <p className="mt-1.5 text-[12px] text-white/35">
                {value.length} of {max} · helps people find your character
            </p>
        </div>
    );
}
