import { useEffect, useRef, useState } from 'react';

/** Символов в секунду: быстрее чтения, но видно, что текст набирается. */
const SPEED = 900;

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

    useEffect(() => {
        if (!enabled) {
            setShown(text);

            return;
        }

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

            const count = Math.floor(((now - start) / 1000) * SPEED);

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
