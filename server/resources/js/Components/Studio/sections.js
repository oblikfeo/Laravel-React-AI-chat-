/**
 * К какому разделу Студии относится работа.
 *
 * Правило одно на всю страницу: по нему работа попадает и в очередь
 * своего раздела, и в его галерею.
 */
export function sectionOf(item) {
    if (item.kind === 'audio') {
        return 'audio';
    }

    if (item.kind === 'video') {
        return 'video';
    }

    return !item.operation || item.operation === 'generate' ? 'image' : 'edit';
}
