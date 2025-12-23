<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$searchableButtonID = $requestData['activeButtonId'];

 $deleteSecond = overCRest::call('entity.item.delete', [
        'ENTITY' => 'customButton',
        'ID' => $searchableButtonID,
    ]);