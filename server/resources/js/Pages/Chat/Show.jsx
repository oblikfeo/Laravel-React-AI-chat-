import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
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
    const [draft, setDraft] = useState('');
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

        if (!text) {
            return;
        }

        setPending(text);
        setDraft('');

        router.post(
            `/chats/${chat.id}/messages`,
            { message: text },
            {
                preserveScroll: true,
                onFinish: () => setPending(null),
            },
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
