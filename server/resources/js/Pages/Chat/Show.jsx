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

    // Печатаем только тот ответ, что пришёл при нас: при открытии
    // старого диалога текст должен быть на месте сразу.
    const typingIdRef = useRef(null);
    const askedRef = useRef(false);

    const list = pending
        ? [...messages, { id: 'pending', role: 'user', content: pending }]
        : messages;

    const waiting = Boolean(pending) || awaitingReply;

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [list.length, waiting]);

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
                onSuccess: (page) => {
                    const last = page.props.messages?.at(-1);

                    if (last?.role === 'assistant') {
                        typingIdRef.current = last.id;
                    }
                },
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
            { preserveScroll: true, preserveState: true },
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
                                    typing={message.id === typingIdRef.current}
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
