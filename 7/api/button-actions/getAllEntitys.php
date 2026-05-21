<?php
// api/button-actions/getAllEntitys.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$staticEntities= [
    [
        "value" => "Lead",
        "name" => "Лид"
    ],
    [
        "value" => "Deal",
        "name" => "Сделка"
    ],
    [
        "value" => "Contact",
        "name" => "Контакт"
    ],
    [
        "value" => "Company",
        "name" => "Компания"
    ],
    [
        'value' => '7',
        'name' => 'Предложение'
    ],
    [
        'value' => '31',
        'name' => 'Счета'
    ],
];

//получение списка смарт процессов
$smartProcesses = [];

$batchSP = [
    'method' => 'crm.type.list',
    'params' => [
      'select' => ['ID', 'NAME'],
      'filter' => []
    ] 
];
  
$totalSP = overCRest::call('crm.type.list', [])["total"];

$listSP = ceil($totalSP / 50); //Количество необходимых листов +1 тк от нуля
$bacthArrSP = [];
for ($i = 0; $i < $listSP; $i++) {
    $batchParamsSP = $batchSP;
    $batchParamsSP['params']['start'] =  $i * 50;
    $bacthArrSP[(int)($i / 49)]["list_" . $i] =  $batchParamsSP;
}

$resultSP = [];
foreach ($bacthArrSP as $key => $cmdSP_arr) {
    sleep(2); //Щадяший режим лучше ставить 2 секунды
    $batchResultSP = overCRest::callBatch($cmdSP_arr, false)['result']['result'];
    foreach ($batchResultSP as $elementSP) {
        // Собираем типы из каждого ответа пачки напрямую в плоский массив,
        // чтобы не перезаписывать ключ 'types' при array_merge
        if (!empty($elementSP['types'])) {
            $resultSP = array_merge($resultSP, $elementSP['types']);
        }
    }
}
foreach ($resultSP as $SP) {
    array_push($smartProcesses, ["value" => $SP['entityTypeId'], "name" => $SP['title']]);
}
$resultEntities = array_merge($staticEntities, $smartProcesses);

echo json_encode([
    'result' => $resultEntities,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);