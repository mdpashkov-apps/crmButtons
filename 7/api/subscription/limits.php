<?php
/**
 * Утилиты для проверки лимитов бесплатного тарифа.
 *
 * Когда появится биллинг, isPro() и getButtonLimit() начнут учитывать подписку:
 *  - isPro($memberId) → запрос к billing API (с локальным кэшем)
 *  - getButtonLimit($memberId) → null для PRO (без лимита), 3 для free
 */

if (!defined('FREE_BUTTON_LIMIT')) {
    define('FREE_BUTTON_LIMIT', 3);
}

require_once(__DIR__ . '/../billing/BillingClient.php');

/**
 * Проверка подписки: тариф из биллинга (plan_type != free). Кэш — внутри BillingClient (5 мин).
 */
function isPro($memberId) {
    $e = BillingClient::getEntitlements((string)$memberId);
    return ($e['plan_type'] ?? 'free') !== 'free';
}

/**
 * Лимит на количество кнопок. null = безлимит.
 * Источник — биллинг (limits.buttons); при недоступности (failover) не блокируем.
 */
function getButtonLimit($memberId) {
    $e = BillingClient::getEntitlements((string)$memberId);
    // биллинг недоступен → не блокируем создание
    if (($e['source'] ?? '') === 'failover') {
        return null;
    }
    // лимит из каталога qabinet, если задан (null = безлимит)
    if (array_key_exists('buttons', $e['limits'] ?? [])) {
        return $e['limits']['buttons'];
    }
    // фолбэк: PRO — безлимит, free — дефолт приложения
    return (($e['plan_type'] ?? 'free') !== 'free') ? null : FREE_BUTTON_LIMIT;
}

/**
 * Считает «пользовательские» кнопки в портале (исключая PORTAL_SETTINGS).
 */
function countUserButtons() {
    $first = overCRest::call('entity.item.get', ['ENTITY' => 'customButton']);
    $total = (int)($first['total'] ?? 0);
    $items = $first['result'] ?? [];

    $count = 0;
    foreach ($items as $item) {
        $isSettings = $item['PROPERTY_VALUES']['isPortalSettings'] ?? '';
        if ($isSettings !== 'true') {
            $count++;
        }
    }

    if ($total > 50) {
        for ($start = 50; $start < $total; $start += 50) {
            $page = overCRest::call('entity.item.get', [
                'ENTITY' => 'customButton',
                'START'  => $start,
            ]);
            foreach (($page['result'] ?? []) as $item) {
                $isSettings = $item['PROPERTY_VALUES']['isPortalSettings'] ?? '';
                if ($isSettings !== 'true') {
                    $count++;
                }
            }
        }
    }

    return $count;
}

/**
 * Проверка перед созданием новой кнопки.
 * Возвращает [used, limit, exceeded]. exceeded=true означает «нельзя создавать».
 */
function checkButtonLimit($memberId) {
    $limit = getButtonLimit($memberId);
    $used = countUserButtons();

    return [
        'used'     => $used,
        'limit'    => $limit,
        'exceeded' => ($limit !== null) && ($used >= $limit),
    ];
}
