<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаю массив с бп которые надо запустить и их параметры
$bpParam = $requestData['bpParam'];
// id бп который надо запустить
$bpId = array_key_first($bpParam);
// параметры
$bpParameters = $bpParam[$bpId];

// фильруем параметры
$filteredParams = [];

foreach ($bpParameters as $key => $value) {
    if ($key === 'user') {
        // если один пользователь
        if (isset($value['value']) && $value['value']) {
            $filteredParams[$key] = 'user_' . $value['value'];
            continue;
        }
        // если несколько пользователей
        if (is_array($value)) {
            $users = [];
            foreach ($value as $item) {
                if (is_array($item) && !empty($item['value'])) {
                    $users[] = 'user_' . $item['value'];
                }
            }
            if (!empty($users)) {
                $filteredParams[$key] = $users;
            }
        }
        continue;
    }
    if (is_string($value)) {
        if ($value !== '') {
            $filteredParams[$key] = $value;
        }
        continue;
    }
    if (is_array($value)) {
        $flat = [];
        array_walk_recursive($value, function ($v) use (&$flat) {
            if ($v !== '' && $v !== null) {
                $flat[] = $v;
            }
        });
        if (!empty($flat)) {
            $filteredParams[$key] = $flat;
        }
    }
}

//получаем id типа сущности
$entityTypeId = $requestData['entityData']['ENTITY_DATA']['entityTypeId'];
$entityMap = [
    '1' => 'Lead',
    '2' => 'Deal',
    '3' => 'Contact',
    '4' => 'Company',
];
$entValue = $entityMap[$entityTypeId] ?? $entityTypeId;

//получаем id сущности
$entityId = (int)$requestData['entityData']['ENTITY_DATA']['entityId'];

// формируем параметр DOCUMENT_ID для запуска бп и запускаем
if ($entValue === '31') {
    $document = [
        'crm',
        'Bitrix\\Crm\\Integration\\BizProc\\Document\\SmartInvoice',
        'SMART_INVOICE_' . $entityId,
    ];
} elseif (is_numeric($entValue)) {
    $document = [
        'crm',
        'Bitrix\\Crm\\Integration\\BizProc\\Document\\Dynamic',
        'DYNAMIC_' . $entValue . '_' . $entityId,
    ];
} else {
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

$startBP = overCRest::call(
    'bizproc.workflow.start',
    [
        'TEMPLATE_ID' => $bpId,
        'DOCUMENT_ID' => $document,
        'PARAMETERS' => $filteredParams
    ]
);
