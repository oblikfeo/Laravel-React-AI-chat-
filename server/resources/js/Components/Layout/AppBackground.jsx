import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Фон приложения.
 *
 * В тёмной теме — картинка дизайнера (figma/back1.jpg), затемнение
 * уже внутри файла. bg-center: планета в макете стоит симметрично.
 *
 * В светлой теме космос убирается совсем: приглушённая картинка под
 * светлым интерфейсом читалась как грязь, а текст на ней терялся.
 * Вместо неё мягкий градиент того же холодного оттенка.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-surface">
            <div
                className={`absolute inset-0 bg-cover bg-center bg-no-repeat transition-opacity duration-500 ${
                    isDark ? 'opacity-100' : 'opacity-0'
                }`}
                style={{ backgroundImage: "url('/images/bg-space.jpg')" }}
            />

            <div
                className={`absolute inset-0 transition-opacity duration-500 ${
                    isDark ? 'opacity-0' : 'opacity-100'
                }`}
                style={{
                    background:
                        'radial-gradient(120% 90% at 50% 0%, rgb(248 250 252) 0%, rgb(241 245 249) 45%, rgb(226 232 240) 100%)',
                }}
            />
        </div>
    );
}
