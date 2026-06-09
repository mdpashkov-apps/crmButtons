<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// читаем настройки цепочки: [{value, name, presets?}, ...]
$chainSettings = json_decode($requestData['crmActions']['bpChainValue_FIELDS'], true);
if (!is_array($chainSettings)) {
    $chainSettings = [];
}

$bpIds = array_map('intval', array_column($chainSettings, 'value'));

$entityId = $requestData['entityData']['ENTITY_DATA']['entityId'];
$rawEntity = $requestData['crmActions']['entitySelection_FIELDS'];

$entityData = json_decode($rawEntity, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($entityData) && isset($entityData['value'])) {
    $entityTypeIdMap = $entityData['value'];
} else {
    $entityTypeIdMap = $rawEntity;
}

if ($entityTypeIdMap === '31') {
    $documentType = 'SMART_INVOICE';
} elseif ($entityTypeIdMap === '7') {
    $documentType = 'Quote';
} elseif (is_numeric($entityTypeIdMap)) {
    $documentType = 'DYNAMIC_' . $entityTypeIdMap;
} else {
    $documentType = $entityTypeIdMap;
}

$getBizProc = overCRest::call(
    'bizproc.workflow.template.list',
    [
        'select' => ['ID', 'NAME', 'PARAMETERS'],
        'filter' => [
            'MODULE_ID'     => 'crm',
            'DOCUMENT_TYPE' => $documentType,
            'ID'            => $bpIds,
        ],
    ]
);

$bpById = [];
foreach (($getBizProc['result'] ?? []) as $bp) {
    $bpById[(int)$bp['ID']] = $bp;
}

$typeMap = [
    'string'   => 'txt',
    'text'     => 'txt',
    'int'      => 'number',
    'double'   => 'number',
    'email'    => 'txt',
    'phone'    => 'txt',
    'web'      => 'txt',
    'user'     => 'user',
    'bool'     => 'bool',
    'datetime' => 'datetime',
    'select'   => 'select',
];

$chain = [];
$hasUserParam = false;
foreach ($chainSettings as $chainItem) {
    $bpId = (int)($chainItem['value'] ?? 0);
    if ($bpId <= 0 || !isset($bpById[$bpId])) {
        continue;
    }

    $bp = $bpById[$bpId];
    $filteredParams = [];
    if (!empty($bp['PARAMETERS'])) {
        foreach ($bp['PARAMETERS'] as $paramKey => $param) {
            $type = $param['Type'] ?? null;
            if (!isset($typeMap[$type])) {
                continue;
            }
            $mappedType = $typeMap[$type];
            if ($mappedType === 'user') {
                $hasUserParam = true;
            }
            $filteredParams[] = [
                'paramKey' => $paramKey,
                'Name'     => $param['Name'],
                'Type'     => $mappedType,
                'Required' => (int)$param['Required'],
                'Multiple' => (int)$param['Multiple'],
                'Default'  => $param['Default'],
                'Options'  => $param['Options'] ?? null,
            ];
        }
    }

    $presets = (isset($chainItem['presets']) && is_array($chainItem['presets']))
        ? $chainItem['presets']
        : new stdClass();

    $chain[] = [
        'ID'         => $bpId,
        'NAME'       => $bp['NAME'],
        'PARAMETERS' => $filteredParams,
        'presets'    => $presets,
    ];
}

if ($entityTypeIdMap === '31') {
    $document = ['crm', 'Bitrix\\Crm\\Integration\\BizProc\\Document\\SmartInvoice', 'SMART_INVOICE_' . $entityId];
} elseif ($entityTypeIdMap === '7') {
    $document = ['crm', 'CCrmDocumentQuote', 'QUOTE_' . $entityId];
} elseif (is_numeric($entityTypeIdMap)) {
    $document = ['crm', 'Bitrix\\Crm\\Integration\\BizProc\\Document\\Dynamic', 'DYNAMIC_' . $entityTypeIdMap . '_' . $entityId];
} else {
    $map = [
        'Lead'    => 'CCrmDocumentLead',
        'Deal'    => 'CCrmDocumentDeal',
        'Contact' => 'CCrmDocumentContact',
        'Company' => 'CCrmDocumentCompany',
    ];
    $document = ['crm', $map[$entityTypeIdMap], strtoupper($entityTypeIdMap) . '_' . $entityId];
}

$allUserFio = null;
if ($hasUserParam) {
    $totalUser = overCRest::call('user.search', [
        'filter' => ['ACTIVE' => true],
    ])['total'];

    $cmdBatch = [];
    for ($i = 0; $i < $totalUser; $i += 50) {
        $cmdBatch[] = [
            'method' => 'user.search',
            'params' => [
                'filter' => ['ACTIVE' => true],
                'start'  => $i,
            ],
        ];
    }
    $responseBatch = overCRest::callBatch($cmdBatch)['result']['result'];
    $allUserFio = [];
    foreach ($responseBatch as $response) {
        foreach ($response as $user) {
            $allUserFio[] = [
                'value' => $user['ID'],
                'name'  => $user['NAME'] . ' ' . $user['LAST_NAME'],
            ];
        }
    }
}

echo json_encode([
    'chain'      => $chain,
    'document'   => $document,
    'allUserFio' => $allUserFio,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
