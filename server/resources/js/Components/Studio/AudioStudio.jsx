import { Music, Mic, Repeat, AudioWaveform } from 'lucide-react';
import ModeTiles from '@/Components/Studio/ModeTiles';
import MusicForm from '@/Components/Studio/Audio/MusicForm';
import SpeechForm from '@/Components/Studio/Audio/SpeechForm';
import ChangerForm from '@/Components/Studio/Audio/ChangerForm';
import EffectForm from '@/Components/Studio/Audio/EffectForm';

/**
 * Режимы звука.
 *
 * Стоят крупными плитками, как инструменты правки: это главное на
 * вкладке, и по одному названию не всегда понятно, что внутри.
 */
const MODES = [
    {
        key: 'music',
        label: 'Music',
        hint: 'Songs and instrumentals',
        icon: Music,
    },
    {
        key: 'speech',
        label: 'Voice',
        hint: 'Read any text aloud',
        icon: Mic,
    },
    {
        key: 'changer',
        label: 'Voice Changer',
        hint: 'Your recording, new voice',
        icon: Repeat,
    },
    {
        key: 'effect',
        label: 'Sound Effect',
        hint: 'Short sounds for video',
        icon: AudioWaveform,
    },
];

/**
 * Работа со звуком: музыка, озвучка, смена голоса и эффекты.
 *
 * Режимы переключаются над одной формой: у них общее поле ввода и
 * разные настройки под ним.
 */
export default function AudioStudio({
    speechModels,
    musicModels,
    effectModels,
    voices,
    defaultSpeechModel,
    defaultMusicModel,
    defaultEffectModel,
    defaultVoice,
    maxCharacters = 4000,
    maxLyrics = 3000,
    available,
    busy,
    limit,
    mode,
    onModeChange,
    incoming = null,
    onSubmit,
}) {
    return (
        <div>
            <ModeTiles items={MODES} value={mode} onChange={onModeChange} />

            {mode === 'music' && (
                <MusicForm
                    incoming={incoming}
                    models={musicModels}
                    defaultModel={defaultMusicModel}
                    maxLyrics={maxLyrics}
                    available={available}
                    busy={busy}
                    limit={limit}
                    onSubmit={(values) => onSubmit('music', values)}
                />
            )}

            {mode === 'speech' && (
                <SpeechForm
                    incoming={incoming}
                    models={speechModels}
                    voices={voices}
                    defaultModel={defaultSpeechModel}
                    defaultVoice={defaultVoice}
                    maxCharacters={maxCharacters}
                    available={available}
                    busy={busy}
                    limit={limit}
                    onSubmit={(values) => onSubmit('speech', values)}
                />
            )}

            {mode === 'changer' && (
                <ChangerForm
                    voices={voices}
                    defaultVoice={defaultVoice}
                    available={available}
                    busy={busy}
                    limit={limit}
                    onSubmit={(values) => onSubmit('voice-change', values)}
                />
            )}

            {mode === 'effect' && (
                <EffectForm
                    incoming={incoming}
                    models={effectModels}
                    defaultModel={defaultEffectModel}
                    available={available}
                    busy={busy}
                    limit={limit}
                    onSubmit={(values) => onSubmit('effect', values)}
                />
            )}
        </div>
    );
}
