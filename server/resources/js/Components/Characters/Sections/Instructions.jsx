import { Plus } from 'lucide-react';
import { INPUT, Labeled, WriteButton } from '@/Components/Characters/Sections/Fields';

/** Раздел «Instructions»: приветствие, характер и свой системный запрос. */
export default function Instructions({ data, setData, errors, limits, customPrompt, setCustomPrompt }) {
    return (
        <div className="space-y-5">
            <Labeled
                label="Intro statement"
                hint="Optional. The character's first message in a new conversation."
                error={errors.intro}
                count={data.intro.length}
                max={limits.intro}
            >
                <textarea
                    value={data.intro}
                    onChange={(event) => setData('intro', event.target.value)}
                    maxLength={limits.intro}
                    rows={2}
                    placeholder="How does your character greet people?"
                    className={`${INPUT} resize-y py-2.5 leading-relaxed`}
                />
            </Labeled>

            <Labeled
                label="Instructions"
                hint="Only you can see this. It becomes the basis of the character's personality."
                error={errors.instructions}
                count={data.instructions.length}
                max={limits.instructions}
            >
                <textarea
                    value={data.instructions}
                    onChange={(event) => setData('instructions', event.target.value)}
                    maxLength={limits.instructions}
                    rows={9}
                    placeholder="Describe your character in as much detail as possible: who they are, how they speak, what they care about."
                    className={`${INPUT} resize-y py-2.5 leading-relaxed`}
                />
            </Labeled>

            <WriteButton
                target="instructions"
                data={data}
                label="Write instructions from name and description"
                onText={(text) => setData('instructions', text)}
            />

            {customPrompt ? (
                <Labeled
                    label="Custom system prompt"
                    hint="Replaces our wrapper entirely. Leave it off unless you know you need it."
                    error={errors.system_prompt}
                    count={data.system_prompt.length}
                    max={limits.system_prompt}
                    action={
                        <button
                            type="button"
                            onClick={() => setCustomPrompt(false)}
                            className="text-[12px] text-white/45 transition hover:text-white"
                        >
                            Remove
                        </button>
                    }
                >
                    <textarea
                        value={data.system_prompt}
                        onChange={(event) =>
                            setData('system_prompt', event.target.value)
                        }
                        maxLength={limits.system_prompt}
                        rows={6}
                        placeholder="The full system prompt sent to the model."
                        className={`${INPUT} resize-y py-2.5 font-mono text-[13px] leading-relaxed`}
                    />
                </Labeled>
            ) : (
                <button
                    type="button"
                    onClick={() => setCustomPrompt(true)}
                    className="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-dashed border-white/[0.16] text-[13px] text-white/60 transition hover:bg-white/10 hover:text-white"
                >
                    <Plus className="h-4 w-4" strokeWidth={1.75} />
                    Add custom system prompt
                </button>
            )}
        </div>
    );
}
