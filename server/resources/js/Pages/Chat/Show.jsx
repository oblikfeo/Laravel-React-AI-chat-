import { useEffect, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import PromptComposer from '@/Components/Home/PromptComposer';
import MessageBubble from '@/Components/Chat/MessageBubble';
import TypingIndicator from '@/Components/Chat/TypingIndicator';

/**
 * Режим диалога: лента сообщений с полем ввода, закреплённым снизу.
 *
 * Пока сервер готовит ответ, сообщение пользователя показывается сразу,
 * а под ним — индикатор набора. Так интерфейс не выглядит зависшим.
 */
export default function ChatShow({ chat, messages }) {
    const { defaultModel } = usePage().props;
    const [draft, setDraft] = useState('');
    const [files, setFiles] = useState([]);
    const [model, setModel] = useState(chat.modelKey ?? defaultModel);
    const [pending, setPending] = useState(null);
    const bottomRef = useRef(null);

    const list = pending
        ? [...messages, { id: 'pending', role: 'user', content: pending }]
        : messages;

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [list.length, pending]);

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
                    <div className="mx-auto w-full max-w-[760px] space-y-7 px-4 py-6 sm:px-6">
                        {list.map((message) => (
                            <MessageBubble key={message.id} message={message} />
                        ))}

                        {pending && <TypingIndicator />}

                        <div ref={bottomRef} />
                    </div>
                </div>

                <div className="shrink-0 px-4 pb-6 pt-2 sm:px-6">
                    <div className="mx-auto w-full max-w-[760px]">
                        <PromptComposer
                            value={draft}
                            onChange={setDraft}
                            onSubmit={submit}
                            model={model}
                            onModelChange={changeModel}
                            files={files}
                            onFilesChange={setFiles}
                            busy={Boolean(pending)}
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
