<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);


$linkFieldData = json_decode($requestData['linkField'], true);
$fieldCode = $linkFieldData['value'] ;

$leadId = 3;

$leadGet = overCRest::call('crm.lead.get', [
    'id' => $leadId
    
]);

$linkValue = $leadGet['result'][$fieldCode] ;

// file_put_contents(__DIR__.'/result91.log', var_export($linkValue, true), FILE_APPEND);



    echo json_encode([
    'result' => $linkValue,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);