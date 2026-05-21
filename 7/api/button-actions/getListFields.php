<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$list = $requestData['list']['value']; // ID выбранного списка с фронта

// Получаем список полей выбранного списка
$getListFields = overCRest::call("lists.field.get", [
    "IBLOCK_TYPE_ID" => "lists",
    'IBLOCK_ID' => $list
]);

$finalResult = [];
// Преобразуем результат в нужную структуру
foreach ($getListFields['result'] as $field) {
    $finalResult[] = [
        'value' => $field['FIELD_ID'],
        'name'  => $field['NAME'],
    ];
}

echo json_encode([
    'result' => $finalResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
