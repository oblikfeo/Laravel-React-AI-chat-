import { LogoMark } from '@/Components/Layout/Logo';
import Markdown from '@/Components/Chat/Markdown';
import MessageAttachments from '@/Components/Chat/MessageAttachments';
import useTypewriter from '@/Components/Chat/useTypewriter';

/**
 * Одно сообщение диалога.
 *
 * Сообщение пользователя — стеклянный пузырь справа.
 * Ответ модели — панель слева со значком: фон с планетой местами
 * светлый, и белый текст на нём терялся, поэтому под ответом лежит
 * затемнённое стекло. Пузырь пользователя оставлен светлее, чтобы
 * две стороны диалога различались с первого взгляда.
 */
export default function MessageBubble({ message, typing = false }) {
    const isUser = message.role === 'user';

    // Печатается только свежий ответ: при открытии старого диалога
    // текст должен быть на месте сразу.
    const shown = useTypewriter(message.content ?? '', typing);

    if (isUser) {
        return (
            <div className="flex justify-end">
                <div className="max-w-[80%] rounded-3xl rounded-br-lg border border-white/[0.12] bg-white/[0.09] px-5 py-3.5 backdrop-blur-xl">
                    <MessageAttachments items={message.attachments} />

                    {message.content && (
                        <p className="whitespace-pre-wrap text-[15px] leading-relaxed text-white">
                            {message.content}
                        </p>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className="flex gap-3.5">
            <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.06]">
                <LogoMark className="h-5 w-5" />
            </span>

            {/* Название модели намеренно не показываем: это внутренняя
                деталь, она хранится в базе и видна только в админке.

                Ответ приходит в Markdown, сообщение пользователя — обычным
                текстом: звёздочки в его словах разметкой быть не должны. */}
            <div className="min-w-0 flex-1 rounded-3xl rounded-tl-lg border border-white/[0.07] bg-slate-950/70 px-5 py-4 shadow-lg shadow-black/20 backdrop-blur-xl">
                <Markdown>{shown}</Markdown>
            </div>
        </div>
    );
}
