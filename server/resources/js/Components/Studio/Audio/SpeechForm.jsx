import { useEffect, useState } from 'react';
import { Mic, Send, Gauge, Cpu } from 'lucide-react';
import { Panel, NotReady, SubmitBar } from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';
import { priceOf } from '@/Components/Studio/price';
import Counter from '@/Components/Studio/Audio/Counter';

/** Скорость чтения: крайние значения звучат неестественно. */
const SPEEDS = [
    { key: '0.75', label: 'Slow', description: 'Calm, easy to follow' },
    { key: '1', label: 'Normal', description: 'Natural pace' },
    { key: '1.25', label: 'Fast', description: 'Brisk delivery' },
    { key: '1.5', label: 'Faster', description: 'For quick listening' },
];

/** Озвучка текста. */
export default function SpeechForm({
    models,
    voices,
    defaultModel,
    defaultVoice,
    maxCharacters,
    available,
    busy,
    limit,
    incoming = null,
    onSubmit,
}) {
    const [text, setText] = useState('');

    // Просьба, принесённая из чата или ленты: заполняем форму.
    useEffect(() => {
        if (!incoming) {
            return;
        }

        setText(spoken(incoming.prompt));
    }, [incoming]);
    const [model, setModel] = useState(defaultModel);
    const [voice, setVoice] = useState(defaultVoice);
    const [speed, setSpeed] = useState('1');

    const exhausted = limit?.remaining === 0;
    const canSubmit =
        text.trim().length > 0 &&
        text.length <= maxCharacters &&
        !busy &&
        available &&
        !exhausted;

    const submit = (event) => {
        event.preventDefault();

        if (canSubmit) {
            onSubmit({
                text: text.trim(),
                model,
                voice,
                speed: Number(speed),
            });

            // Запрос ушёл — форма готова к следующему тексту.
            setText('');
        }
    };

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <textarea
                value={text}
                onChange={(event) => setText(event.target.value)}
                placeholder="Enter the text you want to convert to speech…"
                rows={4}
                className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
            />

            <Counter value={text.length} max={maxCharacters} />

            <div className="mt-3 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={voice}
                    onChange={setVoice}
                    options={voices}
                    label="Voice"
                    icon={Mic}
                />

                <Dropdown
                    value={model}
                    onChange={setModel}
                    options={models}
                    label="Quality"
                    icon={Cpu}
                />

                <Dropdown
                    value={speed}
                    onChange={setSpeed}
                    options={SPEEDS}
                    label="Speed"
                    icon={Gauge}
                />

                <SubmitBar icon={Send}
                    busy={busy}
                    canSubmit={canSubmit}
                    limit={limit}
                    price={priceOf(
                        models.find((item) => item.key === model)?.price,
                        { characters: text.length },
                    )}
                />
            </div>
        </Panel>
    );
}

/**
 * Что озвучить из просьбы в чате: текст после двоеточия, если он есть.
 *
 * «Озвучь: добро пожаловать» — читать нужно «добро пожаловать».
 */
function spoken(prompt = '') {
    const index = prompt.indexOf(':');

    return index >= 0 ? prompt.slice(index + 1).trim() : prompt;
}
