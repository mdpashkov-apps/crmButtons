<?php
/**
 * Активация триала пользователем («Попробовать бесплатно»).
 * Принимает { memberId, contact: { fio, email, phone, note } }.
 * Дёргает BillingClient::startTrial → POST /checkout {plan_code:'trial', contact}
 * (Bearer JWT + Idempotency-Key), затем сбрасывает кэш тарифа.
 *
 * Ответ фронту: { code, response } от qabinet (или { error } при отсутствии JWT).
 *   code 200/201 → триал оформлен (фронт поллит status.php до plan_type=trial);
 *   code 409     → у портала уже есть активная подписка/триал.
 */
$entityBody  = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId    = $requestData['memberId'] ?? null;
$contact     = $requestData['contact'] ?? [];

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

require_once(__DIR__ . '/BillingClient.php');

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    BillingClient::startTrial((string)$memberId, (array)$contact),
    JSON_UNESCAPED_UNICODE
);
