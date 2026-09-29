import { useEffect, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import PromptComposer from '@/Components/Home/PromptComposer';
import QuickActions from '@/Components/Home/QuickActions';
import GuestNotice from '@/Components/Layout/GuestNotice';
import GlareShield from '@/Components/Layout/GlareShield';

export default function Home() {
    const { auth, defaultModel, guest } = usePage().props;
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
                    {/* У каждой строки своё затемнение по её размеру:
                        одно общее пятно читалось как клякса. */}
                    <div className="relative">
                        <GlareShield className="h-[150px] w-[740px] max-w-[130vw] sm:h-[180px] sm:w-[900px]" />

                        <h1 className="text-center text-[56px] font-light leading-none tracking-tight text-white sm:text-7xl lg:text-8xl">
                            uncensia
                        </h1>
                    </div>

                    <div className="relative mt-9">
                        <GlareShield className="h-[110px] w-[980px] max-w-[145vw] sm:h-[120px] sm:w-[1180px]" />

                        <p className="text-center text-base text-white/85 sm:text-lg">
                            The universe has no restrictions. Neither should
                            AI. Uncensia.
                        </p>
                    </div>

                    <div className="mt-8">
                        <GuestNotice />

                        <PromptComposer
                            value={prompt}
                            onChange={setPrompt}
                            onSubmit={submit}
                            model={model}
                            onModelChange={setModel}
                            files={files}
                            onFilesChange={setFiles}
                            busy={busy || guest?.remaining === 0}
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
