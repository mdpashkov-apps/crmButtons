<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = $requestData['id'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);

// Получаем данные из хранилища
$result = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
	'ID' => $id
])['result'];

echo json_encode([
    'result' => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);