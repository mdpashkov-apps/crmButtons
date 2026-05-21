<?php
// api/indexReport/getAllUsers.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$allUserFio = [];

// Получаем всех активных пользователей
$totalUser = overCRest::call('user.search', [
    'filter' => ['ACTIVE' => true],
])['total'];

$cmdBatch = [];
for ($i = 0; $i < $totalUser; $i = $i + 50) {
    $cmdBatch[] = [
        'method' => 'user.search',
        'params' => [
            'filter' => ['ACTIVE' => true],
            'start' => $i,
        ],
    ];
}

$responseBatch = overCRest::callBatch($cmdBatch)['result']['result'];
$allUserFio = [];

foreach ($responseBatch as $response) {
    foreach ($response as $user) {
        $allUserFio[] = [
            'value' => $user['ID'],
            'name' => trim($user['NAME'] . ' ' . $user['LAST_NAME']),
        ];
    }
}

echo json_encode([
    'result' => $allUserFio,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>