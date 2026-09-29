import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Фон приложения: два слоя от дизайнера (figma/fon).
 *
 * Нижний — фотография, верхний — готовый эллипс затемнения с
 * размытыми краями. Раньше это затемнение рисовалось градиентами
 * вручную и каждый раз выглядело пятном: край читался, форма не
 * совпадала. Слой дизайнера решает это сам.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-black">
            <div
                className="absolute inset-0 bg-cover bg-center bg-no-repeat"
                style={{ backgroundImage: "url('/images/bg-photo.png')" }}
            />

            {/* Эллипс гасит свечение там, где стоят заголовок и поле
                ввода. Ширина с запасом: на узких экранах он должен
                накрывать текст целиком. */}
            <div
                className={`absolute left-1/2 top-[42%] h-[70vh] w-[125vw] max-w-none -translate-x-1/2 -translate-y-1/2 bg-contain bg-center bg-no-repeat transition-opacity duration-700 sm:w-[105vw] ${
                    isDark ? 'opacity-100' : 'opacity-0'
                }`}
                style={{ backgroundImage: "url('/images/bg-ellipse.png')" }}
            />

            {/* В светлой теме приглушаем фон, но не перекрываем его целиком:
                композиция макета должна остаться узнаваемой. */}
            <div
                className={`absolute inset-0 bg-gradient-to-b from-slate-200/85 via-slate-100/75 to-white/85 transition-opacity duration-700 ${
                    isDark ? 'opacity-0' : 'opacity-100'
                }`}
            />
        </div>
    );
}
