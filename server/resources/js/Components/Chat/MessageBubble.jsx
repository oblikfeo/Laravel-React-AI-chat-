import { RotateCw } from 'lucide-react';
import { LogoMark } from '@/Components/Layout/Logo';
import Markdown from '@/Components/Chat/Markdown';
import MessageAttachments from '@/Components/Chat/MessageAttachments';
import useTypewriter from '@/Components/Chat/useTypewriter';
import ModelChangeMark from '@/Components/Chat/ModelChangeMark';
import CharacterAvatar from '@/Components/Characters/CharacterAvatar';

/**
 * Одно сообщение диалога.
 *
 * Сообщение пользователя — стеклянный пузырь справа.
 * Ответ модели — текст слева со значком, прямо на подложке окна
 * диалога: читаемость на фоне с планетой даёт она, поэтому своей
 * рамки у ответа нет.
 */
export default function MessageBubble({
    message,
    typing = false,
    fresh = false,
    onRetry,
    character = null,
}) {
    const isUser = message.role === 'user';

    // Печатается только свежий ответ: при открытии старого диалога
    // текст должен быть на месте сразу.
    const shown = useTypewriter(message.content ?? '', typing);

    // Ответ, которого не случилось: модель не отозвалась, и вместо
    // текста сохранилось извинение. Предлагаем повторить.
    const failed =
        message.role === 'assistant' &&
        message.model === null &&
        message.content?.startsWith("I'm having trouble");

    if (message.role === 'system') {
        return <ModelChangeMark label={message.content} />;
    }

    // Анимация только у новых сообщений: если вешать её на все,
    // при каждой перерисовке ленты дёргается вся переписка.
    const enter = fresh ? 'animate-[message-in_0.3s_ease-out]' : '';

    if (isUser) {
        return (
            <div className={`flex justify-end ${enter}`}>
                <div className="max-w-[80%] rounded-3xl rounded-br-lg border border-white/[0.10] bg-white/[0.08] px-5 py-3">
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
        <div className={`flex gap-3.5 ${enter}`}>
            {/* В диалоге с персонажем отвечает он, а не мы: вместо
                нашего значка стоит его аватар. */}
            {character ? (
                <CharacterAvatar
                    name={character.name}
                    src={character.avatar}
                    className="mt-0.5 h-8 w-8 rounded-full text-sm"
                />
            ) : (
                <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[0.06]">
                    <LogoMark className="h-5 w-5" />
                </span>
            )}

            {/* Название модели намеренно не показываем: это внутренняя
                деталь, она хранится в базе и видна только в админке.

                Ответ приходит в Markdown, сообщение пользователя — обычным
                текстом: звёздочки в его словах разметкой быть не должны. */}
            <div
                // Ответ лежит прямо на подложке окна. Рамка остаётся
                // только у несостоявшегося ответа: его надо выделить.
                className={`min-w-0 flex-1 ${
                    failed
                        ? 'rounded-2xl border border-rose-400/20 bg-rose-950/40 px-4 py-3.5'
                        : 'pt-1'
                }`}
            >
                <Markdown>{shown}</Markdown>

                {failed && onRetry && (
                    <button
                        type="button"
                        onClick={onRetry}
                        className="mt-3 flex items-center gap-1.5 rounded-full border border-white/[0.14] px-3 py-1.5 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                    >
                        <RotateCw className="h-3.5 w-3.5" strokeWidth={2} />
                        Try again
                    </button>
                )}
            </div>
        </div>
    );
}
