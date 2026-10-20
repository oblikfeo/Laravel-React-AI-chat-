import { Trash2, Plus } from 'lucide-react';
import { INPUT } from '@/Components/Characters/Sections/Fields';

/** Раздел «Memories»: факты, которые персонаж помнит всегда. */
export default function Memories({ data, setData, errors, limits }) {
    const update = (index, text) =>
        setData(
            'memories',
            data.memories.map((memory, position) =>
                position === index ? text : memory,
            ),
        );

    const remove = (index) =>
        setData(
            'memories',
            data.memories.filter((_, position) => position !== index),
        );

    const full = data.memories.length >= limits.memories;

    return (
        <div className="space-y-4">
            <p className="text-[13px] leading-relaxed text-white/50">
                Facts the character never forgets. A long conversation does not
                fit into one request, so early messages fade — whatever you put
                here stays.
            </p>

            {data.memories.length === 0 && (
                <p className="rounded-xl border border-dashed border-white/[0.14] px-4 py-5 text-center text-[13px] text-white/45">
                    No memories yet. Add the first one below.
                </p>
            )}

            <div className="space-y-2">
                {data.memories.map((memory, index) => (
                    <div key={index} className="flex items-start gap-2">
                        <textarea
                            value={memory}
                            onChange={(event) => update(index, event.target.value)}
                            maxLength={limits.memory_length}
                            rows={2}
                            placeholder="For example: grew up on a mining colony and hates the cold."
                            className={`${INPUT} resize-none py-2.5 text-[14px] leading-relaxed`}
                        />

                        <button
                            type="button"
                            onClick={() => remove(index)}
                            aria-label="Remove memory"
                            className="mt-1.5 shrink-0 rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-rose-300"
                        >
                            <Trash2 className="h-4 w-4" strokeWidth={1.75} />
                        </button>
                    </div>
                ))}
            </div>

            {errors.memories && (
                <p className="text-[12px] text-rose-300">{errors.memories}</p>
            )}

            <button
                type="button"
                disabled={full}
                onClick={() => setData('memories', [...data.memories, ''])}
                className="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-dashed border-white/[0.16] text-[13px] text-white/60 transition hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
            >
                <Plus className="h-4 w-4" strokeWidth={1.75} />
                {full ? `Up to ${limits.memories} memories` : 'Add memory'}
            </button>
        </div>
    );
}
