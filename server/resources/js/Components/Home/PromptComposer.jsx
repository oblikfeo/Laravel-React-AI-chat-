import { useEffect, useRef } from 'react';
import { Plus, Globe, Mic, ArrowUp } from 'lucide-react';

/**
 * Поле ввода промпта — центральный элемент главной (figma/1440w dark.jpg).
 * Стеклянная панель со скруглением 24px, панель действий снизу.
 */
export default function PromptComposer({
    value,
    onChange,
    onSubmit,
    visibility = 'Public',
    onToggleVisibility,
    placeholder = 'Generate or animate videos...',
    busy = false,
    autoFocus = false,
    minRows = 3,
}) {
    const textareaRef = useRef(null);

    useEffect(() => {
        const node = textareaRef.current;

        if (!node) {
            return;
        }

        node.style.height = 'auto';
        node.style.height = `${Math.min(node.scrollHeight, 220)}px`;
    }, [value]);

    const canSubmit = value.trim().length > 0 && !busy;

    const handleSubmit = (event) => {
        event.preventDefault();

        if (canSubmit) {
            onSubmit();
        }
    };

    const handleKeyDown = (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();

            if (canSubmit) {
                onSubmit();
            }
        }
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="w-full rounded-3xl border border-white/[0.14] bg-slate-950/55 p-4 shadow-2xl shadow-black/50 backdrop-blur-2xl sm:p-5"
        >
            <textarea
                ref={textareaRef}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                onKeyDown={handleKeyDown}
                placeholder={placeholder}
                rows={minRows}
                autoFocus={autoFocus}
                className="w-full resize-none border-0 bg-transparent p-0 text-[17px] leading-relaxed text-white placeholder:text-white/45 focus:outline-none focus:ring-0"
            />

            <div className="mt-4 flex items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        aria-label="Attach file"
                        className="flex h-10 w-10 items-center justify-center rounded-full border border-white/[0.12] text-white/70 transition hover:bg-white/10 hover:text-white"
                    >
                        <Plus className="h-5 w-5" strokeWidth={1.75} />
                    </button>

                    <button
                        type="button"
                        onClick={onToggleVisibility}
                        className="flex h-10 items-center gap-2 rounded-full border border-white/[0.12] px-4 text-[15px] text-white/75 transition hover:bg-white/10 hover:text-white"
                    >
                        <Globe className="h-[18px] w-[18px]" strokeWidth={1.75} />
                        {visibility}
                    </button>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        aria-label="Voice input"
                        className="flex h-10 w-10 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white"
                    >
                        <Mic className="h-5 w-5" strokeWidth={1.75} />
                    </button>

                    <button
                        type="submit"
                        disabled={!canSubmit}
                        aria-label="Send"
                        className={`flex h-10 w-10 items-center justify-center rounded-full transition ${
                            canSubmit
                                ? 'bg-white text-black hover:bg-white/90'
                                : 'border border-white/[0.12] text-white/40'
                        }`}
                    >
                        <ArrowUp className="h-5 w-5" strokeWidth={2} />
                    </button>
                </div>
            </div>
        </form>
    );
}
