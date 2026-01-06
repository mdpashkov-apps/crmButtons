<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаем id списка, элемент которого должна создать кнопка
$listsValue_FIELDS = json_decode($requestData['crmActions']['listsValue_FIELDS'], true);
$listValue = $listsValue_FIELDS['value'];

// получаем id сущности и id типа сущности
$entityId = $requestData['entityData']['ENTITY_DATA']['entityId'];

$entityTypeId = $requestData['entityData']['ENTITY_DATA']['entityTypeId'];
$entityMap = [
    '1' => 'lead',
    '2' => 'deal',
    '3' => 'contact',
    '4' => 'company',
];
$entityType = $entityMap[$entityTypeId] ?? $entityTypeId;


// получаем json с сопоставлением полей списка и скщности из таблицы
$raw = $requestData['crmActions']['fieldsTable_FIELDS'];
$fieldsTable_FIELDS = json_decode($raw, true);

// т.к. в fieldsTable_FIELDS два массива, где второй это ключи полей списка, а первый ключи полей сущности разделим их отдельно
$values = $fieldsTable_FIELDS[0];
$keys   = $fieldsTable_FIELDS[1];

// отфильтруем их сопоставив если в значении не null
$filteredComparison = [];
foreach ($values as $i => $value) {
    if ($value !== null && $value !== 'null' && $value !== '') {
        $filteredComparison[$keys[$i]] = $value;
    }
}

// в зависимости от типа сущности получаем значения полей нужной сущности
if (is_numeric($entityType)) {
    $getFieldValue = overCRest::call('crm.item.get', [
        'entityTypeId' => $entityType,
        'id' => $entityId,    
    ]);
    $entityFields = $getFieldValue['result']['item'];
} else {
    $getFieldValue = overCRest::call('crm.' . $entityType . '.get', [
        'id' => $entityId
    ]);
    $entityFields = $getFieldValue['result'];
}

// далее мапим значения получая реальные значения нужных crm полей
$resultMapped = [];
foreach ($filteredComparison as $propertyCode => $entityFieldCode) {
    if (isset($entityFields[$entityFieldCode])) {
        $resultMapped[$propertyCode] = $entityFields[$entityFieldCode];
    }
}

// ф-я генеарции рандомной строки для ELEMENT_CODE
function generateRandomString($length = 14) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}
$randomString = generateRandomString();

// создаем элемент списка
$elementAdd = overCRest::call('lists.element.add', [
    'IBLOCK_TYPE_ID' => 'lists',
    'IBLOCK_ID' => $listValue,
    'ELEMENT_CODE' => $randomString,
    'FIELDS' => $resultMapped
]);		
