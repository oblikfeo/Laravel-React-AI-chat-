import axios from 'axios';
import { guestFingerprint } from './fingerprint';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Отпечаток браузера: по нему узнаём посетителя без учётной записи,
// если он очистил куки. Влияет только на дневные лимиты.
const fingerprint = guestFingerprint();

if (fingerprint) {
    window.axios.defaults.headers.common['X-Guest-Fingerprint'] = fingerprint;
}
