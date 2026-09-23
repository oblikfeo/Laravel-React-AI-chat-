import { useEffect, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import PromptComposer from '@/Components/Home/PromptComposer';
import MessageBubble from '@/Components/Chat/MessageBubble';
import TypingIndicator from '@/Components/Chat/TypingIndicator';

/**
 * Режим диалога: лента сообщений с полем ввода, закреплённым снизу.
 *
 * Сообщение появляется сразу, а ответ запрашивается отдельным запросом
 * уже из открытого диалога: так человек не смотрит на полосу загрузки,
 * а видит свой текст и индикатор набора.
 */
export default function ChatShow({ chat, messages, awaitingReply }) {
    const { defaultModel } = usePage().props;
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

    const list = pending
        ? [...messages, { id: 'pending', role: 'user', content: pending }]
        : messages;

    const waiting = Boolean(pending) || awaitingReply;

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
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
                onFinish: () => setPending(null),
            },
        );
    };

    // Смена модели сохраняется за чатом: выбор делается один раз,
    // а не при каждом сообщении.
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

            <div className="flex min-h-0 flex-1 flex-col">
                <div className="flex-1 overflow-y-auto scrollbar-thin">
                    <div className="mx-auto w-full max-w-[820px] px-4 py-6 sm:px-6">
                        {/* Общая подложка под всей перепиской: выделяет
                            область разговора на фоне с планетой. */}
                        <div className="space-y-7 rounded-[28px] border border-white/[0.06] bg-slate-950/45 p-4 backdrop-blur-md sm:p-6">
                            {list.map((message) => (
                                <MessageBubble
                                    key={message.id}
                                    message={message}
                                    typing={message.id === typingId}
                                />
                            ))}

                            {waiting && <TypingIndicator />}
                        </div>

                        <div ref={bottomRef} />
                    </div>
                </div>

                <div className="shrink-0 px-4 pb-6 pt-2 sm:px-6">
                    <div className="mx-auto w-full max-w-[820px]">
                        <PromptComposer
                            value={draft}
                            onChange={setDraft}
                            onSubmit={submit}
                            model={model}
                            onModelChange={changeModel}
                            files={files}
                            onFilesChange={setFiles}
                            busy={waiting}
                            placeholder="Ask anything…"
                            minRows={1}
                            autoFocus
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

// Постоянный макет: не пересоздаётся при переходах,
// поэтому боковое меню сохраняет состояние.
ChatShow.layout = (page) => <MainLayout>{page}</MainLayout>;
