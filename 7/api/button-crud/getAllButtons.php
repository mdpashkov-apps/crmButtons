<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаю данные хранилища (все кнопки)
$getButtons = overCRest::call("entity.item.get", [
    "ENTITY" => "customButton",
]);

echo json_encode([
    'result' => $getButtons,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);