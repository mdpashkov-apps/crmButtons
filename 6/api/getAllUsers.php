<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);

include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);


$allUserFio = []; 

        $totalUser = overCRest::call('user.search', [
            'filter' => ['ACTIVE' => true],
        ])['total'];

        $cmdBatch = [];
        for ($i = 0; $i < $totalUser; $i = $i + 50) {
            $cmdBatch[] = [
                'method' => 'user.search',
                'params' => [
                    'filter' => [
                        'ACTIVE' => true
                    ],
                    'start' => $i,
                ],
            ];
        }
        $responseBatch = overCRest::callBatch($cmdBatch)['result']['result'];
        $allUserFio = [];
        foreach ($responseBatch as $response) {
            $tmpArray = [];
            foreach ($response as $user) {
                $tmpArray[] = [
                    'value' => $user['ID'],
                    'name' => $user['NAME'] . ' ' . $user['LAST_NAME'],
                ];
            }
            $allUserFio = array_merge($allUserFio, $tmpArray);
        }


        echo json_encode([
    'result' => $allUserFio,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
