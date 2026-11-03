import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Plus, Mic, ArrowUp, Square, Loader2, X } from 'lucide-react';
import ModelPicker from '@/Components/Home/ModelPicker';
import AttachedFiles from '@/Components/Home/AttachedFiles';
import useVoiceInput from '@/Components/Home/useVoiceInput';

/** Что принимаем: должно совпадать с правилами на сервере. */
const ACCEPT = 'image/jpeg,image/png,image/webp,image/gif,application/pdf,'
    + 'text/plain,text/markdown,text/csv,application/json,application/xml';

/** Пределы — те же, что проверяет сервер, см. ValidatesAttachments. */
const MAX_FILES = 5;
const MAX_FILE_MB = 10;
const MAX_TOTAL_MB = 25;
const MAX_CHARS = 8000;

const MB = 1024 * 1024;
const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'txt', 'md', 'csv', 'json', 'xml'];

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
    const { errors = {} } = usePage().props;

    // Что не так с вводом: слишком длинный текст или неподходящий
    // файл. Ловим до отправки — иначе запрос уйдёт и вернётся отказом.
    const [problem, setProblem] = useState(null);

    // Отказ сервера по полям формы: без него сообщение просто не
    // уходило бы, и человек не понимал бы почему.
    const serverError = errors.message
        ?? errors.files
        ?? Object.entries(errors).find(([key]) => key.startsWith('files.'))?.[1]
        ?? null;
    const shownProblem = problem ?? serverError;

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
    const canSubmit = (value.trim().length > 0 || files.length > 0)
        && value.length <= MAX_CHARS
        && !busy;

    const addFiles = (incoming) => {
        if (!onFilesChange) {
            return;
        }

        const types = ACCEPT.split(',');
        // Тип файла браузер знает не всегда (у .md он бывает пустым),
        // поэтому подходит и расширение. Окончательно проверит сервер.
        const accepted = (file) =>
            types.includes(file.type) || EXTENSIONS.includes(file.name.split('.').pop()?.toLowerCase());
        const picked = [];
        let total = files.reduce((sum, file) => sum + file.size, 0);
        let rejected = null;

        for (const file of Array.from(incoming)) {
            if (!accepted(file)) {
                rejected = `${file.name}: this file type is not supported.`;
            } else if (file.size > MAX_FILE_MB * MB) {
                rejected = `${file.name} is larger than ${MAX_FILE_MB} MB.`;
            } else if (total + file.size > MAX_TOTAL_MB * MB) {
                rejected = `Files together must be under ${MAX_TOTAL_MB} MB.`;
            } else if (files.length + picked.length >= MAX_FILES) {
                rejected = `You can attach up to ${MAX_FILES} files.`;
            } else {
                picked.push(file);
                total += file.size;
            }
        }

        setProblem(rejected);

        if (picked.length) {
            onFilesChange([...files, ...picked]);
        }
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
                onChange={(event) => {
                    onChange(event.target.value);

                    if (problem && event.target.value.length <= MAX_CHARS) {
                        setProblem(null);
                    }
                }}
                onKeyDown={handleKeyDown}
                // Картинку можно вставить из буфера обмена.
                onPaste={(event) => {
                    const pasted = Array.from(event.clipboardData.files);

                    if (pasted.length) {
                        event.preventDefault();
                        addFiles(pasted);

                        return;
                    }

                    // Огромный текст браузер молча обрезал бы по пределу:
                    // говорим, как передать его целиком.
                    const text = event.clipboardData.getData('text');

                    if (value.length + text.length > MAX_CHARS) {
                        setProblem(
                            `Messages are limited to ${MAX_CHARS.toLocaleString('en-US')} characters. `
                                + 'Save a longer text as a .txt file and attach it.',
                        );
                    }
                }}
                maxLength={MAX_CHARS}
                placeholder={placeholder}
                rows={minRows}
                autoFocus={autoFocus}
                className="w-full resize-none border-0 bg-transparent p-0 text-[17px] leading-relaxed text-white placeholder:text-white/45 focus:outline-none focus:ring-0"
            />

            {value.length > MAX_CHARS * 0.9 && (
                <p className="mt-2 text-right text-[12px] tabular-nums text-white/45">
                    {value.length.toLocaleString('en-US')} / {MAX_CHARS.toLocaleString('en-US')}
                </p>
            )}

            {shownProblem && (
                <p className="mt-3 flex items-center justify-between gap-3 rounded-xl border border-rose-400/25 bg-rose-950/50 px-3 py-2 text-[13px] text-rose-100">
                    {shownProblem}
                    {problem && (
                        <button
                            type="button"
                            onClick={() => setProblem(null)}
                            aria-label="Dismiss"
                            className="shrink-0 rounded-md p-0.5 text-rose-200/70 transition hover:text-white"
                        >
                            <X className="h-3.5 w-3.5" strokeWidth={2} />
                        </button>
                    )}
                </p>
            )}

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
