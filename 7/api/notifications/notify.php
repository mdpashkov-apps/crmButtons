<?php
/**
 * Приёмник биллинговых уведомлений (контракт NOTIFICATIONS-APP-CONTRACT).
 * Путь «Приложение Б» (голый PHP): HMAC + im.notify.system.add токеном портала + callback.
 *
 * Публичный URL (products.notify_url в биллинге):
 *   https://app.overplan.ru/applications/crmButtons/7/api/notifications/notify.php
 */

// overCRest + settings.php (там константы NOTIFICATIONS_*)
require_once(__DIR__ . '/../../overCRest.php');

header('Content-Type: application/json; charset=utf-8');

$SECRET = defined('NOTIFICATIONS_DISPATCH_SECRET') ? (string)NOTIFICATIONS_DISPATCH_SECRET : '';
$raw = file_get_contents('php://input');
$ts  = $_SERVER['HTTP_X_BILLING_TIMESTAMP'] ?? '';
$sig = $_SERVER['HTTP_X_BILLING_SIGNATURE'] ?? '';

// 1) Проверка подписи (та же формула, что в эталоне)
if ($SECRET === '') {
    http_response_code(503);
    echo json_encode(['error' => 'dispatch secret not configured'], JSON_UNESCAPED_UNICODE);
    exit;
}
$validSig = ctype_digit((string)$ts)
    && abs(time() - (int)$ts) <= 300
    && hash_equals(hash_hmac('sha256', $ts . '.' . $raw, $SECRET), (string)$sig);
if (!$validSig) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2) Разбор тела
$d = json_decode($raw, true);
if (!is_array($d)) {
    http_response_code(422);
    echo json_encode(['error' => 'bad json'], JSON_UNESCAPED_UNICODE);
    exit;
}
$ref      = (string)($d['callback_ref'] ?? '');
$memberId = (string)($d['member_id'] ?? '');
if ($ref === '' || $memberId === '') {
    http_response_code(422);
    echo json_encode(['error' => 'callback_ref and member_id required'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3) Резолв портала по токену. Нет токена → не ошибка, биллинг возьмёт следующий relay.
try {
    overCRest::setCurrentBitrix24($memberId);
} catch (\Throwable $e) {
    http_response_code(200);
    echo json_encode(['accepted' => false, 'reason' => 'not_installed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$settings = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true'],
]);
// Ошибка авторизации/скоупа на валидном члене трактуем как «не установлено» для биллинга.
if (!is_array($settings) || isset($settings['error'])) {
    http_response_code(200);
    echo json_encode(['accepted' => false, 'reason' => 'not_installed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4) Идемпотентность по callback_ref (атомарное создание файла-маркера)
$seenDir = __DIR__ . '/seen';
if (!is_dir($seenDir)) { @mkdir($seenDir, 0775, true); }
$seenFile = $seenDir . '/' . sha1($ref);
$fh = @fopen($seenFile, 'x'); // 'x' — эксклюзивное создание; если файл уже есть → дубликат
if ($fh === false) {
    http_response_code(202);
    echo json_encode(['accepted' => true, 'duplicate' => true], JSON_UNESCAPED_UNICODE);
    exit;
}
fwrite($fh, (string)time());
fclose($fh);
// опциональная уборка старых маркеров (>24ч)
foreach (glob($seenDir . '/*') ?: [] as $f) {
    if (is_file($f) && (time() - @filemtime($f)) > 86400) { @unlink($f); }
}

// 5) Сообщение
$message = trim((string)($d['message'] ?? ''));
if ($message === '') {
    http_response_code(202);
    echo json_encode(['accepted' => true], JSON_UNESCAPED_UNICODE);
    send_callback($ref, 'error', 'empty_message');
    exit;
}

// 6) Получатели: явный user_ids → установивший → владелец(1)
$portalProps = $settings['result'][0]['PROPERTY_VALUES'] ?? [];
$installedBy = (int)($portalProps['installedByUserId'] ?? 0);

// ленивый захват установившего, если ещё не сохранён (user.current на токене портала = установивший)
if ($installedBy <= 0) {
    $me = overCRest::call('user.current', []);
    $installedBy = (int)($me['result']['ID'] ?? 0);
    if ($installedBy > 0 && !empty($settings['result'][0]['ID'])) {
        overCRest::call('entity.item.update', [
            'ENTITY' => 'customButton',
            'ID'     => (int)$settings['result'][0]['ID'],
            'PROPERTY_VALUES' => ['installedByUserId' => $installedBy],
        ]);
    }
}

$explicit = array_values(array_filter(
    array_map('intval', (array)($d['user_ids'] ?? [])),
    fn($v) => $v > 0
));
$recipients = $explicit !== [] ? $explicit : [$installedBy > 0 ? $installedBy : 1];

// 7) Поля уведомления
$fields = ['MESSAGE' => $message];
if (($o = (string)($d['message_out'] ?? '')) !== '') { $fields['MESSAGE_OUT'] = $o; }
if (($t = (string)($d['tag'] ?? '')) !== '')        { $fields['TAG'] = $t; }
if (is_array($d['attach'] ?? null) && $d['attach'] !== []) { $fields['ATTACH'] = $d['attach']; } // готовая форма ATTACH от биллинга

// 8) Доставка (синхронно — 1-3 получателя укладываются в таймаут диспетча)
$ok = true; $err = null;
foreach ($recipients as $uid) {
    $r = overCRest::call('im.notify.system.add', $fields + ['USER_ID' => (int)$uid]);
    if (!is_array($r) || isset($r['error'])) {
        $ok = false;
        $err = is_array($r) ? (string)($r['error_description'] ?? $r['error'] ?? 'send_failed') : 'send_failed';
    }
}

http_response_code(202);
echo json_encode(['accepted' => true], JSON_UNESCAPED_UNICODE);

// 9) Callback в биллинг
send_callback($ref, $ok ? 'ok' : 'error', $ok ? null : mb_substr((string)$err, 0, 200));

// --- helpers ---
function send_callback(string $ref, string $status, ?string $error): void
{
    $url = defined('NOTIFICATIONS_CALLBACK_URL') ? (string)NOTIFICATIONS_CALLBACK_URL : '';
    $secret = defined('NOTIFICATIONS_DISPATCH_SECRET') ? (string)NOTIFICATIONS_DISPATCH_SECRET : '';
    if ($url === '' || $ref === '' || $secret === '') { return; }

    $body = json_encode(['v' => 1, 'callback_ref' => $ref, 'status' => $status, 'error' => $error], JSON_UNESCAPED_UNICODE);
    $ts = (string)time();
    $sig = hash_hmac('sha256', $ts . '.' . $body, $secret);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Billing-Timestamp: ' . $ts,
            'X-Billing-Signature: ' . $sig,
        ],
    ]);
    @curl_exec($ch);
    curl_close($ch);
}
