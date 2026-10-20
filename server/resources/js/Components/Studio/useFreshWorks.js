import { useEffect, useRef, useState } from 'react';
import { sectionOf } from '@/Components/Studio/sections';

/**
 * Готовое, которого человек ещё не видел.
 *
 * Работа сюда попадает в тот момент, когда стала готовой у нас на
 * глазах: была в очереди — и вот готова, либо её не было вовсе. По
 * этому списку подсвечиваются переключатели и сами работы в галерее.
 *
 * unseen — ещё не увиденное, highlighted — подсвеченное в открытой
 * галерее: увиденное перестаёт считаться новым, но подсветка держится,
 * пока человек не уйдёт.
 */
export default function useFreshWorks(generations, view, tab) {
    const [unseen, setUnseen] = useState([]);
    const [highlighted, setHighlighted] = useState([]);
    const known = useRef(null);

    useEffect(() => {
        const before = known.current;

        known.current = {
            statuses: new Map(generations.map((item) => [item.id, item.status])),
            newest: Math.max(0, ...generations.map((item) => item.id)),
        };

        if (!before) {
            return;
        }

        const fresh = generations
            .filter((item) => {
                if (item.status !== 'ready') {
                    return false;
                }

                const was = before.statuses.get(item.id);

                // Работа старше известных — не новая: она просто
                // вошла в список на место удалённой.
                return was ? was !== 'ready' : item.id > before.newest;
            })
            .map((item) => item.id);

        if (fresh.length) {
            setUnseen((previous) => [...new Set([...previous, ...fresh])]);
        }
    }, [generations]);

    const unseenIn = (section) =>
        generations.filter(
            (item) => unseen.includes(item.id) && sectionOf(item) === section,
        );

    useEffect(() => {
        if (view !== 'gallery') {
            setHighlighted([]);

            return;
        }

        const ids = unseenIn(tab).map((item) => item.id);

        setHighlighted(ids);

        if (ids.length) {
            setUnseen((previous) => previous.filter((id) => !ids.includes(id)));
        }
        // Пересчитываем только при смене режима и раздела: работа,
        // пришедшая в открытую галерею, подсветится эффектом ниже.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [view, tab]);

    // Работа, пришедшая прямо в открытую галерею своего раздела.
    useEffect(() => {
        if (view !== 'gallery') {
            return;
        }

        const ids = unseenIn(tab).map((item) => item.id);

        if (ids.length) {
            setHighlighted((previous) => [...new Set([...previous, ...ids])]);
            setUnseen((previous) => previous.filter((id) => !ids.includes(id)));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [unseen]);

    return { unseen, highlighted, unseenIn };
}
