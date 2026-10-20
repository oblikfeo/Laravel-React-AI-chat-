import { useRef, useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * Отправка запросов Студии.
 *
 * Картинка рисуется несколько секунд, и всё это время её нет в базе.
 * Заглушка появляется в очереди сразу по нажатию: иначе экран
 * замирает, и непонятно, принят ли запрос.
 *
 * Запросы идут параллельно: пока рисуется одна картинка, можно
 * запустить следующую или уйти в другой раздел. Обычный переход
 * Inertia отменил бы предыдущий запрос вместе с его результатом.
 */
export default function useStudioRequests() {
    const [pending, setPending] = useState([]);
    const counter = useRef(0);

    // Защита от двойного нажатия: каждая работа стоит денег.
    const [busy, setBusy] = useState(false);

    // Отказ сервера принять запрос: слишком большой файл и подобное.
    const [refusal, setRefusal] = useState(null);

    const send = (url, values, { section, prompt, count = 1, form = false }) => {
        const ids = Array.from(
            { length: count },
            () => `local-${(counter.current += 1)}`,
        );

        setPending((previous) => [
            ...ids.map((id) => ({
                id,
                section,
                status: 'pending',
                prompt,
                createdAt: new Date().toISOString(),
            })),
            ...previous,
        ]);

        setBusy(true);
        setTimeout(() => setBusy(false), 900);
        setRefusal(null);

        router.post(url, values, {
            async: true,
            preserveScroll: true,
            preserveState: true,
            forceFormData: form,
            // Запрос не принят: без сообщения заглушка в очереди
            // просто исчезла бы, и человек не понял бы почему.
            onError: (errors) =>
                setRefusal(
                    Object.values(errors)[0] ?? 'This request was not accepted.',
                ),
            onFinish: () =>
                setPending((previous) =>
                    previous.filter((item) => !ids.includes(item.id)),
                ),
        });
    };

    return { send, pending, busy, refusal };
}
