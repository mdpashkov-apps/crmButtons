<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$searchableButtonID = $requestData['button_ID'];

// получаю данные 1 хранилища
$result = overCRest::call("entity.item.get", [
    "ENTITY" => "customButton",
    'FILTER' => [
        'ID' => $searchableButtonID,
    ]
]);

$button = $result['result'][0]['PROPERTY_VALUES'];
// file_put_contents(__DIR__.'/result91.log', var_export($button, true), FILE_APPEND);




echo json_encode([
    'result' => $button,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);