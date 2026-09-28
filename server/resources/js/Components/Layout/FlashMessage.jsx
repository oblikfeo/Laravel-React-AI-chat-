import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, X } from 'lucide-react';

/**
 * Сообщения от сервера: ошибки и подтверждения.
 *
 * Раньше сервер слал их, а интерфейс молчал: при исчерпании лимита
 * или сбое оплаты человек не понимал, почему ничего не произошло.
 *
 * Успех исчезает сам, ошибка ждёт, пока её закроют: её надо прочитать.
 */
export default function FlashMessage() {
    const { flash } = usePage().props;
    const [dismissed, setDismissed] = useState(null);

    const error = flash?.error;
    const success = flash?.success;
    const message = error ?? success;
    const isError = Boolean(error);

    useEffect(() => {
        setDismissed(null);
    }, [message]);

    useEffect(() => {
        if (!message || isError) {
            return;
        }

        const timer = setTimeout(() => setDismissed(message), 4000);

        return () => clearTimeout(timer);
    }, [message, isError]);

    if (!message || dismissed === message) {
        return null;
    }

    const Icon = isError ? AlertCircle : CheckCircle2;

    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4">
            <div
                role="status"
                className={`pointer-events-auto flex max-w-[520px] animate-[message-in_0.25s_ease-out] items-start gap-3 rounded-2xl border px-4 py-3 shadow-xl shadow-black/30 backdrop-blur-xl ${
                    isError
                        ? 'border-rose-400/25 bg-rose-950/80 text-rose-50'
                        : 'border-emerald-400/25 bg-emerald-950/80 text-emerald-50'
                }`}
            >
                <Icon
                    className={`mt-0.5 h-4 w-4 shrink-0 ${
                        isError ? 'text-rose-300' : 'text-emerald-300'
                    }`}
                    strokeWidth={2}
                />

                <p className="text-[14px] leading-snug">{message}</p>

                <button
                    type="button"
                    onClick={() => setDismissed(message)}
                    aria-label="Dismiss"
                    className="-mr-1 -mt-0.5 shrink-0 rounded-lg p-1 opacity-50 transition hover:bg-white/10 hover:opacity-100"
                >
                    <X className="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
    );
}
