import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Фон приложения — фотография космоса с планетой снизу (figma/fon.png).
 *
 * Гасим только блик над планетой, а не картинку целиком: звёзды и край
 * планеты должны остаться контрастными. Ровная вуаль поверх всего
 * убивала глубину снимка.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-black">
            <div
                className="absolute inset-0 bg-cover bg-bottom bg-no-repeat"
                style={{ backgroundImage: "url('/images/space-bg.png')" }}
            />

            {/* Затемнение по форме блика: овал в середине экрана, где
                свечение сильнее всего. К краям сходит на нет, поэтому
                звёзды и дуга планеты остаются яркими. */}
            <div
                className={`absolute inset-0 transition-opacity duration-700 ${
                    isDark ? 'opacity-100' : 'opacity-0'
                }`}
                style={{
                    background:
                        'radial-gradient(ellipse 75% 45% at 50% 52%, rgba(5,7,13,0.82) 0%, rgba(5,7,13,0.55) 45%, rgba(5,7,13,0.15) 72%, transparent 100%)',
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
