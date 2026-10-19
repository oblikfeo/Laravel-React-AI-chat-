import { router } from '@inertiajs/react';

/*
 * Действия с работой Студии.
 *
 * Запросы параллельные: обычный переход Inertia отменяет предыдущий
 * запрос, а в это время может идти генерация другой работы.
 */
const options = { async: true, preserveScroll: true };

export function removeWork(item) {
    router.delete(`/studio/${item.id}`, options);
}

export function retryWork(item) {
    router.post(`/studio/${item.id}/retry`, {}, options);
}
