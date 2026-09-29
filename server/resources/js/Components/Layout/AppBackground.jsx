import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Фон приложения — фотография космоса с планетой снизу (figma/fon.png).
 *
 * Верх экрана на макете почти чёрный: свечения над планетой нет, только
 * звёзды. Поэтому сверху лежит плотная чёрная заливка, которая сходит
 * на нет к дуге планеты. Сама планета и её край остаются контрастными:
 * ровная вуаль поверх всего убивала глубину снимка.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-black">
            <div
                className="absolute inset-0 bg-cover bg-bottom bg-no-repeat"
                style={{ backgroundImage: "url('/images/space-bg.png')" }}
            />

            <div
                className={`absolute inset-0 transition-opacity duration-700 ${
                    isDark ? 'opacity-100' : 'opacity-0'
                }`}
                style={{
                    background:
                        // Сверху чёрный, к середине слабеет, ниже дуги
                        // планеты не трогаем совсем.
                        'linear-gradient(to bottom,'
                        + ' #05070d 0%,'
                        + ' #05070d 28%,'
                        + ' rgba(5,7,13,0.92) 42%,'
                        + ' rgba(5,7,13,0.55) 56%,'
                        + ' rgba(5,7,13,0.12) 70%,'
                        + ' transparent 82%)',
                }}
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
