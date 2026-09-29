import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Фон приложения — готовая картинка дизайнера (figma/back1.jpg).
 *
 * Затемнение уже внутри файла, отдельный слой эллипса не нужен.
 * bg-center: планета в макете стоит симметрично по центру кадра.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-black">
            <div
                className="absolute inset-0 bg-cover bg-center bg-no-repeat"
                style={{ backgroundImage: "url('/images/bg-space.jpg')" }}
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
