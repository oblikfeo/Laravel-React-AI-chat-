import { useEffect, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import PromptComposer from '@/Components/Home/PromptComposer';
import QuickActions from '@/Components/Home/QuickActions';

export default function Home() {
    const { auth, defaultModel } = usePage().props;
    const [prompt, setPrompt] = useState('');
    const [model, setModel] = useState(defaultModel);
    const [files, setFiles] = useState([]);
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
        if (!prompt.trim() && !files.length) {
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

        // forceFormData: файлы уходят обычной формой, иначе Inertia
        // отправит JSON и вложения потеряются.
        router.post(
            '/chats',
            { message: prompt, model, files },
            {
                forceFormData: true,
                // Переход мгновенный, полоса только мигает.
                showProgress: false,
                onFinish: () => setBusy(false),
            },
        );
    };

    return (
        <>
            <Head title="Uncensia" />

            <div className="flex flex-1 flex-col items-center justify-center overflow-y-auto scrollbar-thin px-4 pb-16 pt-4 sm:px-6">
                <div className="w-full max-w-[720px]">
                    <h1 className="text-outline text-center text-[56px] font-light leading-none tracking-tight text-white sm:text-7xl lg:text-8xl">
                        uncensia
                    </h1>

                    <p className="text-outline mt-5 text-center text-base text-white/85 sm:text-lg">
                        The universe has no restrictions. Neither should AI.
                        Uncensia.
                    </p>

                    <div className="mt-8">
                        <PromptComposer
                            value={prompt}
                            onChange={setPrompt}
                            onSubmit={submit}
                            model={model}
                            onModelChange={setModel}
                            files={files}
                            onFilesChange={setFiles}
                            busy={busy}
                        />
                    </div>

                    <div className="mt-5">
                        <QuickActions onSelect={setPrompt} />
                    </div>
                </div>
            </div>
        </>
    );
}

// Постоянный макет: не пересоздаётся при переходах,
// поэтому боковое меню сохраняет состояние.
Home.layout = (page) => <MainLayout>{page}</MainLayout>;
