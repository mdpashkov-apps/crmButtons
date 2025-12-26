<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);


$businessProcessesData = json_decode($requestData['crmActions']['businessProcessesValue_FIELDS'], true);
$bpId = $businessProcessesData[0]['value'];

$entityData = json_decode($requestData['crmActions']['entitySelection_FIELDS'], true);
$entValue = $entityData['value'];








$startBP = overCRest::call(
    'bizproc.workflow.start',
    [
        'TEMPLATE_ID' => $bpId,
        'DOCUMENT_ID' => [
            'crm',
            'CCrmDocument' . $entValue,
            strtoupper($entValue) . '_3'
        ],
     
    ]
);

// file_put_contents(__DIR__.'/result91.log', var_export($startBP, true), FILE_APPEND);




    echo json_encode([
    'result' => $startBP,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);