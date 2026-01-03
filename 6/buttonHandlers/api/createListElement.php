<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);


$listsValue_FIELDS = json_decode($requestData['crmActions']['listsValue_FIELDS'], true);

$listValue = $listsValue_FIELDS['value'];


$entityId = $requestData['entityData']['ENTITY_DATA']['entityId'];

$entityTypeId = $requestData['entityData']['ENTITY_DATA']['entityTypeId'];
$entityMap = [
    '1' => 'lead',
    '2' => 'deal',
    '3' => 'contact',
    '4' => 'company',
];
$entityType = $entityMap[$entityTypeId] ?? $entityTypeId;



$raw = $requestData['crmActions']['fieldsTable_FIELDS'];

    $fieldsTable_FIELDS = json_decode($raw, true);




$values = $fieldsTable_FIELDS[0];
$keys   = $fieldsTable_FIELDS[1];

$filteredComparison = [];

foreach ($values as $i => $value) {
    if ($value !== null && $value !== 'null' && $value !== '') {
        $filteredComparison[$keys[$i]] = $value;
    }
}





if (is_numeric($entityTypeId)) {
    $getFieldValue = overCRest::call('crm.item.get', [
        'entityTypeId' => $entityTypeId,
        'id' => $entityId,    
    ]);
    $entityFields = $getFieldValue['result']['item'];

} else {
    $getFieldValue = overCRest::call('crm.' . $entityType . '.get', [
        'id' => $entityId
    ]);
    $entityFields = $getFieldValue['result'];

}



// $getFieldValue = overCRest::call('crm.' . $entityType . '.get', [
//         'id' => $entityId
//     ]);

$resultMapped = [];


foreach ($filteredComparison as $propertyCode => $entityFieldCode) {
    if (isset($entityFields[$entityFieldCode])) {
        $resultMapped[$propertyCode] = $entityFields[$entityFieldCode];
    }
}

// file_put_contents(__DIR__.'/result91.log', var_export($resultMapped, true), FILE_APPEND);

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



        $elementAdd = overCRest::call(
    'lists.element.add',
    [
        'IBLOCK_TYPE_ID' => 'lists',
        'IBLOCK_ID' => $listValue,
        'ELEMENT_CODE' => $randomString,
        'FIELDS' => $resultMapped
    ]
);		

