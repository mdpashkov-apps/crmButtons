<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'] ?? null;

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

require_once(__DIR__ . '/limits.php');
require_once(__DIR__ . '/../billing/BillingClient.php');

// форс-рефетч мимо кэша (после оплаты — instant-обновление, поллинг refreshUntilActive)
if (!empty($requestData['force'])) {
    BillingClient::invalidate((string)$memberId);
}

$ent    = BillingClient::getEntitlements((string)$memberId);
$isPro  = ($ent['plan_type'] ?? 'free') !== 'free';
$status = checkButtonLimit($memberId);

echo json_encode([
    'plan'         => $isPro ? 'pro' : 'free',      // совместимость с фронтом (plan === 'pro')
    'plan_name'    => $ent['plan'] ?? 'free',        // реальное имя тарифа из qabinet
    'plan_type'    => $ent['plan_type'] ?? 'free',   // free | paid | trial
    'is_pro'       => $isPro,
    'source'       => $ent['source'] ?? 'billing',   // billing | failover
    'features'     => $ent['features'] ?? [],
    'can'          => [
        'bp_chains'        => BillingClient::canUseFeature((string)$memberId, 'bp_chains'),
        'link_with_params' => BillingClient::canUseFeature((string)$memberId, 'link_with_params'),
    ],
    'valid_until'         => $ent['expires_at'] ?? ($ent['trial_end_at'] ?? null),
    'trial_end_at'        => $ent['trial_end_at'] ?? null,
    'subscription_status' => $ent['subscription_status'] ?? null,
    'limits'       => [
        'buttons' => [
            'used'  => $status['used'],
            'limit' => $status['limit'],
        ],
    ],
    'checkout_url' => defined('BILLING_WIDGET_URL') ? BILLING_WIDGET_URL : null,
], JSON_UNESCAPED_UNICODE);
