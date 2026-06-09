<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// сущность кнопки (для определения DOCUMENT_TYPE)
$entity = $requestData['entity'] ?? null;
if (is_array($entity) && isset($entity['value'])) {
    $entity = $entity['value'];
}

$bpIds = $requestData['bpIds'] ?? [];
if (!is_array($bpIds)) {
    $bpIds = [];
}
$bpIds = array_map('intval', $bpIds);
$bpIds = array_values(array_filter($bpIds, function ($id) { return $id > 0; }));

if (empty($bpIds) || $entity === null) {
    echo json_encode(['result' => new stdClass()], JSON_UNESCAPED_UNICODE);
    exit;
}

// DOCUMENT_TYPE
if ((string)$entity === '31') {
    $documentType = 'SMART_INVOICE';
} elseif ((string)$entity === '7') {
    $documentType = 'Quote';
} elseif (is_numeric($entity)) {
    $documentType = 'DYNAMIC_' . $entity;
} else {
    $documentType = $entity;
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

$result = [];
foreach (($getBizProc['result'] ?? []) as $bp) {
    $bpId = (string)(int)$bp['ID'];
    $params = [];
    if (!empty($bp['PARAMETERS'])) {
        foreach ($bp['PARAMETERS'] as $paramKey => $param) {
            $type = $param['Type'] ?? null;
            if (!isset($typeMap[$type])) {
                continue;
            }
            $params[] = [
                'paramKey' => $paramKey,
                'Name'     => $param['Name'],
                'Type'     => $typeMap[$type],
                'Required' => (int)$param['Required'],
                'Multiple' => (int)$param['Multiple'],
                'Default'  => $param['Default'],
                'Options'  => $param['Options'] ?? null,
            ];
        }
    }
    $result[$bpId] = $params;
}

echo json_encode(['result' => $result], JSON_UNESCAPED_UNICODE);
