<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$entityId = (int)$requestData['entityData']['ENTITY_DATA']['entityId'];

$docTemplateData = json_decode($requestData['crmActions']['documentTemplatesValue_FIELDS'], true);
$docTemplateId = $docTemplateData[0]['value'];

$entityTypeId = $requestData['entityData']['ENTITY_DATA']['entityTypeId'];

$documentCreate = overCRest::call('crm.documentgenerator.document.add', [
    'templateId' => $docTemplateId,
    'entityTypeId' => $entityTypeId,
    'entityId' => $entityId
]);

