import { useState } from 'react';
import { Volume2, Mic, Gauge } from 'lucide-react';
import { Panel, NotReady, Remaining } from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';

/** Скорость чтения: крайние значения звучат неестественно. */
const SPEEDS = [
    { key: '0.75', label: 'Slow', description: 'Calm, easy to follow' },
    { key: '1', label: 'Normal', description: 'Natural pace' },
    { key: '1.25', label: 'Fast', description: 'Brisk delivery' },
    { key: '1.5', label: 'Faster', description: 'For quick listening' },
];

/**
 * Озвучка текста.
 *
 * Голос выбирает провайдер по модели, поэтому в форме только то, что
 * человеку действительно нужно решить: чем читать и как быстро.
 */
export default function SpeechForm({
    models,
    defaultModel,
    maxCharacters = 4000,
    available,
    busy,
    limit,
    onSubmit,
}) {
    const [text, setText] = useState('');
    const [model, setModel] = useState(defaultModel);
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

        if (!canSubmit) {
            return;
        }

        onSubmit({
            text: text.trim(),
            model,
            speed: Number(speed),
        });
    };

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <textarea
                value={text}
                onChange={(event) => setText(event.target.value)}
                placeholder="Enter the text you want read out loud…"
                rows={4}
                className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
            />

            <div className="mt-1 text-right text-[12px] text-white/35">
                {text.length}/{maxCharacters}
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={model}
                    onChange={setModel}
                    options={models}
                    label="Voice"
                    icon={Mic}
                />

                <Dropdown
                    value={speed}
                    onChange={setSpeed}
                    options={SPEEDS}
                    label="Speed"
                    icon={Gauge}
                />

                <div className="ml-auto flex items-center gap-3">
                    <Remaining limit={limit} />

                    <button
                        type="submit"
                        disabled={!canSubmit}
                        className={`flex h-10 items-center gap-2 rounded-full px-5 text-[14px] font-semibold transition ${
                            canSubmit
                                ? 'bg-white text-black hover:bg-white/90'
                                : 'cursor-not-allowed border border-white/[0.12] text-white/35'
                        }`}
                    >
                        <Volume2 className="h-4 w-4" strokeWidth={2} />
                        {busy ? 'Reading…' : 'Generate'}
                    </button>
                </div>
            </div>
        </Panel>
    );
}
