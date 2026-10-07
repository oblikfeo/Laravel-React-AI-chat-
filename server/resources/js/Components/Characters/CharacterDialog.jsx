import { useEffect, useRef, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import {
    X,
    UserRound,
    MessageSquareText,
    FileText,
    Brain,
    BarChart3,
    Settings as SettingsIcon,
    Upload,
    Images,
    Trash2,
    Sparkles,
    Plus,
    Cpu,
    ImagePlus,
} from 'lucide-react';
import CharacterAvatar from '@/Components/Characters/CharacterAvatar';
import TagInput from '@/Components/Characters/TagInput';
import Toggle from '@/Components/Characters/Toggle';
import Dropdown from '@/Components/Studio/Dropdown';
import AssetPicker from '@/Components/Studio/AssetPicker';

/**
 * Разделы окна.
 *
 * Поля, за которые отвечает раздел, перечислены рядом: если сервер
 * вернул ошибку, окно само открывает раздел с ней — иначе человек
 * нажимает «Сохранить» и не понимает, почему ничего не произошло.
 */
const SECTIONS = [
    {
        key: 'general',
        label: 'General',
        icon: UserRound,
        fields: ['name', 'description', 'tags', 'avatar'],
    },
    {
        key: 'instructions',
        label: 'Instructions',
        icon: MessageSquareText,
        fields: ['intro', 'instructions', 'system_prompt'],
    },
    { key: 'context', label: 'Context', icon: FileText, fields: ['context'] },
    { key: 'memories', label: 'Memories', icon: Brain, fields: ['memories'] },
    { key: 'insights', label: 'Insights', icon: BarChart3, fields: [] },
    {
        key: 'settings',
        label: 'Settings',
        icon: SettingsIcon,
        fields: ['model', 'temperature', 'is_public'],
    },
];

/**
 * Создание и правка персонажа.
 *
 * Оформлено как окно настроек: разделы слева, содержимое справа, на
 * узком экране разделы уезжают строкой наверх.
 */
export default function CharacterDialog({ character, form: options, studioWorks, onClose }) {
    const { models = [] } = usePage().props;
    const isNew = !character;
    const limits = options.limits;

    const [section, setSection] = useState('general');
    const [pickingAvatar, setPickingAvatar] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);

    const form = useForm({
        name: character?.name ?? '',
        description: character?.description ?? '',
        tags: character?.tags ?? [],
        intro: character?.intro ?? '',
        instructions: character?.instructions ?? '',
        system_prompt: character?.systemPrompt ?? '',
        memories: character?.memories ?? [],
        model: character?.modelKey ?? options.defaultModel,
        temperature: character?.temperature ?? null,
        is_public: character?.wantsPublic ?? false,
        avatar: null,
        avatar_generation_id: null,
        remove_avatar: false,
        context: null,
        remove_context: false,
    });

    const { data, setData, errors, processing } = form;

    // Что показывать в рамке аватара: новый выбор важнее сохранённого.
    const [avatarPreview, setAvatarPreview] = useState(character?.avatar ?? null);
    const [customPrompt, setCustomPrompt] = useState(
        Boolean(character?.systemPrompt),
    );
    const [advanced, setAdvanced] = useState(
        character?.temperature !== null && character?.temperature !== undefined,
    );

    // Закрытие по Escape — обычное поведение окна.
    useEffect(() => {
        const onKeyDown = (event) => {
            if (event.key === 'Escape' && !pickingAvatar) {
                onClose();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [onClose, pickingAvatar]);

    // Раздел с первой ошибкой открывается сам.
    useEffect(() => {
        const failed = Object.keys(errors).map((key) => key.split('.')[0]);

        if (!failed.length) {
            return;
        }

        const target = SECTIONS.find((item) =>
            item.fields.some((field) => failed.includes(field)),
        );

        if (target) {
            setSection(target.key);
        }
    }, [errors]);

    const hasError = (item) =>
        item.fields.some((field) =>
            Object.keys(errors).some((key) => key.split('.')[0] === field),
        );

    const canPublish = !options.privateModels.includes(data.model);

    const submit = (event) => {
        event.preventDefault();

        form.transform((values) => ({
            ...values,
            system_prompt: customPrompt ? values.system_prompt : '',
            temperature: advanced ? values.temperature : null,
            is_public: canPublish && values.is_public,
            memories: values.memories.filter((memory) => memory.trim()),
        }));

        form.post(isNew ? '/characters' : `/characters/${character.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const remove = () =>
        router.delete(`/characters/${character.id}`, { onSuccess: onClose });

    const visible = SECTIONS.filter((item) => item.key !== 'insights' || !isNew);
    const current = visible.find((item) => item.key === section) ?? visible[0];

    return (
        <div
            className="backdrop-dim fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-0 backdrop-blur-sm sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-label={isNew ? 'Create character' : 'Edit character'}
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <form
                onSubmit={submit}
                className="flex h-full w-full max-w-[860px] flex-col overflow-hidden border-white/10 bg-slate-950/95 shadow-2xl shadow-black/60 backdrop-blur-2xl sm:h-[680px] sm:max-h-full sm:flex-row sm:rounded-2xl sm:border"
            >
                {/* Разделы: на широком экране колонкой слева. */}
                <nav className="hidden w-[208px] shrink-0 flex-col border-r border-white/[0.07] p-3 sm:flex">
                    <p className="px-3 pb-2 pt-1 text-xs font-medium uppercase tracking-wide text-white/35">
                        {isNew ? 'New character' : 'Character'}
                    </p>

                    {visible.map((item) => (
                        <SectionButton
                            key={item.key}
                            item={item}
                            active={current.key === item.key}
                            failed={hasError(item)}
                            onClick={() => setSection(item.key)}
                        />
                    ))}
                </nav>

                <div className="flex min-h-0 min-w-0 flex-1 flex-col">
                    <div className="flex shrink-0 items-center justify-between gap-3 border-b border-white/[0.07] px-5 py-4">
                        <p className="truncate text-[15px] font-semibold text-white">
                            <span className="sm:hidden">
                                {isNew ? 'New character' : data.name || 'Character'}
                            </span>
                            <span className="hidden sm:inline">{current.label}</span>
                        </p>

                        <button
                            type="button"
                            onClick={onClose}
                            aria-label="Close"
                            className="-mr-1.5 shrink-0 rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white"
                        >
                            <X className="h-5 w-5" strokeWidth={1.75} />
                        </button>
                    </div>

                    {/* На узком экране разделы — строка с прокруткой. */}
                    <div className="scrollbar-thin flex shrink-0 gap-1 overflow-x-auto border-b border-white/[0.07] px-3 py-2 sm:hidden">
                        {visible.map((item) => (
                            <button
                                key={item.key}
                                type="button"
                                onClick={() => setSection(item.key)}
                                className={`relative shrink-0 rounded-lg px-3 py-1.5 text-[13px] transition ${
                                    current.key === item.key
                                        ? 'bg-white/10 font-medium text-white'
                                        : 'text-white/60'
                                }`}
                            >
                                {item.label}
                                {hasError(item) && (
                                    <span className="absolute right-1 top-1 h-1.5 w-1.5 rounded-full bg-rose-400" />
                                )}
                            </button>
                        ))}
                    </div>

                    <div className="scrollbar-thin min-h-0 flex-1 overflow-y-auto px-5 py-5">
                        {current.key === 'general' && (
                            <General
                                data={data}
                                setData={setData}
                                errors={errors}
                                limits={limits}
                                suggestedTags={options.suggestedTags}
                                avatarPreview={avatarPreview}
                                setAvatarPreview={setAvatarPreview}
                                hasStudioWorks={studioWorks.length > 0}
                                onPickFromStudio={() => setPickingAvatar(true)}
                            />
                        )}

                        {current.key === 'instructions' && (
                            <Instructions
                                data={data}
                                setData={setData}
                                errors={errors}
                                limits={limits}
                                customPrompt={customPrompt}
                                setCustomPrompt={setCustomPrompt}
                            />
                        )}

                        {current.key === 'context' && (
                            <Context
                                data={data}
                                setData={setData}
                                errors={errors}
                                limits={limits}
                                saved={character?.context ?? null}
                            />
                        )}

                        {current.key === 'memories' && (
                            <Memories
                                data={data}
                                setData={setData}
                                errors={errors}
                                limits={limits}
                            />
                        )}

                        {current.key === 'insights' && (
                            <Insights insights={character?.insights} />
                        )}

                        {current.key === 'settings' && (
                            <CharacterSettings
                                data={data}
                                setData={setData}
                                errors={errors}
                                models={models}
                                canPublish={canPublish}
                                advanced={advanced}
                                setAdvanced={setAdvanced}
                            />
                        )}
                    </div>

                    <div className="flex shrink-0 flex-wrap items-center gap-2 border-t border-white/[0.07] px-5 py-3.5">
                        {!isNew &&
                            (confirmDelete ? (
                                <>
                                    <span className="text-[13px] text-white/60">
                                        Delete this character?
                                    </span>
                                    <button
                                        type="button"
                                        onClick={remove}
                                        className="h-9 rounded-full border border-rose-400/40 px-4 text-[13px] font-medium text-rose-300 transition hover:bg-rose-400/10"
                                    >
                                        Yes, delete
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setConfirmDelete(false)}
                                        className="h-9 rounded-full px-3 text-[13px] text-white/60 transition hover:text-white"
                                    >
                                        Keep
                                    </button>
                                </>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => setConfirmDelete(true)}
                                    className="flex h-9 items-center gap-2 rounded-full px-3 text-[13px] text-white/50 transition hover:bg-white/10 hover:text-rose-300"
                                >
                                    <Trash2 className="h-4 w-4" strokeWidth={1.75} />
                                    Delete
                                </button>
                            ))}

                        <div className="ml-auto flex items-center gap-2">
                            <button
                                type="button"
                                onClick={onClose}
                                className="h-10 rounded-full border border-white/[0.14] px-5 text-[14px] text-white/75 transition hover:bg-white/10 hover:text-white"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                disabled={processing}
                                className="h-10 rounded-full bg-white px-6 text-[14px] font-semibold text-black transition hover:bg-white/90 disabled:opacity-50"
                            >
                                {processing ? 'Saving…' : isNew ? 'Create' : 'Save'}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {pickingAvatar && (
                <AssetPicker
                    items={studioWorks}
                    onClose={() => setPickingAvatar(false)}
                    onPick={([work]) => {
                        if (work) {
                            setData((values) => ({
                                ...values,
                                avatar: null,
                                avatar_generation_id: work.id,
                                remove_avatar: false,
                            }));
                            setAvatarPreview(work.thumbnail ?? work.url);
                        }

                        setPickingAvatar(false);
                    }}
                />
            )}
        </div>
    );
}

function SectionButton({ item, active, failed, onClick }) {
    const Icon = item.icon;

    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm transition ${
                active
                    ? 'bg-white/10 font-medium text-white'
                    : 'text-white/65 hover:bg-white/[0.06] hover:text-white'
            }`}
        >
            <Icon className="h-4 w-4 shrink-0" strokeWidth={1.75} />
            <span className="flex-1">{item.label}</span>
            {failed && <span className="h-1.5 w-1.5 rounded-full bg-rose-400" />}
        </button>
    );
}

/** Подпись поля со счётчиком и текстом ошибки. */
function Labeled({ label, hint, error, count, max, action, children }) {
    return (
        <div>
            <div className="mb-1.5 flex items-center justify-between gap-3">
                <span className="text-[13px] font-medium text-white/70">{label}</span>

                <span className="flex items-center gap-2">
                    {typeof count === 'number' && (
                        <span
                            className={`text-[12px] tabular-nums ${
                                count > max ? 'text-rose-300' : 'text-white/35'
                            }`}
                        >
                            {count}/{max}
                        </span>
                    )}
                    {action}
                </span>
            </div>

            {children}

            {hint && !error && (
                <p className="mt-1.5 text-[12px] leading-snug text-white/40">{hint}</p>
            )}

            {error && <p className="mt-1.5 text-[12px] text-rose-300">{error}</p>}
        </div>
    );
}

const INPUT =
    'w-full rounded-xl border border-white/[0.12] bg-white/[0.04] px-3.5 text-[15px] text-white outline-none transition placeholder:text-white/30 focus:border-white/25 focus:ring-0';

/**
 * Кнопка «допиши за меня».
 *
 * По тому, что уже заполнено, модель предлагает недостающий текст.
 * Ответ приходит данными и подставляется в открытую форму.
 */
function WriteButton({ target, data, onText, label }) {
    const [busy, setBusy] = useState(false);
    const [problem, setProblem] = useState(null);

    const run = async () => {
        setBusy(true);
        setProblem(null);

        try {
            const response = await window.axios.post('/characters/write', {
                target,
                name: data.name,
                description: data.description,
                instructions: data.instructions,
            });

            onText(response.data.text);
        } catch (error) {
            setProblem(
                error.response?.data?.message ??
                    'Could not write it right now. Please try again in a moment.',
            );
        } finally {
            setBusy(false);
        }
    };

    return (
        <div>
            <button
                type="button"
                onClick={run}
                disabled={busy}
                className="flex h-9 w-full items-center justify-center gap-2 rounded-xl border border-white/[0.12] text-[13px] text-white/70 transition hover:bg-white/10 hover:text-white disabled:opacity-50"
            >
                <Sparkles
                    className={`h-4 w-4 ${busy ? 'animate-pulse' : ''}`}
                    strokeWidth={1.75}
                />
                {busy ? 'Writing…' : label}
            </button>

            {problem && <p className="mt-1.5 text-[12px] text-rose-300">{problem}</p>}
        </div>
    );
}

function General({
    data,
    setData,
    errors,
    limits,
    suggestedTags,
    avatarPreview,
    setAvatarPreview,
    hasStudioWorks,
    onPickFromStudio,
}) {
    const fileRef = useRef(null);

    const upload = (file) => {
        if (!file) {
            return;
        }

        setData((values) => ({
            ...values,
            avatar: file,
            avatar_generation_id: null,
            remove_avatar: false,
        }));
        setAvatarPreview(URL.createObjectURL(file));
    };

    const clear = () => {
        setData((values) => ({
            ...values,
            avatar: null,
            avatar_generation_id: null,
            remove_avatar: true,
        }));
        setAvatarPreview(null);
    };

    return (
        <div className="space-y-5">
            <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                <div className="relative shrink-0">
                    {avatarPreview ? (
                        <img
                            src={avatarPreview}
                            alt=""
                            className="h-[132px] w-[132px] rounded-3xl border border-white/[0.12] object-cover"
                        />
                    ) : data.name.trim() ? (
                        <CharacterAvatar
                            name={data.name}
                            className="h-[132px] w-[132px] rounded-3xl text-5xl"
                        />
                    ) : (
                        <span className="flex h-[132px] w-[132px] items-center justify-center rounded-3xl border border-dashed border-white/20 bg-white/[0.04] text-white/30">
                            <ImagePlus className="h-9 w-9" strokeWidth={1.25} />
                        </span>
                    )}
                </div>

                <div className="w-full min-w-0 flex-1 space-y-2">
                    <p className="text-[13px] font-medium text-white/70">Avatar</p>

                    <input
                        ref={fileRef}
                        type="file"
                        accept="image/*"
                        className="hidden"
                        onChange={(event) => {
                            upload(event.target.files[0]);
                            event.target.value = '';
                        }}
                    />

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => fileRef.current?.click()}
                            className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-3.5 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                        >
                            <Upload className="h-4 w-4" strokeWidth={1.75} />
                            Upload
                        </button>

                        {hasStudioWorks && (
                            <button
                                type="button"
                                onClick={onPickFromStudio}
                                className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-3.5 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                            >
                                <Images className="h-4 w-4" strokeWidth={1.75} />
                                From Studio
                            </button>
                        )}

                        {avatarPreview && (
                            <button
                                type="button"
                                onClick={clear}
                                className="flex h-9 items-center gap-2 rounded-full px-3 text-[13px] text-white/50 transition hover:bg-white/10 hover:text-rose-300"
                            >
                                <Trash2 className="h-4 w-4" strokeWidth={1.75} />
                                Remove
                            </button>
                        )}
                    </div>

                    <p className="text-[12px] leading-snug text-white/40">
                        Any image up to 5 MB. Without one the character gets a
                        coloured initial.
                    </p>

                    {errors.avatar && (
                        <p className="text-[12px] text-rose-300">{errors.avatar}</p>
                    )}
                </div>
            </div>

            <Labeled
                label="Name"
                error={errors.name}
                count={data.name.length}
                max={limits.name}
            >
                <input
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    maxLength={limits.name}
                    placeholder="What is your character called?"
                    className={`${INPUT} h-11`}
                />
            </Labeled>

            <Labeled
                label="Description"
                hint="Shown on the character's card. The model does not treat it as instructions."
                error={errors.description}
                count={data.description.length}
                max={limits.description}
            >
                <textarea
                    value={data.description}
                    onChange={(event) => setData('description', event.target.value)}
                    maxLength={limits.description}
                    rows={3}
                    placeholder="One or two sentences about who this is."
                    className={`${INPUT} resize-y py-2.5 leading-relaxed`}
                />
            </Labeled>

            <WriteButton
                target="description"
                data={data}
                label="Auto-Generate description"
                onText={(text) => setData('description', text)}
            />

            <Labeled label="Tags" error={errors.tags}>
                <TagInput
                    value={data.tags}
                    onChange={(tags) => setData('tags', tags)}
                    suggestions={suggestedTags}
                    max={limits.tags}
                />
            </Labeled>
        </div>
    );
}

function Instructions({ data, setData, errors, limits, customPrompt, setCustomPrompt }) {
    return (
        <div className="space-y-5">
            <Labeled
                label="Intro statement"
                hint="Optional. The character's first message in a new conversation."
                error={errors.intro}
                count={data.intro.length}
                max={limits.intro}
            >
                <textarea
                    value={data.intro}
                    onChange={(event) => setData('intro', event.target.value)}
                    maxLength={limits.intro}
                    rows={2}
                    placeholder="How does your character greet people?"
                    className={`${INPUT} resize-y py-2.5 leading-relaxed`}
                />
            </Labeled>

            <Labeled
                label="Instructions"
                hint="Only you can see this. It becomes the basis of the character's personality."
                error={errors.instructions}
                count={data.instructions.length}
                max={limits.instructions}
            >
                <textarea
                    value={data.instructions}
                    onChange={(event) => setData('instructions', event.target.value)}
                    maxLength={limits.instructions}
                    rows={9}
                    placeholder="Describe your character in as much detail as possible: who they are, how they speak, what they care about."
                    className={`${INPUT} resize-y py-2.5 leading-relaxed`}
                />
            </Labeled>

            <WriteButton
                target="instructions"
                data={data}
                label="Write instructions from name and description"
                onText={(text) => setData('instructions', text)}
            />

            {customPrompt ? (
                <Labeled
                    label="Custom system prompt"
                    hint="Replaces our wrapper entirely. Leave it off unless you know you need it."
                    error={errors.system_prompt}
                    count={data.system_prompt.length}
                    max={limits.system_prompt}
                    action={
                        <button
                            type="button"
                            onClick={() => setCustomPrompt(false)}
                            className="text-[12px] text-white/45 transition hover:text-white"
                        >
                            Remove
                        </button>
                    }
                >
                    <textarea
                        value={data.system_prompt}
                        onChange={(event) =>
                            setData('system_prompt', event.target.value)
                        }
                        maxLength={limits.system_prompt}
                        rows={6}
                        placeholder="The full system prompt sent to the model."
                        className={`${INPUT} resize-y py-2.5 font-mono text-[13px] leading-relaxed`}
                    />
                </Labeled>
            ) : (
                <button
                    type="button"
                    onClick={() => setCustomPrompt(true)}
                    className="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-dashed border-white/[0.16] text-[13px] text-white/60 transition hover:bg-white/10 hover:text-white"
                >
                    <Plus className="h-4 w-4" strokeWidth={1.75} />
                    Add custom system prompt
                </button>
            )}
        </div>
    );
}

function Context({ data, setData, errors, limits, saved }) {
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

function Memories({ data, setData, errors, limits }) {
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

function Insights({ insights }) {
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

function CharacterSettings({
    data,
    setData,
    errors,
    models,
    canPublish,
    advanced,
    setAdvanced,
}) {
    const temperature = data.temperature ?? 0.7;

    return (
        <div>
            <div className="divide-y divide-white/[0.07]">
                <Toggle
                    label="Public"
                    hint={
                        canPublish
                            ? 'Show this character in the catalogue and the feed. Your instructions stay hidden.'
                            : 'Characters on this model stay private and are not shown in the catalogue.'
                    }
                    checked={canPublish && data.is_public}
                    disabled={!canPublish}
                    onChange={(value) => setData('is_public', value)}
                />

                <div className="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div className="min-w-0">
                        <p className="text-[14px] font-medium text-white">Model</p>
                        <p className="mt-0.5 text-[12px] text-white/45">
                            Which model speaks for the character.
                        </p>
                    </div>

                    <Dropdown
                        value={data.model}
                        onChange={(value) => setData('model', value)}
                        options={models}
                        label="Model"
                        icon={Cpu}
                        align="right"
                    />
                </div>

                <Toggle
                    label="Advanced settings"
                    hint="Fine-tune how the character answers."
                    checked={advanced}
                    onChange={(value) => {
                        setAdvanced(value);

                        if (value && data.temperature === null) {
                            setData('temperature', 0.7);
                        }
                    }}
                />
            </div>

            {errors.model && (
                <p className="mt-1 text-[12px] text-rose-300">{errors.model}</p>
            )}

            {advanced && (
                <div className="mt-2 rounded-2xl border border-white/[0.08] bg-white/[0.04] p-4">
                    <div className="flex items-center justify-between">
                        <p className="text-[14px] font-medium text-white">
                            Temperature
                        </p>
                        <span className="rounded-lg bg-white/10 px-2 py-0.5 text-[13px] tabular-nums text-white">
                            {Number(temperature).toFixed(1)}
                        </span>
                    </div>

                    <input
                        type="range"
                        min="0"
                        max="2"
                        step="0.1"
                        value={temperature}
                        onChange={(event) =>
                            setData('temperature', Number(event.target.value))
                        }
                        aria-label="Temperature"
                        className="mt-3 w-full accent-sky-400"
                    />

                    <div className="mt-1 flex justify-between text-[12px] text-white/40">
                        <span>Focused</span>
                        <span>Creative</span>
                    </div>

                    {errors.temperature && (
                        <p className="mt-1.5 text-[12px] text-rose-300">
                            {errors.temperature}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
