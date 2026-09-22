import { createContext, useContext, useEffect, useMemo, useState } from 'react';

const ThemeContext = createContext(null);

const STORAGE_KEY = 'uncensia-theme';

/**
 * Тёмная тема — основная по макету, поэтому она используется
 * по умолчанию независимо от настроек системы. Системную тему
 * намеренно не читаем: иначе первый заход выглядел бы не так,
 * как задумано в дизайне.
 */
function getPreferredTheme() {
    if (typeof window === 'undefined') {
        return 'dark';
    }

    const stored = window.localStorage.getItem(STORAGE_KEY);

    return stored === 'light' ? 'light' : 'dark';
}

export function ThemeProvider({ children }) {
    const [theme, setTheme] = useState(getPreferredTheme);

    useEffect(() => {
        const root = window.document.documentElement;

        root.classList.remove('light', 'dark');
        root.classList.add(theme);

        window.localStorage.setItem(STORAGE_KEY, theme);
    }, [theme]);

    const value = useMemo(
        () => ({
            theme,
            isDark: theme === 'dark',
            toggleTheme: () =>
                setTheme((current) => (current === 'dark' ? 'light' : 'dark')),
            setTheme,
        }),
        [theme],
    );

    return (
        <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
    );
}

export function useTheme() {
    const context = useContext(ThemeContext);

    if (!context) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }

    return context;
}
