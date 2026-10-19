import { useEffect, useRef } from 'react';
import { Plus, Mic, ArrowUp, Square, Loader2, X } from 'lucide-react';
import ModelPicker from '@/Components/Home/ModelPicker';
import AttachedFiles from '@/Components/Home/AttachedFiles';
import useVoiceInput from '@/Components/Home/useVoiceInput';

/** Что принимаем: должно совпадать с правилами на сервере. */
const ACCEPT = 'image/jpeg,image/png,image/webp,image/gif,application/pdf,'
    + 'text/plain,text/markdown,text/csv,application/json,application/xml';

const MAX_FILES = 5;

/**
 * Поле ввода промпта — центральный элемент главной.
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
    placeholder = 'Ask anything or describe an image…',
    busy = false,
    autoFocus = false,
    minRows = 3,
    embedded = false,
}) {
    const textareaRef = useRef(null);
    const fileRef = useRef(null);

    // Распознанная речь дописывается к уже набранному тексту.
    const voice = useVoiceInput((text) => {
        onChange(value.trim() ? `${value.trim()} ${text}` : text);
        textareaRef.current?.focus();
    });

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
            // В окне диалога поле лежит на общей подложке: размытие
            // даёт она, а скругление подогнано под её углы.
            className={`w-full p-4 sm:p-5 ${
                embedded
                    ? 'rounded-[20px] border border-white/[0.12] bg-white/[0.05] transition-colors focus-within:border-white/25'
                    : 'rounded-3xl border border-white/20 bg-white/[0.03] backdrop-blur-md'
            }`}
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

            {voice.error && (
                <p className="mt-3 flex items-center justify-between gap-3 rounded-xl border border-rose-400/25 bg-rose-950/50 px-3 py-2 text-[13px] text-rose-100">
                    {voice.error}
                    <button
                        type="button"
                        onClick={voice.dismissError}
                        aria-label="Dismiss"
                        className="shrink-0 rounded-md p-0.5 text-rose-200/70 transition hover:text-white"
                    >
                        <X className="h-3.5 w-3.5" strokeWidth={2} />
                    </button>
                </p>
            )}

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
                    {voice.state === 'recording' && (
                        <span className="flex items-center gap-2 text-[13px] tabular-nums text-rose-200">
                            <span className="h-2 w-2 animate-pulse rounded-full bg-rose-400" />
                            {Math.floor(voice.seconds / 60)}:
                            {String(voice.seconds % 60).padStart(2, '0')}
                        </span>
                    )}

                    {voice.state === 'transcribing' && (
                        <span className="text-[13px] text-white/50">Listening…</span>
                    )}

                    {voice.supported && (
                        <button
                            type="button"
                            onClick={voice.toggle}
                            disabled={voice.state === 'transcribing'}
                            aria-label={voice.state === 'recording' ? 'Stop recording' : 'Dictate a message'}
                            title={voice.state === 'recording' ? 'Stop recording' : 'Dictate a message'}
                            className={`flex h-10 w-10 items-center justify-center rounded-full transition ${
                                voice.state === 'recording'
                                    ? 'keep-light bg-rose-500 text-white shadow-lg shadow-rose-500/30 hover:bg-rose-400'
                                    : 'text-white/70 hover:bg-white/10 hover:text-white disabled:opacity-60'
                            }`}
                        >
                            {voice.state === 'recording' ? (
                                <Square className="h-4 w-4" strokeWidth={2.5} fill="currentColor" />
                            ) : voice.state === 'transcribing' ? (
                                <Loader2 className="h-5 w-5 animate-spin" strokeWidth={1.75} />
                            ) : (
                                <Mic className="h-5 w-5" strokeWidth={1.75} />
                            )}
                        </button>
                    )}

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
