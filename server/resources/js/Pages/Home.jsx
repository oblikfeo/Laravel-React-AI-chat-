import { useEffect, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import PromptComposer from '@/Components/Home/PromptComposer';
import QuickActions from '@/Components/Home/QuickActions';
import GuestNotice from '@/Components/Layout/GuestNotice';

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
                    {/* Свечение планеты съедало текст: слева буквы
                        пропадали целиком. Под заголовком лежит мягкое
                        затемнение — края растворяются, поэтому это
                        читается как тень, а не как плашка. */}
                    <div className="relative">
                        <div
                            aria-hidden
                            className="pointer-events-none absolute -inset-x-10 -inset-y-8 bg-[radial-gradient(ellipse_60%_55%_at_50%_50%,rgba(3,7,18,0.72),rgba(3,7,18,0.45)_55%,transparent_78%)]"
                        />

                        <h1 className="relative text-center text-[56px] font-light leading-none tracking-tight text-white [text-shadow:0_2px_30px_rgba(0,0,0,0.85)] sm:text-7xl lg:text-8xl">
                            uncensia
                        </h1>

                        <p className="relative mt-5 text-center text-base text-white/90 [text-shadow:0_1px_16px_rgba(0,0,0,0.9)] sm:text-lg">
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
