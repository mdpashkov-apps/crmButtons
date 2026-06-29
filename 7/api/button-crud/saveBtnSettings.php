<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаем массив настроек которые надо сохранить в entity для кнопки
$btnSettings = $requestData['btnSettings'];

// гейтинг PRO-действий: «Ссылка с параметрами» (4) и «Цепочка БП» (5) — только при доступной фиче.
// Защита от обхода UI: проверяем и при создании, и при обновлении кнопки.
require_once(__DIR__ . '/../billing/BillingClient.php');
$__actions = $btnSettings['buttonActionsId_FIELDS'] ?? [];
if (is_string($__actions)) { $__actions = json_decode($__actions, true) ?: []; }
$__actions = array_map('intval', (array)$__actions);
$__proMap = [4 => 'link_with_params', 5 => 'bp_chains'];
foreach ($__proMap as $__aid => $__code) {
    if (in_array($__aid, $__actions, true) && !BillingClient::canUseFeature((string)$memberId, $__code)) {
        echo json_encode([
            'error'   => 'feature_locked',
            'feature' => $__code,
            'message' => 'Это действие доступно только на тарифе PRO.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/*
| С фронта fieldsTable_FIELDS приходит как массив строк таблицы,
| где каждая строка содержит:
|  - value            → поле списка
|  - entField.value   → связанное CRM-поле (может отсутствовать)
|
| На выходе формируем компактную структуру из двух массивов:
|  [0] → CRM-поля
|  [1] → поля списка
| Индексы массивов совпадают и образуют пары
*/

$crmFields  = []; // массив CRM-полей
$listFields = []; // массив полей списка

foreach ($btnSettings['fieldsTable_FIELDS'] as $row) {

    // если CRM-поле выбрано — сохраняем его value
    // если не выбрано — сохраняем строку "null"
    if (
        isset($row['entField']) &&
        is_array($row['entField']) &&
        isset($row['entField']['value'])
    ) {
        $crmFields[] = $row['entField']['value'];
    } else {
        $crmFields[] = "null";
    }

    // поле списка (всегда есть)
    // берём value поля списка
    $listFields[] = $row['value'];
}

// перезаписываем fieldsTable_FIELDS в виде:
// [
//   [CRM_FIELD_1, CRM_FIELD_2, ...],
//   [LIST_FIELD_1, LIST_FIELD_2, ...]
// ]


$btnSettings['fieldsTable_FIELDS'] = [
    $crmFields,
    $listFields
];

//массивы преобразуем в JSON-строки

function encodeAllArrays(array $data): array
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
            );
        }
    }
    return $data;
}

$btnSettings = encodeAllArrays($btnSettings);

// искомый элемент (кнопка)
$searchableButtonID = $requestData['activeButtonId'];

// если пришло id кнопки, то уже есть такая кнопка и надо обновить настройки элемента
if ($searchableButtonID) {
    $updateItem = overCRest::call('entity.item.update', [
        'ENTITY' => 'customButton',
        'ID' => $searchableButtonID,
        'PROPERTY_VALUES' => $btnSettings
    ]);
// иначе создаем новый элемент
} else {
    // проверка лимита кнопок (free-тариф)
    require_once(__DIR__ . '/../subscription/limits.php');
    $limitCheck = checkButtonLimit($memberId);
    if ($limitCheck['exceeded']) {
        echo json_encode([
            'error'   => 'button_limit_reached',
            'message' => 'Достигнут лимит бесплатного тарифа: ' . $limitCheck['limit'] . ' ' . pluralize($limitCheck['limit'], ['кнопка', 'кнопки', 'кнопок']) . '. Обновитесь до PRO, чтобы создавать больше.',
            'limit'   => $limitCheck['limit'],
            'used'    => $limitCheck['used'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $itemAdd = overCRest::call("entity.item.add", [
        "ENTITY" => "customButton",
        'NAME' => $btnSettings['buttonName_FIELDS'],
        'PROPERTY_VALUES' => $btnSettings
    ]);

    // возвращаем созданную кнопку
    echo json_encode([
        'result' => $itemAdd['result'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function pluralize($n, $forms) {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $forms[2];
    if ($n1 > 1 && $n1 < 5) return $forms[1];
    if ($n1 === 1) return $forms[0];
    return $forms[2];
}
