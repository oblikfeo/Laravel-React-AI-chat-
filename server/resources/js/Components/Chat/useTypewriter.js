import { useEffect, useRef, useState } from 'react';

/**
 * Символов в секунду.
 *
 * 900 было незаметно: короткий ответ дорисовывался за десятые доли
 * секунды. 45 — примерно вдвое быстрее чтения вслух: видно, что текст
 * набирается, и при этом не приходится ждать.
 */
const SPEED = 45;

/** Длинный ответ печатаем быстрее, иначе конца не дождёшься. */
function speedFor(length) {
    if (length > 1200) {
        return 200;
    }

    if (length > 400) {
        return 110;
    }

    return SPEED;
}

/**
 * Постепенное проявление текста.
 *
 * Ответ приходит целиком, поэтому «печать» рисуется на стороне
 * браузера. Печатается только свежий ответ: при открытии старого
 * диалога текст должен быть на месте сразу.
 *
 * Уважает системную настройку «уменьшить движение».
 */
export default function useTypewriter(text, enabled = true) {
    const [shown, setShown] = useState(enabled ? '' : text);
    const frame = useRef(null);

    // Один и тот же ответ печатаем только раз: перерисовка страницы
    // не должна запускать анимацию заново.
    const played = useRef(false);

    useEffect(() => {
        if (!enabled || played.current) {
            setShown(text);

            return;
        }

        played.current = true;

        const reduced = window.matchMedia?.(
            '(prefers-reduced-motion: reduce)',
        )?.matches;

        if (reduced) {
            setShown(text);

            return;
        }

        let start = null;

        const step = (now) => {
            start ??= now;

            const count = Math.floor(((now - start) / 1000) * speedFor(text.length));

            if (count >= text.length) {
                setShown(text);

                return;
            }

            setShown(text.slice(0, count));
            frame.current = requestAnimationFrame(step);
        };

        frame.current = requestAnimationFrame(step);

        return () => cancelAnimationFrame(frame.current);
    }, [text, enabled]);

    return shown;
}
