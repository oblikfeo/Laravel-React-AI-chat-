import { useRef, useState } from 'react';
import { FileText, Upload, Trash2 } from 'lucide-react';

/** Раздел «Context»: файл со справкой, на которую опирается персонаж. */
export default function Context({ data, setData, errors, limits, saved }) {
    const fileRef = useRef(null);
    const [over, setOver] = useState(false);

    const take = (file) => {
        if (file) {
            setData((values) => ({ ...values, context: file, remove_context: false }));
        }
    };

    // Что сейчас у персонажа: новый файл важнее сохранённого.
    const shown = data.context
        ? { name: data.context.name, fresh: true }
        : saved && !data.remove_context
          ? saved
          : null;

    const share = shown?.characters
        ? Math.min(100, (shown.characters / limits.context_chars) * 100)
        : 0;

    return (
        <div className="space-y-5">
            <p className="text-[13px] leading-relaxed text-white/50">
                Background details the character can rely on: a world
                description, a biography, product notes.
            </p>

            <input
                ref={fileRef}
                type="file"
                accept=".pdf,.txt,.md,application/pdf,text/plain,text/markdown"
                className="hidden"
                onChange={(event) => {
                    take(event.target.files[0]);
                    event.target.value = '';
                }}
            />

            {shown ? (
                <div className="flex items-center gap-3 rounded-xl border border-white/[0.12] bg-white/[0.04] p-3.5">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white/60">
                        <FileText className="h-5 w-5" strokeWidth={1.75} />
                    </span>

                    <span className="min-w-0 flex-1">
                        <span className="block truncate text-[14px] text-white">
                            {shown.name}
                        </span>
                        <span className="text-[12px] text-white/40">
                            {shown.fresh
                                ? 'Will be read when you save'
                                : shown.readable
                                  ? `${shown.characters.toLocaleString('en-US')} characters in use`
                                  : 'No text could be read from this file'}
                        </span>
                    </span>

                    <button
                        type="button"
                        onClick={() =>
                            setData((values) => ({
                                ...values,
                                context: null,
                                remove_context: true,
                            }))
                        }
                        aria-label="Remove file"
                        className="rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-rose-300"
                    >
                        <Trash2 className="h-4 w-4" strokeWidth={1.75} />
                    </button>
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => fileRef.current?.click()}
                    onDragOver={(event) => {
                        event.preventDefault();
                        setOver(true);
                    }}
                    onDragLeave={() => setOver(false)}
                    onDrop={(event) => {
                        event.preventDefault();
                        setOver(false);
                        take(event.dataTransfer.files[0]);
                    }}
                    className={`flex w-full flex-col items-center gap-2 rounded-2xl border border-dashed px-6 py-9 text-center transition ${
                        over
                            ? 'border-sky-300/60 bg-white/10'
                            : 'border-white/[0.16] hover:bg-white/[0.06]'
                    }`}
                >
                    <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-white/60">
                        <Upload className="h-5 w-5" strokeWidth={1.75} />
                    </span>
                    <span className="text-[14px] text-white">
                        <span className="font-semibold">Upload a file</span> or drag
                        and drop
                    </span>
                    <span className="text-[12px] text-white/40">
                        PDF, TXT or MD up to 5 MB
                    </span>
                </button>
            )}

            {errors.context && (
                <p className="text-[12px] text-rose-300">{errors.context}</p>
            )}

            <div>
                <div className="flex items-center justify-between text-[13px]">
                    <span className="font-medium text-white/70">Space used</span>
                    <span className="tabular-nums text-white/45">
                        {(shown?.characters ?? 0).toLocaleString('en-US')} /{' '}
                        {limits.context_chars.toLocaleString('en-US')} characters
                    </span>
                </div>

                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10">
                    <div
                        className="h-full rounded-full bg-sky-400"
                        style={{ width: `${share}%` }}
                    />
                </div>

                <p className="mt-1.5 text-[12px] leading-snug text-white/40">
                    Only the text is kept, the file itself is not stored. Longer
                    documents are cut to fit.
                </p>
            </div>
        </div>
    );
}
