<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$bpParam = $requestData['bpParam'];

$bpId = array_key_first($bpParam);


$bpParameters = $bpParam[$bpId];



// file_put_contents(__DIR__.'/result91.log', var_export($bpParameters, true), FILE_APPEND);


$filteredParams = [];

foreach ($bpParameters as $key => $value) {

    /** =========================
     * USER
     * ========================= */
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

    /** =========================
     * STRING
     * ========================= */
    if (is_string($value)) {
        if ($value !== '') {
            $filteredParams[$key] = $value;
        }
        continue;
    }

    /** =========================
     * ARRAY (number, text, multi)
     * ========================= */
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







$entityTypeId = $requestData['entityData']['ENTITY_DATA']['entityTypeId'];
$entityMap = [
    '1' => 'Lead',
    '2' => 'Deal',
    '3' => 'Contact',
    '4' => 'Company',
];
$entValue = $entityMap[$entityTypeId] ?? $entityTypeId;



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

$startBP = overCRest::call(
    'bizproc.workflow.start',
    [
        'TEMPLATE_ID' => $bpId,
        'DOCUMENT_ID' => $document,
        'PARAMETERS' => $filteredParams
    ]
);


