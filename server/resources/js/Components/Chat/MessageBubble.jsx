import { LogoMark } from '@/Components/Layout/Logo';

/**
 * Одно сообщение диалога.
 *
 * Сообщение пользователя — стеклянный пузырь справа.
 * Ответ модели — текст во всю ширину слева со значком, без рамки:
 * так читается длинный ответ, как в большинстве AI-интерфейсов.
 */
export default function MessageBubble({ message }) {
    const isUser = message.role === 'user';

    if (isUser) {
        return (
            <div className="flex justify-end">
                <div className="max-w-[80%] rounded-3xl rounded-br-lg border border-white/[0.12] bg-white/[0.09] px-5 py-3.5 backdrop-blur-xl">
                    <p className="whitespace-pre-wrap text-[15px] leading-relaxed text-white">
                        {message.content}
                    </p>
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
                деталь, она хранится в базе и видна только в админке. */}
            <div className="min-w-0 flex-1 pt-1">
                <p className="whitespace-pre-wrap text-[15px] leading-relaxed text-white/90">
                    {message.content}
                </p>
            </div>
        </div>
    );
}
