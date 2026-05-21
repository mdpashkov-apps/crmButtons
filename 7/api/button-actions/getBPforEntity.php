<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$entity = $requestData['current_button']['value'];

// Определяем тип документа для бизнес-процессов
if ($entity === '31') {
    $documentType = 'SMART_INVOICE';
} elseif (is_numeric($entity)) {
    $documentType = 'DYNAMIC_' . $entity;
} else {
    $documentType = $entity;
}

// Получаем общее количество шаблонов бизнес-процессов
$total = overCRest::call('bizproc.workflow.template.list',[
    'filter' => [
        'MODULE_ID'     => 'crm',
        'DOCUMENT_TYPE' => $documentType,
    ],
])['total'];

// Считаем количество страниц (по 50 элементов на страницу)
$pages = ceil($total / 50);

// Готовим batch-запросы
$batch = [];
for ($i = 0; $i < $pages; $i++) {
    $batch["list_{$i}"] = [
        'method' => 'bizproc.workflow.template.list',
        'params' => [
            'select' => ['ID', 'NAME'],
            'filter' => [
                'MODULE_ID'     => 'crm',
                'DOCUMENT_TYPE' => $documentType,
            ],
            'start' => $i * 50,
        ],
    ];
}

// Итоговый список бизнес-процессов
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

// возвращаем на фронт
echo json_encode([
    'result' => $bizProcList,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);