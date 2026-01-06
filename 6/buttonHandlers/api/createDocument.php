<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаем id типа сущности и id сущности
$entityTypeId = $requestData['entityData']['ENTITY_DATA']['entityTypeId'];
$entityId = (int)$requestData['entityData']['ENTITY_DATA']['entityId'];

// получаем id шаблона документа
$docTemplateData = json_decode($requestData['crmActions']['documentTemplatesValue_FIELDS'], true);
$docTemplateId = $docTemplateData[0]['value'];

// создаем документ
$documentCreate = overCRest::call('crm.documentgenerator.document.add', [
    'templateId' => $docTemplateId,
    'entityTypeId' => $entityTypeId,
    'entityId' => $entityId
]);