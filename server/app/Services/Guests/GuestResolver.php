<?php

namespace App\Services\Guests;

use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Узнаёт посетителя без учётной записи.
 *
 * Порядок опознания — от надёжного к приблизительному:
 *
 * 1. Кука. Живёт год, переживает перезагрузку, привязана к браузеру.
 * 2. Отпечаток браузера. Ловит того, кто очистил куки: набор из
 *    разрешения экрана, часового пояса, языка и набора шрифтов
 *    повторяется у одного человека и редко совпадает у разных.
 *
 * IP в опознание не входит намеренно. За одним адресом сидят все
 * жильцы дома, все сотрудники офиса, все посетители кофейни, а у
 * мобильных операторов — тысячи абонентов. Считать их одним человеком
 * значит выдать десять сообщений на весь дом. IP применяется отдельно,
 * как потолок запросов с адреса, см. GuestLimiter.
 */
class GuestResolver
{
    public function resolve(Request $request): Guest
    {
        $guest = $this->byCookie($request) ?? $this->byFingerprint($request);

        if (! $guest) {
            $guest = Guest::create([
                'token' => (string) Str::uuid(),
                'fingerprint' => $this->fingerprintOf($request),
            ]);
        }

        $guest->forceFill([
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'last_seen_at' => now(),
            // Отпечаток мог прийти позже куки: запоминаем, когда есть.
            'fingerprint' => $this->fingerprintOf($request) ?? $guest->fingerprint,
        ])->save();

        return $guest;
    }

    private function byCookie(Request $request): ?Guest
    {
        $token = $request->cookie(config('guests.cookie_name'));

        if (! is_string($token) || $token === '') {
            return null;
        }

        return Guest::where('token', $token)->first();
    }

    /**
     * Поиск по отпечатку браузера.
     *
     * Берём только свежие записи: отпечаток не уникален, и за год
     * совпадения у разных людей неизбежны. Гостя, который уже
     * зарегистрировался, не возвращаем — у него теперь аккаунт.
     */
    private function byFingerprint(Request $request): ?Guest
    {
        $fingerprint = $this->fingerprintOf($request);

        if (! $fingerprint) {
            return null;
        }

        return Guest::query()
            ->where('fingerprint', $fingerprint)
            ->whereNull('user_id')
            ->where('last_seen_at', '>=', now()->subDays(30))
            ->latest('last_seen_at')
            ->first();
    }

    /** Отпечаток приходит от браузера заголовком. */
    private function fingerprintOf(Request $request): ?string
    {
        $value = $request->header('X-Guest-Fingerprint');

        if (! is_string($value) || strlen($value) < 8) {
            return null;
        }

        return substr(hash('sha256', $value), 0, 64);
    }
}
