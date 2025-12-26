<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);

$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);


$docTemplateData = json_decode($requestData['crmActions']['documentTemplatesValue_FIELDS'], true);
$docTemplateId = $docTemplateData[0]['value'];
file_put_contents(__DIR__.'/result91.log', var_export($docTemplateId, true), FILE_APPEND);

$leadId = 5;

$documentCreate = overCRest::call('crm.documentgenerator.document.add', [
    'templateId' => $docTemplateId,
    'entityTypeId' => 2,
    'entityId' => $leadId
]);


file_put_contents(__DIR__.'/result91.log', var_export($docTemplateId, true), FILE_APPEND);




//     echo json_encode([
//     'result' => $linkValue,
// ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);