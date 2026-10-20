import { useRef } from 'react';
import { Upload, Images, Trash2, ImagePlus } from 'lucide-react';
import CharacterAvatar from '@/Components/Characters/CharacterAvatar';
import TagInput from '@/Components/Characters/TagInput';
import { INPUT, Labeled, WriteButton } from '@/Components/Characters/Sections/Fields';

/** Раздел «General»: аватар, имя, описание и теги. */
export default function General({
    data,
    setData,
    errors,
    limits,
    suggestedTags,
    avatarPreview,
    setAvatarPreview,
    hasStudioWorks,
    onPickFromStudio,
}) {
    const fileRef = useRef(null);

    const upload = (file) => {
        if (!file) {
            return;
        }

        setData((values) => ({
            ...values,
            avatar: file,
            avatar_generation_id: null,
            remove_avatar: false,
        }));
        setAvatarPreview(URL.createObjectURL(file));
    };

    const clear = () => {
        setData((values) => ({
            ...values,
            avatar: null,
            avatar_generation_id: null,
            remove_avatar: true,
        }));
        setAvatarPreview(null);
    };

    return (
        <div className="space-y-5">
            <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                <div className="relative shrink-0">
                    {avatarPreview ? (
                        <img
                            src={avatarPreview}
                            alt=""
                            className="h-[132px] w-[132px] rounded-3xl border border-white/[0.12] object-cover"
                        />
                    ) : data.name.trim() ? (
                        <CharacterAvatar
                            name={data.name}
                            className="h-[132px] w-[132px] rounded-3xl text-5xl"
                        />
                    ) : (
                        <span className="flex h-[132px] w-[132px] items-center justify-center rounded-3xl border border-dashed border-white/20 bg-white/[0.04] text-white/30">
                            <ImagePlus className="h-9 w-9" strokeWidth={1.25} />
                        </span>
                    )}
                </div>

                <div className="w-full min-w-0 flex-1 space-y-2">
                    <p className="text-[13px] font-medium text-white/70">Avatar</p>

                    <input
                        ref={fileRef}
                        type="file"
                        accept="image/*"
                        className="hidden"
                        onChange={(event) => {
                            upload(event.target.files[0]);
                            event.target.value = '';
                        }}
                    />

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => fileRef.current?.click()}
                            className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-3.5 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                        >
                            <Upload className="h-4 w-4" strokeWidth={1.75} />
                            Upload
                        </button>

                        {hasStudioWorks && (
                            <button
                                type="button"
                                onClick={onPickFromStudio}
                                className="flex h-9 items-center gap-2 rounded-full border border-white/[0.14] px-3.5 text-[13px] text-white/75 transition hover:bg-white/10 hover:text-white"
                            >
                                <Images className="h-4 w-4" strokeWidth={1.75} />
                                From Studio
                            </button>
                        )}

                        {avatarPreview && (
                            <button
                                type="button"
                                onClick={clear}
                                className="flex h-9 items-center gap-2 rounded-full px-3 text-[13px] text-white/50 transition hover:bg-white/10 hover:text-rose-300"
                            >
                                <Trash2 className="h-4 w-4" strokeWidth={1.75} />
                                Remove
                            </button>
                        )}
                    </div>

                    <p className="text-[12px] leading-snug text-white/40">
                        Any image up to 5 MB. Without one the character gets a
                        coloured initial.
                    </p>

                    {errors.avatar && (
                        <p className="text-[12px] text-rose-300">{errors.avatar}</p>
                    )}
                </div>
            </div>

            <Labeled
                label="Name"
                error={errors.name}
                count={data.name.length}
                max={limits.name}
            >
                <input
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    maxLength={limits.name}
                    placeholder="What is your character called?"
                    className={`${INPUT} h-11`}
                />
            </Labeled>

            <Labeled
                label="Description"
                hint="Shown on the character's card. The model does not treat it as instructions."
                error={errors.description}
                count={data.description.length}
                max={limits.description}
            >
                <textarea
                    value={data.description}
                    onChange={(event) => setData('description', event.target.value)}
                    maxLength={limits.description}
                    rows={3}
                    placeholder="One or two sentences about who this is."
                    className={`${INPUT} resize-y py-2.5 leading-relaxed`}
                />
            </Labeled>

            <WriteButton
                target="description"
                data={data}
                label="Auto-Generate description"
                onText={(text) => setData('description', text)}
            />

            <Labeled label="Tags" error={errors.tags}>
                <TagInput
                    value={data.tags}
                    onChange={(tags) => setData('tags', tags)}
                    suggestions={suggestedTags}
                    max={limits.tags}
                />
            </Labeled>
        </div>
    );
}
