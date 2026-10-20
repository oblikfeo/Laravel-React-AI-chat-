import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Одна картинка фона и, по желанию, размытая подложка по бокам на
 * широких экранах. Как они ведут себя — в .scene-image, app.css.
 */
function Scene({ src, ratio, visible, fill = false }) {
    const style = { backgroundImage: `url('${src}')`, '--ratio': ratio };

    return (
        <div
            className={`absolute inset-0 transition-opacity duration-500 ${
                visible ? 'opacity-100' : 'opacity-0'
            }`}
        >
            {fill && <div className="scene-fill" style={style} />}
            <div className="scene-image" style={style} />
        </div>
    );
}

/**
 * Фон приложения.
 *
 * У каждой темы своя картинка дизайнера: космос в тёмной, луг в
 * светлой. Обе кадрированы по низу — там стоит главный объект,
 * планета и холмы, и при высоком окне он не должен уезжать.
 */
export default function AppBackground() {
    const { isDark } = useTheme();

    return (
        <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-surface">
            <Scene src="/images/bg-space.jpg" ratio={3140 / 1868} visible={isDark} />
            <Scene src="/images/bg-light.png" ratio={1440 / 900} visible={!isDark} fill />
        </div>
    );
}
