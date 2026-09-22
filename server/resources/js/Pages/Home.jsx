import { useEffect, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import PromptComposer from '@/Components/Home/PromptComposer';
import QuickActions from '@/Components/Home/QuickActions';

export default function Home() {
    const { auth } = usePage().props;
    const [prompt, setPrompt] = useState('');
    const [visibility, setVisibility] = useState('Public');
    const [busy, setBusy] = useState(false);

    // Возвращаем текст, набранный до входа.
    useEffect(() => {
        const draft = window.sessionStorage.getItem('uncensia-draft');

        if (draft && auth?.user) {
            setPrompt(draft);
            window.sessionStorage.removeItem('uncensia-draft');
        }
    }, [auth?.user]);

    const submit = () => {
        if (!prompt.trim()) {
            return;
        }

        // Гостя отправляем авторизоваться, сохранив набранный текст,
        // чтобы после входа его не пришлось печатать заново.
        if (!auth?.user) {
            window.sessionStorage.setItem('uncensia-draft', prompt);
            router.visit('/auth?mode=register');

            return;
        }

        setBusy(true);

        router.post(
            '/chats',
            { message: prompt, visibility },
            { onFinish: () => setBusy(false) },
        );
    };

    return (
        <MainLayout>
            <Head title="Uncensia" />

            <div className="flex flex-1 flex-col items-center justify-center px-4 pb-16 pt-4 sm:px-6">
                <div className="w-full max-w-[720px]">
                    <h1 className="text-center text-[56px] font-light leading-none tracking-tight text-white [text-shadow:0_2px_24px_rgba(0,0,0,0.5)] sm:text-7xl lg:text-8xl">
                        uncensia
                    </h1>

                    <p className="mt-5 text-center text-base text-white/75 [text-shadow:0_1px_12px_rgba(0,0,0,0.6)] sm:text-lg">
                        The universe has no restrictions. Neither should AI.
                        Uncensia.
                    </p>

                    <div className="mt-8">
                        <PromptComposer
                            value={prompt}
                            onChange={setPrompt}
                            onSubmit={submit}
                            visibility={visibility}
                            onToggleVisibility={() =>
                                setVisibility((value) =>
                                    value === 'Public' ? 'Private' : 'Public',
                                )
                            }
                            busy={busy}
                        />
                    </div>

                    <div className="mt-5">
                        <QuickActions onSelect={setPrompt} />
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
