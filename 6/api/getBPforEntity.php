<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$entity = $requestData['current_button']['value'];


$total = overCRest::call(
    'bizproc.workflow.template.list',
    [
        'filter' => [
            'MODULE_ID'     => 'crm',
            'DOCUMENT_TYPE' => $entity,
        ],
    ]
)['total'];

$pages = ceil($total / 50);



$batch = [];

for ($i = 0; $i < $pages; $i++) {
    $batch["list_{$i}"] = [
        'method' => 'bizproc.workflow.template.list',
        'params' => [
            'select' => ['ID', 'NAME'],
            'filter' => [
                'MODULE_ID'     => 'crm',
                'DOCUMENT_TYPE' => $entity,
            ],
            'start' => $i * 50,
        ],
    ];
}


$bizProcList = [];
$batchChunks = array_chunk($batch, 50, true);

foreach ($batchChunks as $chunk) {
    sleep(2); // щадящий режим

    $result = overCRest::callBatch($chunk, false)['result']['result'];

    foreach ($result as $bpList) {
        foreach ($bpList as $bp) {
            $bizProcList[] = [
                'value' => $bp['ID'],
                'name'  => $bp['NAME'],
            ];
        }
    }
}




    echo json_encode([
    'result' => $bizProcList,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);



