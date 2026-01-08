<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$listsGet = overCRest::call("lists.get", [
    "IBLOCK_TYPE_ID" => "lists",
]);

$total = $listsGet['total'];
$pages = ceil($total / 50);

$batch = [];
for ($i = 0; $i < $pages; $i++) {
    $batch["lists_{$i}"] = [
        'method' => 'lists.get',
        'params' => [
            'IBLOCK_TYPE_ID' => 'lists',
            'start' => $i * 50,
        ],
    ];
}

$finalResult = [];
$batchChunks = array_chunk($batch, 50, true);
foreach ($batchChunks as $chunk) {
    sleep(2);
    $result = overCRest::callBatch($chunk, false)['result']['result'];
    foreach ($result as $lists) {
        foreach ($lists as $list) {
            $finalResult[] = [
                'value' => $list['ID'],
                'name'  => $list['NAME'],
            ];
        }
    }
}

echo json_encode([
    'result' => $finalResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
