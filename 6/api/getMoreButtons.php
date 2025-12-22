<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаю данные 1 хранилища
$result = overCRest::call("entity.item.get", [
    "ENTITY" => "customButton",
]);


$items = $result['result'];           // массив элементов хранилища
$moreItems = array_slice($items, 6);  // начиная с 7-го элемента

// file_put_contents(__DIR__.'/result91.log', var_export($moreItems, true), FILE_APPEND);


echo json_encode([
    'result' => $moreItems,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);