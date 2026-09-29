import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Фон приложения — фотография космоса с планетой снизу (figma/fon.png).
 *
 * Поверх фотографии лежит затемнение на весь экран: на исходнике
 * свечение планеты настолько яркое, что съедает белый текст. Раньше
 * с этим боролись обводкой букв и пятном под заголовком — и то и
 * другое выглядело грязно. На макете свечение приглушено по всей
 * картинке, текст читается сам собой.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-black">
            <div
                className="absolute inset-0 bg-cover bg-bottom bg-no-repeat"
                style={{ backgroundImage: "url('/images/space-bg.png')" }}
            />

            {/* Ровная вуаль гасит общую яркость. */}
            <div
                className={`absolute inset-0 bg-[#05070d] transition-opacity duration-700 ${
                    isDark ? 'opacity-[0.55]' : 'opacity-0'
                }`}
            />

            {/* Дополнительное затемнение сверху и снизу: сверху лежит
                заголовок, снизу — поле ввода, и оба должны читаться. */}
            <div
                className={`absolute inset-0 bg-gradient-to-b from-[#05070d]/85 via-transparent to-[#05070d]/70 transition-opacity duration-700 ${
                    isDark ? 'opacity-100' : 'opacity-0'
                }`}
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
