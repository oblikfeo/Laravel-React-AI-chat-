import { useEffect, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import {
    X,
    UserRound,
    MessageSquareText,
    FileText,
    Brain,
    BarChart3,
    Settings as SettingsIcon,
    Trash2,
} from 'lucide-react';
import AssetPicker from '@/Components/Studio/AssetPicker';
import General from '@/Components/Characters/Sections/General';
import Instructions from '@/Components/Characters/Sections/Instructions';
import Context from '@/Components/Characters/Sections/Context';
import Memories from '@/Components/Characters/Sections/Memories';
import Insights from '@/Components/Characters/Sections/Insights';
import CharacterSettings from '@/Components/Characters/Sections/CharacterSettings';

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
