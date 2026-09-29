import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Фон приложения — два слоя из макета (figma/fon).
 *
 * У дизайнера фон 1440×900, эллипс затемнения 1349×825: он почти во
 * всю ширину экрана, а не по размеру заголовка. Поэтому оба слоя
 * растягиваются одинаково, как в макете, и сохраняют взаимное
 * положение при любом размере окна.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-black">
            {/* bg-bottom: планета стоит внизу кадра и не должна уезжать
                при высоком окне. */}
            <div
                className="absolute inset-0 bg-cover bg-bottom bg-no-repeat"
                style={{ backgroundImage: "url('/images/bg-photo.png')" }}
            />

            <div
                className={`absolute inset-0 bg-cover bg-bottom bg-no-repeat transition-opacity duration-700 ${
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
