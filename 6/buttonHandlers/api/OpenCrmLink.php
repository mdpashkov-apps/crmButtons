<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$entityId = (int)$requestData['entityData']['ENTITY_DATA']['entityId'];

$entityTypeId = $requestData['entityData']['ENTITY_DATA']['entityTypeId'];
$entityMap = [
    '1'    => 'lead',
    '2'    => 'deal',
    '3' => 'contact',
    '4' => 'company',
];
$entityType = $entityMap[$entityTypeId];

$linkFieldData = json_decode($requestData['crmActions']['crmLinkFields_FIELDS'], true);
$fieldCode = $linkFieldData['value'] ;

if (is_numeric($entityTypeId)) {
    $getFieldValue = overCRest::call('crm.item.get', [
        'entityTypeId' => $entityTypeId,
        'id' => $entityId,    
    ]);
    $linkValue = $getFieldValue['result']['item'][$fieldCode];
} else {
    $getFieldValue = overCRest::call('crm.' . $entityType . '.get', [
        'id' => $entityId
    ]);
    $linkValue = $getFieldValue['result'][$fieldCode] ;
}

echo json_encode([
    'result' => $linkValue,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);