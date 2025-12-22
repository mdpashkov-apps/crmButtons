<?php

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
include_once ($path . '/overCRest.php');

$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$selected = '';

function generateParamsBatchParams(string $documentType, int $offset = 0): array
{
    return [
        'SELECT' => [
            'ID',
            'NAME',
        ],
        'FILTER' => [
            'DOCUMENT_TYPE' => $documentType,
        ],
        'start' => $offset
    ];
}

$entityType = $requestData['entityType'];
$documentTypeBizproc = match ($entityType) {
    'Lead' => 'LEAD',
    'Contact' => 'CONTACT',
    'Company' => 'COMPANY',
    'Deal' => 'DEAL',
    '31' => 'SMART_INVOICE',
    default => 'DYNAMIC_' . (int) $entityType,
};

$totalBizproc = overCRest::call(
    'bizproc.workflow.template.list',
    generateParamsBatchParams($documentTypeBizproc)
)['total'];
$totalPage = ceil($totalBizproc / 50);
$response = [];
$result = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton'
])['result'];
foreach ($result as $item) {
    if ($item['ACTIVE'] === 'Y' && $item['PROPERTY_VALUES']['businessProcessesValue_FIELDS']) {
        $selected = $item['PROPERTY_VALUES']['businessProcessesValue_FIELDS'];
    }
}
if ($documentTypeBizproc === 'SmartProccess') {
    $storage = overCRest::call('entity.item.get', [
        'ENTITY' => 'customButton'
    ])['result'];

    foreach ($storage as $elemtStorage) {
        if ($elemtStorage['ACTIVE'] === 'Y') {
            $entityTypeId = (int) $elemtStorage['PROPERTY_VALUES']['entitySelection_FIELDS'];
            $documentTypeBizproc = 'DYNAMIC_' . $entityTypeId;
            break;
        }
    }
}

if ($totalPage > 1) {
    for ($currentPage = 1; $currentPage <= $totalPage; $currentPage += 50) {
        $arrayBatch = [];
        $batchSize = min(50, $totalPage - $currentPage + 1);

        for ($i = 0; $i < $batchSize; $i++) {
            $page = $i * 50;
            $arrayBatch[$i . '_items'] = [
                'method' => 'bizproc.workflow.template.list',
                'params' => generateParamsBatchParams($documentTypeBizproc, $page),
            ];
        }

        $responseBatch[] = overCRest::callBatch($arrayBatch);

        unset($arrayBatch);
    }
    foreach ($responseBatch[0]['result']['result'] as $key => $value) {
        for ($i = 0; $i < count($value); $i++) {
            $response[] = $value[$i];
        }
    }
} else {
    $arrayBatch = [];
    for ($page = 0; $page <= $totalPage; $page += 50) {
        $arrayBatch[$page . '_items'] = [
            'method' => 'bizproc.workflow.template.list',
            'params' => generateParamsBatchParams($documentTypeBizproc, $page),
        ];
    }
    $responseBatch = overCRest::callBatch($arrayBatch);
    unset($arrayBatch);
    foreach ($responseBatch['result']['result']['0_items'] as $bizproc) {
        $response[] = $bizproc;
    }
    unset($responseBatch);
}
$responseUniq = [];
foreach ($response as $key => $item) {
    $responseUniq[$key]['value'] = $item['ID'];
    $responseUniq[$key]['name'] = $item['NAME'];
}
$value = [];
$jsonDecodeValueFields = json_decode($selected);
if (!empty($jsonDecodeValueFields)) {
    $selectedResult = [];
    foreach ($responseUniq as $key => $fields) {
        if ($jsonDecodeValueFields[0] === $fields['value']) {
            $selectedResult['value'] = $jsonDecodeValueFields[0];
            $selectedResult['name'] = $fields['name'];
        }
    }
}
$arrayResponse = [
    'options' => $responseUniq,
    'selected' => $selectedResult,
];
echo json_encode($arrayResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
