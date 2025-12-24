<?php
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
include_once ($path . '/overCRest.php');

$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$storeage = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton'
])['result'];
$selected = [];
$result = [];
foreach ($storeage as $item) {
    if ($item['ACTIVE'] === 'Y' && $item['PROPERTY_VALUES']['documentTemplatesValue_FIELDS']) {
        $selected[0]['value'] = json_decode($item['PROPERTY_VALUES']['documentTemplatesValue_FIELDS'])[0];
    }
}

function unique_multidim_array($array, $key)
{
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}

function generateParamsBatchParams(string $ent, int $offset = 0): array
{
    return [
        'filter' => [
            'entityTypeId' => $ent
        ],
        'select' => [
            'id',
            'name',
            'entityTypeId'
        ],
        'start' => $offset
    ];
}

$entityType = $requestData['entityType'];
$tip = match ($entityType) {
    'Contact', 'Company', 'Lead' => 0,
    default => 1,  //  DEAL SP SMART_INVOICE
};
$entityTypeId = match ($entityType) {
    'Lead' => 1,
    'Deal' => 2,
    'Contact' => 3,
    'Company' => 4,
    '31' => 31,
    default => (int) $entityType,
};

$result = [];
if ($tip === 1) {
    $resultCategory = overCRest::call('crm.category.list', [
        'entityTypeId' => $entityTypeId
    ])['result']['categories'];
    $resultBatchDocuments = [];
    foreach ($resultCategory as $elem) {
        if ($entityTypeId === 2) {
            $ent = '2_category_' . (string) $elem['id'];
        } elseif (
            $entityTypeId !== 1 &&
            $entityTypeId !== 2 &&
            $entityTypeId !== 3 &&
            $entityTypeId !== 4 &&
            $entityTypeId !== 31
        ) {
            $ent = $entityTypeId . '_' . (string) $elem['id'];
        } else {
            $ent = $entityTypeId . '_' . (string) $elem['id'];
        }
        $batchParams = [
            'method' => 'crm.documentgenerator.template.list',
            'params' => [
                'filter' => ['entityTypeId' => $ent],
                'select' => ['id', 'name', 'entityTypeId']
            ]
        ];
        $totalDocument = overCRest::call('crm.documentgenerator.template.list', ['filter' => ['entityTypeId' => $ent]])['total'];

        $listDocument = ceil($totalDocument / 50);  // Количество необходимых листов +1 тк от нуля
        $batchArrDocument = [];
        for ($i = 0; $i < $listDocument; $i++) {
            $batchParams['params']['start'] = $i * 50;
            $batchArrDocument[(int) ($i / 49)]['list_' . $i] = $batchParams;
        }
        foreach ($batchArrDocument as $cmd_arr) {
            $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
            foreach ($batchResult as $elementDocument) {
                $resultBatchDocuments = array_merge($resultBatchDocuments, $elementDocument['templates']);
            }
        }
    }
    
    $resultDocuments = [];
    foreach ($resultBatchDocuments as $element) {
        $resultDocuments[] = ['id' => $element['id'], 'name' => $element['name']];
    }
    $result = unique_multidim_array($resultDocuments, 'id');
} else {
    $batchParams = [
        'method' => 'crm.documentgenerator.template.list',
        'params' => [
            'select' => ['id', 'name'],
            'filter' => ['entityTypeId' => $entityTypeId]
        ]
    ];
    $totalDocument = overCRest::call(
        'crm.documentgenerator.template.list',
        ['filter' => ['entityTypeId' => $entityTypeId]]
    )['total'];
    $listDocument = ceil($totalDocument / 50);  // Количество необходимых листов +1 тк от нуля
    $bacthArrDocument = [];
    for ($i = 0; $i < $listDocument; $i++) {
        $batchParams['params']['start'] = $i * 50;
        $bacthArrDocument[(int) ($i / 49)]['list_' . $i] = $batchParams;
    }
    foreach ($bacthArrDocument as $key => $cmd_arr) {
        $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
        foreach ($batchResult as $elementDocument) {
            $result = array_merge($result, $elementDocument['templates']);
        }
    }
}

$resultUniq = [];
$resultNormalized = array_values($result);
$resultUniq = array_map(function ($element) {
    return [
        'value' => $element['id'],
        'name' => $element['name'],
    ];
}, $resultNormalized);
if (count($selected) === 1) {
    foreach ($resultUniq as $key => $value) {
        if ($value['value'] === $selected[0]['value']) {
            $selected[0]['name'] = $value['name'];
        }
    }
}

echo json_encode(
    [
        'options' => $resultUniq,
        'value' => $selected,
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);
