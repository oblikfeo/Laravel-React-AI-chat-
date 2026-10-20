import { useEffect, useState } from 'react';
import { AudioWaveform, Send, Clock } from 'lucide-react';
import { Panel, NotReady, SubmitBar } from '@/Components/Studio/Controls';
import Dropdown from '@/Components/Studio/Dropdown';
import { priceOf } from '@/Components/Studio/price';
import Counter from '@/Components/Studio/Audio/Counter';
import { stepsBetween } from '@/Components/Studio/Audio/duration';

/** Звуковой эффект. */
export default function EffectForm({
    models,
    defaultModel,
    available,
    busy,
    limit,
    incoming = null,
    onSubmit,
}) {
    const [prompt, setPrompt] = useState('');

    // Просьба, принесённая из чата или ленты: заполняем форму.
    useEffect(() => {
        if (!incoming) {
            return;
        }

        setPrompt(incoming.prompt ?? '');
    }, [incoming]);
    const [model, setModel] = useState(defaultModel);
    const [duration, setDuration] = useState(null);

    const current = models.find((item) => item.key === model) ?? models[0];
    const durationOptions = stepsBetween(
        current?.minDuration,
        current?.maxDuration,
    );

    const chosenDuration =
        duration ?? String(current?.defaultDuration ?? durationOptions[0]?.key ?? 5);

    const exhausted = limit?.remaining === 0;
    const canSubmit =
        prompt.trim().length > 0 && !busy && available && !exhausted;

    const submit = (event) => {
        event.preventDefault();

        if (canSubmit) {
            onSubmit({
                prompt: prompt.trim(),
                model,
                duration: Number(chosenDuration),
            });

            setPrompt('');
        }
    };

    return (
        <Panel onSubmit={submit}>
            {!available && <NotReady />}

            <textarea
                value={prompt}
                onChange={(event) => setPrompt(event.target.value)}
                placeholder="Spacious braam suitable for high-impact movie trailer moments…"
                rows={3}
                className="w-full resize-none border-0 bg-transparent p-0 text-[16px] leading-relaxed text-white placeholder:text-white/40 focus:outline-none focus:ring-0"
            />

            <Counter value={prompt.length} max={450} />

            <div className="mt-3 flex flex-wrap items-center gap-2">
                <Dropdown
                    value={model}
                    onChange={(next) => {
                        setModel(next);
                        setDuration(null);
                    }}
                    options={models}
                    label="Kind"
                    icon={AudioWaveform}
                />

                {durationOptions.length > 0 && (
                    <Dropdown
                        value={chosenDuration}
                        onChange={setDuration}
                        options={durationOptions}
                        label="Length"
                        icon={Clock}
                    />
                )}

                <SubmitBar icon={Send}
                    busy={busy}
                    canSubmit={canSubmit}
                    limit={limit}
                    price={priceOf(current?.price, {
                        seconds: Number(chosenDuration),
                    })}
                />
            </div>
        </Panel>
    );
}
