import { Moon, Sun } from 'lucide-react';
import { useTheme } from '@/Contexts/ThemeContext';

/**
 * Переключатель темы: капсула с двумя иконками,
 * активная половина подсвечена (figma/1440w dark.jpg, правый верхний угол).
 */
export default function ThemeToggle() {
    const { theme, setTheme } = useTheme();

    const options = [
        { value: 'dark', icon: Moon, label: 'Dark theme' },
        { value: 'light', icon: Sun, label: 'Light theme' },
    ];

    return (
        <div className="glass-chip flex items-center gap-1 rounded-full border p-1 backdrop-blur-xl">
            {options.map(({ value, icon: Icon, label }) => {
                const active = theme === value;

                return (
                    <button
                        key={value}
                        type="button"
                        onClick={() => setTheme(value)}
                        aria-label={label}
                        aria-pressed={active}
                        className={`flex h-8 w-8 items-center justify-center rounded-full transition ${
                            active
                                ? 'glass-chip-item-active'
                                : 'glass-chip-item'
                        }`}
                    >
                        <Icon className="h-4 w-4" strokeWidth={1.75} />
                    </button>
                );
            })}
        </div>
    );
}
