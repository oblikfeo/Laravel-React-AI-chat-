import { createContext, useCallback, useContext, useState } from 'react';
import SettingsDialog from '@/Components/Settings/SettingsDialog';

const SettingsContext = createContext({ openSettings: () => {} });

/**
 * Даёт любому компоненту возможность открыть настройки.
 *
 * Устроено так же, как тарифы: состояние живёт здесь, а вызывающему
 * достаточно openSettings().
 */
export function SettingsProvider({ children }) {
    const [open, setOpen] = useState(false);

    const openSettings = useCallback(() => setOpen(true), []);
    const closeSettings = useCallback(() => setOpen(false), []);

    return (
        <SettingsContext.Provider value={{ openSettings, closeSettings }}>
            {children}
            <SettingsDialog open={open} onClose={closeSettings} />
        </SettingsContext.Provider>
    );
}

export function useSettings() {
    return useContext(SettingsContext);
}
