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

// итоговый массив параметров, который пойдёт в запуск БП
$filteredParams = [];

foreach ($bpParameters as $paramKey => $paramData) {
    // тип параметра (например: user, string, list и т.п.)
    $type = $paramData['type'] ?? null;
    // является ли параметр множественным
    $multiple = $paramData['multiple'] ?? false;
    // значение параметра
    $value = $paramData['value'] ?? null;

    // Обработка параметров типа "Пользователь"
    if ($type === 'user') {
        // одиночный пользователь
        // ожидаем массив вида ['value' => ID]
        if (!$multiple && is_array($value) && !empty($value['value'])) {
            $filteredParams[$paramKey] = 'user_' . $value['value'];
            continue;
        }

        // множественный пользователь
        // ожидаем массив пользователей, каждый с ['value' => ID]
        if ($multiple && is_array($value)) {
            $users = [];
            foreach ($value as $item) {
                if (isset($item['value'])) {
                    $users[] = 'user_' . $item['value'];
                }
            }

            // добавляем параметр только если есть пользователи
            if (!empty($users)) {
                $filteredParams[$paramKey] = $users;
            }
        }
        continue;
    }

    // Обработка строковых значений (одиночные поля без множественного выбора)
    if (is_string($value)) {
        // игнорируем пустые строки
        if ($value !== '') {
            $filteredParams[$paramKey] = $value;
        }
        continue;
    }

    // Обработка массивов значений- множественный выбор)
    if (is_array($value)) {
        // убираем пустые и null значения
        $flat = [];
        foreach ($value as $v) {
            if ($v !== '' && $v !== null) {
                $flat[] = $v;
            }
        }
        // если после фильтрации что-то осталось
        if (!empty($flat)) {
            // если параметр множественный — передаём массив
            // если одиночный — берём первое значение
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

echo json_encode([
    'templateId' => (int)$bpId,
    'document'   => $document,
    'parameters' => $filteredParams
], JSON_UNESCAPED_UNICODE);