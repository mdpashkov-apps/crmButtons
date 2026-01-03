<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);


$businessProcessesData = json_decode($requestData['crmActions']['businessProcessesValue_FIELDS'], true);
// $bpId = $businessProcessesData[0]['value'];
$bpIds = array_column($businessProcessesData, 'value');
$bpIds = array_map('intval', $bpIds);





$entityData = json_decode($requestData['crmActions']['entitySelection_FIELDS'], true);
$entityTypeIdMap = $entityData['value'];








if ($entityTypeIdMap === '31') {
    $documentType = 'SMART_INVOICE';
} elseif (is_numeric($entityTypeIdMap)) {
    $documentType = 'DYNAMIC_' . $entity;
} else {
    // лид, сделка, контакт и т.п.
    $documentType = $entityTypeIdMap;
}





$getBizProc = overCRest::call(
    'bizproc.workflow.template.list',
    [
        'select' => [
            'ID',
            'NAME',
            'PARAMETERS',
           
        ],
        'filter' => [
            'MODULE_ID'    => 'crm',
            'DOCUMENT_TYPE'=> $documentType,
            'ID' => $bpIds, // ← фильтр по BP

        ],
       
    ]
);
		// file_put_contents(__DIR__.'/result91.log', var_export($getBizProc, true), FILE_APPEND);


$typeMap = [
    'string' => 'txt',
    'text'   => 'txt',
    'int'    => 'number',
    'double' => 'number',
    'email' => 'txt',
    'phone' => 'txt',
    'web' => 'txt',
    'user' => 'user'
];

$result = [];

foreach ($getBizProc['result'] as $bp) {

    $filteredParams = [];

    if (!empty($bp['PARAMETERS'])) {
foreach ($bp['PARAMETERS'] as $paramKey => $param) {
            if (isset($typeMap[$param['Type']])) {
                $filteredParams[] = [
                    'paramKey' => $paramKey,
                    'Name'     => $param['Name'],
                    'Type'     => $typeMap[$param['Type']],
                    'Required' => (int)$param['Required'],
                    'Multiple' => (int)$param['Multiple'],
                    'Default'  => $param['Default'],
                ];
            }
        }
    }

    $result[] = [
        'ID'         => (int)$bp['ID'],
        'NAME'       => $bp['NAME'],
        'PARAMETERS' => $filteredParams, // даже если пусто — ок
    ];
}


		// file_put_contents(__DIR__.'/result91.log', var_export($result, true), FILE_APPEND);
$allUserFio = null; 

foreach ($result[0]['PARAMETERS'] as $param) {
    if (($param['Type'] ?? null) === 'user') {
       

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

        		// file_put_contents(__DIR__.'/result91.log', var_export($allUserFio, true), FILE_APPEND);


    }
}

echo json_encode([
    'result' => $result,
    'allUserFio' => $allUserFio
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


