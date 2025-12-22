<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = $requestData['id'];
// Путь и подключение CRestExt
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);

$result = overCRest::call('entity.item.delete', [
	'ENTITY' => 'customButton',
	"ID" => $id
]);
