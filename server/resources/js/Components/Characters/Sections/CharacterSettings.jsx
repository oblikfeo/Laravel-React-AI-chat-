import { Cpu } from 'lucide-react';
import Toggle from '@/Components/Characters/Toggle';
import Dropdown from '@/Components/Studio/Dropdown';

/** Раздел «Settings»: публичность, модель и температура. */
export default function CharacterSettings({
    data,
    setData,
    errors,
    models,
    canPublish,
    advanced,
    setAdvanced,
}) {
    const temperature = data.temperature ?? 0.7;

    return (
        <div>
            <div className="divide-y divide-white/[0.07]">
                <Toggle
                    label="Public"
                    hint={
                        canPublish
                            ? 'Show this character in the catalogue and the feed. Your instructions stay hidden.'
                            : 'Characters on this model stay private and are not shown in the catalogue.'
                    }
                    checked={canPublish && data.is_public}
                    disabled={!canPublish}
                    onChange={(value) => setData('is_public', value)}
                />

                <div className="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div className="min-w-0">
                        <p className="text-[14px] font-medium text-white">Model</p>
                        <p className="mt-0.5 text-[12px] text-white/45">
                            Which model speaks for the character.
                        </p>
                    </div>

                    <Dropdown
                        value={data.model}
                        onChange={(value) => setData('model', value)}
                        options={models}
                        label="Model"
                        icon={Cpu}
                        align="right"
                    />
                </div>

                <Toggle
                    label="Advanced settings"
                    hint="Fine-tune how the character answers."
                    checked={advanced}
                    onChange={(value) => {
                        setAdvanced(value);

                        if (value && data.temperature === null) {
                            setData('temperature', 0.7);
                        }
                    }}
                />
            </div>

            {errors.model && (
                <p className="mt-1 text-[12px] text-rose-300">{errors.model}</p>
            )}

            {advanced && (
                <div className="mt-2 rounded-2xl border border-white/[0.08] bg-white/[0.04] p-4">
                    <div className="flex items-center justify-between">
                        <p className="text-[14px] font-medium text-white">
                            Temperature
                        </p>
                        <span className="rounded-lg bg-white/10 px-2 py-0.5 text-[13px] tabular-nums text-white">
                            {Number(temperature).toFixed(1)}
                        </span>
                    </div>

                    <input
                        type="range"
                        min="0"
                        max="2"
                        step="0.1"
                        value={temperature}
                        onChange={(event) =>
                            setData('temperature', Number(event.target.value))
                        }
                        aria-label="Temperature"
                        className="mt-3 w-full accent-sky-400"
                    />

                    <div className="mt-1 flex justify-between text-[12px] text-white/40">
                        <span>Focused</span>
                        <span>Creative</span>
                    </div>

                    {errors.temperature && (
                        <p className="mt-1.5 text-[12px] text-rose-300">
                            {errors.temperature}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
