<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаю данные хранилища
$getButtons = overCRest::call("entity.item.get", [
    "ENTITY" => "customButton",
]);

$items = $getButtons['result']; // массив элементов хранилища
$moreItems = array_slice($items, 6); // начиная с 7-го элемента

echo json_encode([
    'result' => $moreItems,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);