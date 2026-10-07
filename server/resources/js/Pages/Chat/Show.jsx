import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import PromptComposer from '@/Components/Home/PromptComposer';
import MessageBubble from '@/Components/Chat/MessageBubble';
import TypingIndicator from '@/Components/Chat/TypingIndicator';
import GuestNotice from '@/Components/Layout/GuestNotice';
import CharacterAvatar from '@/Components/Characters/CharacterAvatar';

/**
 * Режим диалога: лента сообщений с полем ввода, закреплённым снизу.
 *
 * Сообщение появляется сразу, а ответ запрашивается отдельным запросом
 * уже из открытого диалога: так человек не смотрит на полосу загрузки,
 * а видит свой текст и индикатор набора.
 */
export default function ChatShow({ chat, messages, awaitingReply, character }) {
    const { defaultModel, guest } = usePage().props;
    const [draft, setDraft] = useState('');
    const [files, setFiles] = useState([]);
    const [model, setModel] = useState(chat.modelKey ?? defaultModel);
    const [pending, setPending] = useState(null);
    const bottomRef = useRef(null);

    const askedRef = useRef(false);

    // Ответы, которые уже были на экране к моменту отрисовки.
    // Вычисляется при первом рендере страницы, поэтому новый ответ
    // сразу попадает в печать: если ждать onSuccess, Inertia успевает
    // показать текст целиком, и печать начинается со второго кадра.
    const seenRef = useRef(null);

    if (seenRef.current === null) {
        seenRef.current = new Set(
            messages.filter((m) => m.role === 'assistant').map((m) => m.id),
        );
    }

    const lastReply = [...messages].reverse().find((m) => m.role === 'assistant');
    const typingId =
        lastReply && !seenRef.current.has(lastReply.id) ? lastReply.id : null;

    // Сообщения, уже побывавшие на экране, не анимируем повторно:
    // иначе при каждом обновлении ленты дёргается вся переписка.
    const shownRef = useRef(null);

    if (shownRef.current === null) {
        shownRef.current = new Set(messages.map((m) => m.id));
    }

    const list = pending
        ? [...messages, { id: 'pending', role: 'user', content: pending }]
        : messages;

    const waiting = Boolean(pending) || awaitingReply;

    const countRef = useRef(list.length);

    useEffect(() => {
        // Прокручиваем, только когда сообщений стало больше: иначе
        // страница дёргается при каждом обновлении ленты.
        if (list.length > countRef.current || waiting) {
            bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
        }

        countRef.current = list.length;
    }, [list.length, waiting]);

    // Напечатанный ответ становится виденным: следующая перерисовка
    // покажет его целиком, а не запустит печать заново.
    useEffect(() => {
        if (typingId) {
            seenRef.current.add(typingId);
        }
    }, [typingId]);

    // Ответ ещё не получен — запрашиваем его, оказавшись на странице.
    useEffect(() => {
        if (!awaitingReply || askedRef.current) {
            return;
        }

        askedRef.current = true;

        router.post(
            `/chats/${chat.id}/reply`,
            {},
            {
                preserveScroll: true,
                // Полоса загрузки тут лишняя: ожидание уже показано
                // индикатором набора в ленте сообщений.
                showProgress: false,
                onFinish: () => {
                    askedRef.current = false;
                },
            },
        );
    }, [awaitingReply, chat.id]);

    const submit = () => {
        const text = draft.trim();

        if (!text && !files.length) {
            return;
        }

        setPending(text || 'Attached file');
        setDraft('');
        const sending = files;
        setFiles([]);

        router.post(
            `/chats/${chat.id}/messages`,
            { message: text, files: sending },
            {
                forceFormData: true,
                preserveScroll: true,
                showProgress: false,
                // Сообщение не должно пропасть при сбое: возвращаем
                // его в поле ввода вместе с файлами.
                onError: () => {
                    setDraft(text);
                    setFiles(sending);
                },
                onFinish: () => setPending(null),
            },
        );
    };

    // Смена модели сохраняется за чатом: выбор делается один раз,
    // а не при каждом сообщении.
    // Повтор после сбоя: удаляем неудачный ответ и просим заново.
    const retry = () => {
        router.post(
            `/chats/${chat.id}/retry`,
            {},
            { preserveScroll: true, showProgress: false },
        );
    };

    const changeModel = (key) => {
        setModel(key);

        router.put(
            `/chats/${chat.id}/model`,
            { model: key },
            { preserveScroll: true, preserveState: true, showProgress: false },
        );
    };

    return (
        <>
            <Head title={`${chat.title} — Uncensia`} />

            <div className="flex min-h-0 flex-1 flex-col px-3 pb-4 sm:px-6 sm:pb-6">
                {/* Окно диалога — один элемент: шапка персонажа, переписка
                    и поле ввода лежат на общей подложке. Прокручивается
                    только переписка внутри окна, поэтому его углы всегда
                    целы. Обрезку не ставим на само окно: список моделей
                    раскрывается из поля ввода вверх и должен быть виден. */}
                <div className="mx-auto flex min-h-0 w-full max-w-[820px] flex-1 flex-col rounded-[28px] border border-white/[0.08] bg-slate-950/45 backdrop-blur-md">
                    {character && <CharacterHeader character={character} />}

                    <div
                        className={`chat-scroll min-h-0 flex-1 overflow-y-auto scrollbar-thin ${
                            character ? '' : 'rounded-t-[27px]'
                        }`}
                    >
                        <div className="space-y-7 p-4 sm:p-6">
                            {/* Персонаж без вступления: пустое окно
                                выглядело бы сломанным. */}
                            {character && list.length === 0 && !waiting && (
                                <p className="py-6 text-center text-[14px] text-white/45">
                                    Say hello to {character.name} — the
                                    conversation is saved and continues every
                                    time you come back.
                                </p>
                            )}

                            {list.map((message) => {
                                const fresh = !shownRef.current.has(message.id);
                                shownRef.current.add(message.id);

                                return (
                                    <MessageBubble
                                        key={message.id}
                                        message={message}
                                        typing={message.id === typingId}
                                        fresh={fresh}
                                        onRetry={retry}
                                        character={character}
                                    />
                                );
                            })}

                            {waiting && <TypingIndicator character={character} />}

                            <div ref={bottomRef} />
                        </div>
                    </div>

                    <div className="shrink-0 border-t border-white/[0.08]">
                        {guest && (
                            <div className="-mb-3 px-4 pt-4 sm:px-5">
                                <GuestNotice />
                            </div>
                        )}

                        <PromptComposer
                            value={draft}
                            onChange={setDraft}
                            onSubmit={submit}
                            model={model}
                            // В диалоге с персонажем модель задаёт его
                            // автор, выбирать её здесь нельзя.
                            onModelChange={character ? undefined : changeModel}
                            files={files}
                            onFilesChange={setFiles}
                            busy={waiting || guest?.remaining === 0}
                            placeholder={
                                character
                                    ? `Message ${character.name}…`
                                    : 'Ask anything…'
                            }
                            minRows={1}
                            autoFocus
                            embedded
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

/**
 * Шапка окна диалога с персонажем: с кем идёт разговор.
 */
function CharacterHeader({ character }) {
    return (
        <div className="flex shrink-0 items-center gap-3.5 border-b border-white/[0.08] p-3.5 sm:px-5">
            <CharacterAvatar
                name={character.name}
                src={character.avatar}
                className="h-12 w-12 rounded-2xl text-xl"
            />

            <div className="min-w-0 flex-1">
                <p className="truncate text-[15px] font-semibold text-white">
                    {character.name}
                </p>
                <p className="truncate text-[13px] text-white/50">
                    {character.description ||
                        (character.author ? `by ${character.author}` : 'Character')}
                </p>
            </div>

            <Link
                href="/characters"
                className="shrink-0 rounded-full border border-white/[0.14] px-3.5 py-1.5 text-[13px] text-white/70 transition hover:bg-white/10 hover:text-white"
            >
                All characters
            </Link>
        </div>
    );
}

// Постоянный макет: не пересоздаётся при переходах,
// поэтому боковое меню сохраняет состояние.
ChatShow.layout = (page) => <MainLayout>{page}</MainLayout>;
