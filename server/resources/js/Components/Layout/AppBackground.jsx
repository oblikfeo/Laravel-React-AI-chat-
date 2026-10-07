import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Фон приложения.
 *
 * У каждой темы своя картинка дизайнера: космос в тёмной (back1.jpg),
 * луг в светлой (lite.png). Обе кадрированы по низу — там стоит
 * главный объект, планета и холмы, и при высоком окне он не должен
 * уезжать.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-surface">
            <div
                className={`absolute inset-0 bg-cover bg-bottom bg-no-repeat transition-opacity duration-500 ${
                    isDark ? 'opacity-100' : 'opacity-0'
                }`}
                style={{ backgroundImage: "url('/images/bg-space.jpg')" }}
            />

            <div
                className={`absolute inset-0 bg-cover bg-bottom bg-no-repeat transition-opacity duration-500 ${
                    isDark ? 'opacity-0' : 'opacity-100'
                }`}
                style={{ backgroundImage: "url('/images/bg-light.png')" }}
            />
        </div>
    );
}
