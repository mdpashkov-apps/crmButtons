<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'] ?? null;

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

require_once(__DIR__ . '/limits.php');

$status = checkButtonLimit($memberId);

echo json_encode([
    'plan'        => isPro($memberId) ? 'pro' : 'free',
    'valid_until' => null,
    'limits'      => [
        'buttons' => [
            'used'  => $status['used'],
            'limit' => $status['limit'],
        ],
    ],
], JSON_UNESCAPED_UNICODE);
