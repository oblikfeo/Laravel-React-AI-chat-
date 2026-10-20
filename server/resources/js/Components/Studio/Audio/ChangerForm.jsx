import { useEffect, useRef, useState } from 'react';
import { Mic, AudioWaveform, Send, Upload, Square, X } from 'lucide-react';
import { Panel, NotReady, SubmitBar } from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';
import { formatSeconds } from '@/Components/Studio/Audio/duration';

/** Смена голоса в записи. */
export default function ChangerForm({
    voices,
    defaultVoice,
    available,
    busy,
    limit,
    onSubmit,
}) {
    const [file, setFile] = useState(null);
    const [voice, setVoice] = useState(defaultVoice);
    const [recording, setRecording] = useState(false);
    const [seconds, setSeconds] = useState(0);
    const fileRef = useRef(null);
    const recorderRef = useRef(null);
    const chunksRef = useRef([]);
    const timerRef = useRef(null);

    // Запись останавливаем при уходе со страницы: микрофон не должен
    // остаться включённым.
    useEffect(
        () => () => {
            recorderRef.current?.stream
                ?.getTracks()
                .forEach((track) => track.stop());
            clearInterval(timerRef.current);
        },
        [],
    );

    const startRecording = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });

            const recorder = new MediaRecorder(stream);
            chunksRef.current = [];

            recorder.ondataavailable = (event) => {
                if (event.data.size) {
                    chunksRef.current.push(event.data);
                }
            };

            recorder.onstop = () => {
                const blob = new Blob(chunksRef.current, {
                    type: recorder.mimeType || 'audio/webm',
                });

                setFile(
                    new File([blob], 'recording.webm', { type: blob.type }),
                );

                stream.getTracks().forEach((track) => track.stop());
            };

            recorder.start();
            recorderRef.current = recorder;
            setRecording(true);
            setSeconds(0);

            timerRef.current = setInterval(
                () => setSeconds((value) => value + 1),
                1000,
            );
        } catch {
            // Отказ в доступе к микрофону — обычное дело, загрузка
            // файлом остаётся.
            setRecording(false);
        }
    };

    const stopRecording = () => {
        recorderRef.current?.stop();
        clearInterval(timerRef.current);
        setRecording(false);
    };

    const exhausted = limit?.remaining === 0;
    const canSubmit = Boolean(file) && !busy && available && !exhausted;

    const submit = (event) => {
        event.preventDefault();

        if (canSubmit) {
            onSubmit({ recording: file, voice });
            setFile(null);
        }
    };

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <input
                ref={fileRef}
                type="file"
                accept="audio/*"
                className="hidden"
                onChange={(event) => {
                    setFile(event.target.files[0] ?? null);
                    event.target.value = '';
                }}
            />

            {file ? (
                <div className="flex items-center gap-3 rounded-xl border border-white/[0.12] bg-white/[0.03] p-3">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/[0.07] text-white/60">
                        <AudioWaveform className="h-5 w-5" strokeWidth={1.75} />
                    </span>

                    <span className="min-w-0 flex-1">
                        <span className="block truncate text-[14px] text-white/85">
                            {file.name}
                        </span>
                        <span className="text-[12px] text-white/35">
                            {Math.round(file.size / 1024)} KB
                        </span>
                    </span>

                    <button
                        type="button"
                        onClick={() => setFile(null)}
                        aria-label="Remove"
                        className="rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white"
                    >
                        <X className="h-4 w-4" strokeWidth={1.75} />
                    </button>
                </div>
            ) : (
                <div className="flex flex-wrap items-center gap-2.5 rounded-xl border border-dashed border-white/[0.14] px-4 py-3.5">
                    <span className="mr-auto text-[13px] text-white/50">
                        {recording
                            ? `Recording… ${formatSeconds(seconds)}`
                            : 'Upload or record your speech'}
                    </span>

                    {recording ? (
                        <button
                            type="button"
                            onClick={stopRecording}
                            className="flex h-9 items-center gap-2 rounded-full bg-rose-500 px-4 text-[13px] font-semibold text-white transition hover:bg-rose-400"
                        >
                            <Square className="h-3.5 w-3.5" strokeWidth={2.5} />
                            Stop
                        </button>
                    ) : (
                        <>
                            <button
                                type="button"
                                onClick={() => fileRef.current?.click()}
                                className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-4 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                            >
                                <Upload className="h-4 w-4" strokeWidth={1.75} />
                                Upload
                            </button>

                            <button
                                type="button"
                                onClick={startRecording}
                                className="flex h-9 items-center gap-2 rounded-full bg-white px-4 text-[13px] font-semibold text-black transition hover:bg-white/90"
                            >
                                <Mic className="h-4 w-4" strokeWidth={2} />
                                Record
                            </button>
                        </>
                    )}
                </div>
            )}

            <div className="mt-4 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={voice}
                    onChange={setVoice}
                    options={voices}
                    label="New voice"
                    icon={Mic}
                />

                <SubmitBar icon={Send} busy={busy} canSubmit={canSubmit} limit={limit} />
            </div>
        </Panel>
    );
}
