import { useEffect, useRef } from 'react';
import { Plus, Mic, ArrowUp } from 'lucide-react';
import ModelPicker from '@/Components/Home/ModelPicker';
import AttachedFiles from '@/Components/Home/AttachedFiles';

/** Что принимаем: должно совпадать с правилами на сервере. */
const ACCEPT = 'image/jpeg,image/png,image/webp,image/gif,application/pdf,'
    + 'text/plain,text/markdown,text/csv,application/json,application/xml';

const MAX_FILES = 5;

/**
 * Поле ввода промпта — центральный элемент главной (figma/1440w dark.jpg).
 * Стеклянная панель со скруглением 24px, панель действий снизу.
 */
export default function PromptComposer({
    value,
    onChange,
    onSubmit,
    model,
    onModelChange,
    files = [],
    onFilesChange,
    placeholder = 'Generate or animate videos...',
    busy = false,
    autoFocus = false,
    minRows = 3,
}) {
    const textareaRef = useRef(null);
    const fileRef = useRef(null);

    useEffect(() => {
        const node = textareaRef.current;

        if (!node) {
            return;
        }

        node.style.height = 'auto';
        node.style.height = `${Math.min(node.scrollHeight, 220)}px`;
    }, [value]);

    // Отправлять можно и без текста, если приложен файл: «посмотри
    // этот документ» без слов — обычный сценарий.
    const canSubmit = (value.trim().length > 0 || files.length > 0) && !busy;

    const addFiles = (incoming) => {
        if (!onFilesChange) {
            return;
        }

        onFilesChange([...files, ...Array.from(incoming)].slice(0, MAX_FILES));
    };

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
            // Файл можно перетащить прямо на поле ввода.
            onDragOver={(event) => event.preventDefault()}
            onDrop={(event) => {
                event.preventDefault();
                addFiles(event.dataTransfer.files);
            }}
            className="w-full rounded-3xl border border-white/[0.14] bg-slate-950/55 p-4 shadow-2xl shadow-black/50 backdrop-blur-2xl sm:p-5"
        >
            {onFilesChange && (
                <AttachedFiles
                    files={files}
                    onRemove={(index) =>
                        onFilesChange(files.filter((_, i) => i !== index))
                    }
                />
            )}

            <textarea
                ref={textareaRef}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                onKeyDown={handleKeyDown}
                // Картинку можно вставить из буфера обмена.
                onPaste={(event) => {
                    const pasted = Array.from(event.clipboardData.files);

                    if (pasted.length) {
                        event.preventDefault();
                        addFiles(pasted);
                    }
                }}
                placeholder={placeholder}
                rows={minRows}
                autoFocus={autoFocus}
                className="w-full resize-none border-0 bg-transparent p-0 text-[17px] leading-relaxed text-white placeholder:text-white/45 focus:outline-none focus:ring-0"
            />

            <div className="mt-4 flex items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <input
                        ref={fileRef}
                        type="file"
                        multiple
                        accept={ACCEPT}
                        className="hidden"
                        onChange={(event) => {
                            addFiles(event.target.files);
                            // Сброс: иначе повторный выбор того же файла
                            // не вызовет событие.
                            event.target.value = '';
                        }}
                    />

                    <button
                        type="button"
                        onClick={() => fileRef.current?.click()}
                        disabled={!onFilesChange || files.length >= MAX_FILES}
                        aria-label="Attach file"
                        title={
                            files.length >= MAX_FILES
                                ? `Up to ${MAX_FILES} files`
                                : 'Attach file'
                        }
                        className="flex h-10 w-10 items-center justify-center rounded-full border border-white/[0.12] text-white/70 transition hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <Plus className="h-5 w-5" strokeWidth={1.75} />
                    </button>

                    {onModelChange && (
                        <ModelPicker value={model} onChange={onModelChange} />
                    )}
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
