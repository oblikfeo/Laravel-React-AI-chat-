import { useEffect, useRef, useState } from 'react';

/** Дольше этого запись останавливается сама. */
const MAX_SECONDS = 120;

/**
 * Голосовой ввод: запись с микрофона и её расшифровка в текст.
 *
 * Текст отдаётся в поле ввода, а не отправляется сразу: человек видит,
 * что распознано, и может поправить перед отправкой.
 *
 * Состояния: idle — ждём, recording — пишем, transcribing — распознаём.
 */
export default function useVoiceInput(onText) {
    const [state, setState] = useState('idle');
    const [seconds, setSeconds] = useState(0);
    const [error, setError] = useState(null);

    const recorderRef = useRef(null);
    const chunksRef = useRef([]);
    const timerRef = useRef(null);
    const cancelledRef = useRef(false);

    // Свежий обработчик: пока шла запись, человек мог дописать текст.
    const onTextRef = useRef(onText);
    onTextRef.current = onText;

    // Микрофон не должен остаться включённым после ухода со страницы.
    useEffect(
        () => () => {
            cancelledRef.current = true;
            clearInterval(timerRef.current);
            recorderRef.current?.stream?.getTracks().forEach((track) => track.stop());
        },
        [],
    );

    const supported =
        typeof window !== 'undefined' &&
        Boolean(navigator.mediaDevices?.getUserMedia) &&
        typeof window.MediaRecorder !== 'undefined';

    const transcribe = async (blob) => {
        setState('transcribing');

        const form = new FormData();
        const extension = blob.type.includes('mp4') ? 'm4a' : blob.type.includes('ogg') ? 'ogg' : 'webm';
        form.append('audio', blob, `voice.${extension}`);

        try {
            const { data } = await window.axios.post('/voice/transcribe', form);

            if (!cancelledRef.current && data.text) {
                onTextRef.current(data.text);
            }
        } catch (failure) {
            setError(
                failure.response?.data?.message ??
                    failure.response?.data?.errors?.audio?.[0] ??
                    'Could not recognise the recording. Please try again.',
            );
        } finally {
            if (!cancelledRef.current) {
                setState('idle');
            }
        }
    };

    const stop = () => {
        clearInterval(timerRef.current);
        recorderRef.current?.stop();
    };

    const start = async () => {
        setError(null);

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const recorder = new MediaRecorder(stream);

            chunksRef.current = [];

            recorder.ondataavailable = (event) => {
                if (event.data.size) {
                    chunksRef.current.push(event.data);
                }
            };

            recorder.onstop = () => {
                stream.getTracks().forEach((track) => track.stop());

                const blob = new Blob(chunksRef.current, {
                    type: recorder.mimeType || 'audio/webm',
                });

                if (blob.size) {
                    transcribe(blob);
                } else {
                    setState('idle');
                }
            };

            recorder.start();
            recorderRef.current = recorder;
            setSeconds(0);
            setState('recording');

            timerRef.current = setInterval(() => {
                setSeconds((value) => {
                    if (value + 1 >= MAX_SECONDS) {
                        stop();
                    }

                    return value + 1;
                });
            }, 1000);
        } catch {
            setError('Allow access to the microphone to dictate a message.');
            setState('idle');
        }
    };

    const toggle = () => {
        if (state === 'recording') {
            stop();
        } else if (state === 'idle') {
            start();
        }
    };

    return { state, seconds, error, supported, toggle, dismissError: () => setError(null) };
}
