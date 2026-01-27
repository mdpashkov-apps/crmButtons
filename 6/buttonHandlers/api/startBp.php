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
// file_put_contents(__DIR__.'/result91.log', var_export($bpParam, true), FILE_APPEND);



$filteredParams = [];

foreach ($bpParameters as $paramKey => $paramData) {

    $type     = $paramData['type'] ?? null;
    $multiple = $paramData['multiple'] ?? false;
    $value    = $paramData['value'] ?? null;

    // ===== USER =====
    if ($type === 'user') {

        // одиночный пользователь
        if (!$multiple && is_array($value) && !empty($value['value'])) {
            $filteredParams[$paramKey] = 'user_' . $value['value'];
            continue;
        }

        // множественный пользователь
        if ($multiple && is_array($value)) {
            $users = [];
            foreach ($value as $item) {
                if (isset($item['value'])) {
                    $users[] = 'user_' . $item['value'];
                }
            }
            if (!empty($users)) {
                $filteredParams[$paramKey] = $users;
            }
        }

        continue;
    }

    // ===== STRING / INT / LIST =====
    if (is_string($value)) {
        if ($value !== '') {
            $filteredParams[$paramKey] = $value;
        }
        continue;
    }

    if (is_array($value)) {
        $flat = [];
        foreach ($value as $v) {
            if ($v !== '' && $v !== null) {
                $flat[] = $v;
            }
        }
        if (!empty($flat)) {
            $filteredParams[$paramKey] = $multiple ? $flat : $flat[0];
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


// file_put_contents(__DIR__.'/result91.log', var_export($filteredParams, true), FILE_APPEND);



echo json_encode([
    'templateId' => (int)$bpId,
    'document'   => $document,
    'parameters' => $filteredParams
], JSON_UNESCAPED_UNICODE);