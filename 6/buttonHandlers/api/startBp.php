<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$bpParam = $requestData['bpParam'];

$businessProcessesData = json_decode($requestData['crmActions']['businessProcessesValue_FIELDS'], true);
$bpId = $businessProcessesData[0]['value'];

$entityData = json_decode($requestData['crmActions']['entitySelection_FIELDS'], true);
$entValue = $entityData['value'];
file_put_contents(__DIR__.'/result91.log', var_export($bpParam, true), FILE_APPEND);


$entityId = (int)$requestData['entityData']['ENTITY_DATA']['entityId'];

if ($entValue === '31') {
    // смарт-счёт
    $document = [
        'crm',
        'Bitrix\\Crm\\Integration\\BizProc\\Document\\SmartInvoice',
        'SMART_INVOICE_' . $entityId,
    ];
} elseif (is_numeric($entValue)) {
    // любой смарт-процесс
    $document = [
        'crm',
        'Bitrix\\Crm\\Integration\\BizProc\\Document\\Dynamic',
        'DYNAMIC_' . $entValue . '_' . $entityId,
    ];
} else {
    // стандартные сущности
    $map = [
        'Lead'    => 'CCrmDocumentLead',
        'Deal'    => 'CCrmDocumentDeal',
        'Contact' => 'CCrmDocumentContact',
        'Company' => 'CCrmDocumentCompany',
    ];
    $document = [
        'crm',
        $map[$entValue],
        strtoupper($entValue) . '_' . $entityId,
    ];
}

// $startBP = overCRest::call(
//     'bizproc.workflow.start',
//     [
//         'TEMPLATE_ID' => $bpId,
//         'DOCUMENT_ID' => $document,
//     ]
// );