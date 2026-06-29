<?php
/**
 * Клиент биллинга qabinet (pull): JWT-резолв, entitlements (тариф/фичи/лимиты),
 * каталог планов, триал/чекаут, инвалидация кэша.
 *
 * Голый PHP + curl + файловый кэш (каталог cache/, должен быть записываем веб-сервером).
 * Требует settings.php (константы BILLING_*) и overCRest/APP_DATABASE (домен портала
 * для resolve берётся из таблицы токенов).
 *
 * Контракт сверен с «Толмач-биллинг-и-уведомления.md»:
 *   POST /identities/resolve  {type:b24_portal, value:member_id, app_code, portal_domain} → {jwt}
 *   GET  /entitlements        Bearer <jwt> → {plan, plan_type, features, limits}
 *   GET  /products/{app}/plans
 *   POST /checkout            Bearer <jwt> + Idempotency-Key {plan_code, contact}
 */

class BillingClient
{
    private static function cacheDir(): string
    {
        $d = __DIR__ . '/cache';
        if (!is_dir($d)) { @mkdir($d, 0775, true); }
        return $d;
    }

    private static function cacheGet(string $key)
    {
        $f = self::cacheDir() . '/' . $key . '.json';
        if (!is_file($f)) { return null; }
        $j = json_decode((string) @file_get_contents($f), true);
        if (!is_array($j) || (int) ($j['exp'] ?? 0) < time()) { return null; }
        return $j['data'] ?? null;
    }

    private static function cacheSet(string $key, $data, int $ttl): void
    {
        $f = self::cacheDir() . '/' . $key . '.json';
        @file_put_contents($f, json_encode(['exp' => time() + $ttl, 'data' => $data], JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private static function cacheDel(string $key): void
    {
        @unlink(self::cacheDir() . '/' . $key . '.json');
    }

    /** Домен портала из таблицы токенов (нужен для resolve). */
    private static function portalDomain(string $memberId): ?string
    {
        if (!defined('APP_DATABASE')) { return null; }
        $c = APP_DATABASE;
        $m = @new mysqli($c['host'], $c['login'], $c['password'], $c['database']);
        if ($m->connect_error) { return null; }
        $stmt = $m->prepare("SELECT domain FROM `{$c['table']}` WHERE member_id = ? LIMIT 1");
        $stmt->bind_param('s', $memberId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $m->close();
        return $row['domain'] ?? null;
    }

    /** HTTP-запрос. Возвращает [httpCode, decodedBody|null]. */
    private static function http(string $method, string $url, ?array $json = null, array $headers = []): array
    {
        $h = ['Accept: application/json'];
        foreach ($headers as $k => $v) { $h[] = "$k: $v"; }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if ($json !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE);
            $h[] = 'Content-Type: application/json';
        }
        $opts[CURLOPT_HTTPHEADER] = $h;
        $ch = curl_init($url);
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, json_decode((string) $body, true)];
    }

    /** JWT по member_id. Кэш 55 мин. Возвращает строку JWT или null. */
    public static function resolveJwt(string $memberId): ?string
    {
        $ck = 'jwt_' . sha1($memberId);
        $cached = self::cacheGet($ck);
        if (is_string($cached) && $cached !== '') { return $cached; }

        [$code, $resp] = self::http('POST', BILLING_BASE_URL . '/identities/resolve', [
            'type'          => 'b24_portal',
            'value'         => $memberId,
            'app_code'      => BILLING_APP_CODE,
            'portal_domain' => self::portalDomain($memberId),
        ]);
        if ($code >= 200 && $code < 300 && !empty($resp['jwt'])) {
            self::cacheSet($ck, $resp['jwt'], 55 * 60);
            return $resp['jwt'];
        }
        return null;
    }

    /** Нормализованный снимок тарифа. Кэш 5 мин. Недоступность биллинга → фолбэк free. */
    public static function getEntitlements(string $memberId): array
    {
        $ck = 'ent_' . sha1($memberId);
        $cached = self::cacheGet($ck);
        if (is_array($cached)) { return $cached; }

        if (self::cacheGet('failover') !== null) {
            return self::freeSnapshot('failover');
        }

        $jwt = self::resolveJwt($memberId);
        if (!$jwt) {
            self::cacheSet('failover', 1, 60);
            return self::freeSnapshot('failover');
        }

        [$code, $resp] = self::http('GET', BILLING_BASE_URL . '/entitlements', null, ['Authorization' => 'Bearer ' . $jwt]);
        if ($code === 401) {
            // JWT протух раньше TTL — сбрасываем и пробуем ещё раз
            self::cacheDel('jwt_' . sha1($memberId));
            $jwt = self::resolveJwt($memberId);
            if ($jwt) {
                [$code, $resp] = self::http('GET', BILLING_BASE_URL . '/entitlements', null, ['Authorization' => 'Bearer ' . $jwt]);
            }
        }

        if ($code >= 200 && $code < 300 && is_array($resp)) {
            $snap = self::normalize($resp);
            self::cacheSet($ck, $snap, 5 * 60);
            return $snap;
        }

        // биллинг недоступен/ошибка → failover на free на 60 сек, UI не блокируем
        self::cacheSet('failover', 1, 60);
        return self::freeSnapshot('failover');
    }

    /**
     * Доступна ли PRO-фича. Если qabinet явно вернул фичу в каталоге (features[code]) — берём её;
     * иначе дефолт по тарифу (free → нельзя, trial/paid → можно). При failover не блокируем.
     */
    public static function canUseFeature(string $memberId, string $code): bool
    {
        $e = self::getEntitlements($memberId);
        if (($e['source'] ?? '') === 'failover') {
            return true;
        }
        $features = $e['features'] ?? [];
        if (is_array($features) && array_key_exists($code, $features)) {
            return (bool) $features[$code];
        }
        return ($e['plan_type'] ?? 'free') !== 'free';
    }

    private static function normalize(array $e): array
    {
        $features = [];
        foreach (($e['features'] ?? []) as $k => $v) {
            $features[$k] = (bool) $v;
        }
        $limits = [];
        foreach (($e['limits'] ?? []) as $k => $v) {
            $val = is_array($v) ? ($v['value'] ?? null) : $v;
            $limits[$k] = ($val === 'unlimited' || $val === null || $val === '') ? null : (int) $val;
        }
        return [
            'plan'                 => (string) ($e['plan'] ?? 'free'),
            'plan_type'            => (string) ($e['plan_type'] ?? 'free'),
            'subscription_status'  => (string) ($e['subscription_status'] ?? 'none'),
            'features'             => $features,
            'limits'               => $limits,
            'expires_at'           => $e['expires_at'] ?? null,       // окончание периода (платный/триал)
            'trial_end_at'         => $e['trial_end_at'] ?? null,     // окончание триала
            'cancel_at_period_end' => !empty($e['cancel_at_period_end']),
            'source'               => 'billing',
        ];
    }

    private static function freeSnapshot(string $source): array
    {
        return [
            'plan'                => 'free',
            'plan_type'           => 'free',
            'subscription_status' => 'none',
            'features'            => [],
            'limits'              => [], // пусто → лимит берётся из дефолта приложения
            'source'              => $source,
        ];
    }

    /** Каталог планов продукта. Без JWT, кэш 60 мин. */
    public static function getPlans(): array
    {
        $cached = self::cacheGet('plans');
        if (is_array($cached)) { return $cached; }
        [$code, $resp] = self::http('GET', BILLING_BASE_URL . '/products/' . BILLING_APP_CODE . '/plans');
        $plans = ($code >= 200 && $code < 300 && is_array($resp)) ? $resp : [];
        self::cacheSet('plans', $plans, 60 * 60);
        return $plans;
    }

    /** Запрос триала/чекаута с контактами. */
    public static function startTrial(string $memberId, array $contact): array
    {
        $jwt = self::resolveJwt($memberId);
        if (!$jwt) { return ['error' => 'no_jwt']; }
        [$code, $resp] = self::http('POST', BILLING_BASE_URL . '/checkout', [
            'plan_code' => defined('BILLING_TRIAL_PLAN_CODE') ? BILLING_TRIAL_PLAN_CODE : 'trial',
            'contact'   => $contact,
        ], [
            'Authorization'   => 'Bearer ' . $jwt,
            'Idempotency-Key' => self::uuid(),
        ]);
        self::invalidate($memberId); // после чекаута перечитываем тариф
        return ['code' => $code, 'response' => $resp];
    }

    /** Сброс кэша тарифа (после оплаты / для instant-обновления). */
    public static function invalidate(string $memberId): void
    {
        self::cacheDel('jwt_' . sha1($memberId));
        self::cacheDel('ent_' . sha1($memberId));
    }

    private static function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
