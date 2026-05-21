<?php
// api/button-actions/getFeedWorkflowParameters.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$templateId = $requestData['templateId'];
$documentId = $requestData['documentId'] ?? null;

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$result = overCRest::call('bizproc.workflow.template.list', [
    'select' => ['ID', 'NAME', 'PARAMETERS', 'DOCUMENT_TYPE', 'MODULE_ID', 'ENTITY'],
    'filter' => ['ID' => (int)$templateId]
]);

$parameters = [];
$documentType = null;
$moduleId = null;
$entity = null;

if (!isset($result['error']) && !empty($result['result'][0])) {
    $parameters = $result['result'][0]['PARAMETERS'] ?? [];
    $documentType = $result['result'][0]['DOCUMENT_TYPE'] ?? null;
    $moduleId = $result['result'][0]['MODULE_ID'] ?? 'lists';
    $entity = $result['result'][0]['ENTITY'] ?? 'BizprocDocument';
}

$hasRequiredParams = false;
foreach ($parameters as $param) {
    if (isset($param['Required']) && $param['Required']) {
        $hasRequiredParams = true;
        break;
    }
}

echo json_encode([
    'result' => [
        'parameters' => $parameters,
        'hasRequiredParams' => $hasRequiredParams,
        'documentType' => $documentType,
        'moduleId' => $moduleId,
        'entity' => $entity
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>