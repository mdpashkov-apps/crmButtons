<?
// $entityBody = file_get_contents('php://input');
// $requestData = json_decode($entityBody, true);
// $memberId = $requestData['memberId'];
// $path = pathinfo(__DIR__, PATHINFO_DIRNAME);
// include_once($path . '/overCRest.php');
// overCRest::setCurrentBitrix24($memberId);
// $entity = $requestData['current_button']['value'];

// // получаю данные 1 хранилища
// $getLists = overCRest::call("lists.get", [
//     "IBLOCK_TYPE_ID" => "lists",
// ]);


// $finalResult = [];

// foreach ($getLists['result'] as $list) {
//     $finalResult[] = [
//         'value' => $list['ID'],
//         'name'  => $list['NAME'],
//     ];
// }
// // file_put_contents(__DIR__.'/result91.log', var_export($finalResult, true), FILE_APPEND);

// echo json_encode([
//     'result' => $finalResult,
// ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  



$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);

$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');

overCRest::setCurrentBitrix24($memberId);

/** 1. Получаем total */
$first = overCRest::call("lists.get", [
    "IBLOCK_TYPE_ID" => "lists",
]);

$total = $first['total'];
$pages = ceil($total / 50);

/** 2. Готовим batch */
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

/** 3. Выполняем batch чанками */
$finalResult = [];
$batchChunks = array_chunk($batch, 50, true);

foreach ($batchChunks as $chunk) {
    sleep(2); // если хочешь — убери

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
// file_put_contents(__DIR__.'/result91.log', var_export($finalResult, true), FILE_APPEND);

/** 4. Ответ */
echo json_encode([
    'result' => $finalResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
