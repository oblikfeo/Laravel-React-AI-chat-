import { createContext, useCallback, useContext, useState } from 'react';
import PlansDialog from '@/Components/Billing/PlansDialog';

const PlansContext = createContext({ openPlans: () => {} });

/**
 * Даёт любому компоненту возможность показать тарифы.
 *
 * Обёртка живёт в MainLayout, поэтому вызвать баннер можно из меню,
 * из сайдбара или из будущих страниц — не прокидывая состояние руками:
 *
 *     const { openPlans } = usePlans();
 *     <button onClick={openPlans}>Upgrade</button>
 */
export function PlansProvider({ children }) {
    const [open, setOpen] = useState(false);

    const openPlans = useCallback(() => setOpen(true), []);
    const closePlans = useCallback(() => setOpen(false), []);

    return (
        <PlansContext.Provider value={{ openPlans, closePlans }}>
            {children}
            <PlansDialog open={open} onClose={closePlans} />
        </PlansContext.Provider>
    );
}

export function usePlans() {
    return useContext(PlansContext);
}
