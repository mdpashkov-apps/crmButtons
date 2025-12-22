
<?php
$entityBPBody = file_get_contents('php://input');
$requestData = json_decode($entityBPBody, true);
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once ($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$valueEntity = $requestData['valueEntity'];
$newData = [];

$newData['lists'] = [
    'options' => [],
    'value' => []
];
$newData['businessProcesses'] = [
    'options' => [],
    'value' => []
];
$newData['documentTemplates'] = [
    'options' => [],
    'value' => []
];

// Пишем содержимое обратно в файл

echo json_encode([
    'newData' => $newData,
    'test' => [],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
