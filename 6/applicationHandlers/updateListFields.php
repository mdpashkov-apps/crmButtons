<?php

$entityBody = file_get_contents('php://input');

$requestData = json_decode($entityBody, true);

$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);

$idList = $requestData['idList'];
$entitie = $requestData['entitie'];
$optionsUserFields = [];
$optionsListFields = [];
$entityFields = '';

switch ($entitie) {
    case 'Lead':
        $entityFields = 'crm.lead.fields';
        break;
    case 'Contact':
        $entityFields = 'crm.contact.fields';
        break;
    case 'Company':
        $entityFields = 'crm.company.fields';
        break;
    case 'Deal':
        $entityFields = 'crm.deal.fields';
        break;
        case '31':
            $entityFields = 'crm.item.fields';
            break;
        default:
        $entityFields = 'crm.item.fields';
        break;
}
$entFilter = [];
if ($entityFields == 'crm.item.fields') {
    $entFilter = ['entityTypeId' => $entitie];
}

// Поля сущности
$resultUserFields = overCRest::call($entityFields, $entFilter)['result'];
if ($entityFields == 'crm.item.fields') {
    $resultUserFields = $resultUserFields['fields'];
}
// file_put_contents(__DIR__.'/result.log', var_export( $resultUserFields, 1), FILE_APPEND);
foreach ($resultUserFields as $key => $elem) {
    $listBool = false;
    if($elem["type"] == 'enumeration'){
        $listBool = true;
    }
    if ($elem['listLabel']) {       
        $optionsUserFields[] = ['value' => $key, 'name' => $elem['listLabel'],'list'=>$listBool];
    } else {
        $optionsUserFields[] = ['value' => $key, 'name' => $elem['title'],'list'=>$listBool];
    }

}
// поля списка
$resultListFields = overCRest::call('lists.field.get', [
    'IBLOCK_TYPE_ID' => 'lists',
    'IBLOCK_ID' => $idList,
])['result'];

foreach ($resultListFields as $key => $elem) {
    $listBool = false;
    if($elem['TYPE']== 'L'){
        $listBool = true;
    }
    $optionsListFields[] = [
        'fieldsLists' => ['value' => $key, 'name' => $elem['NAME'], 'isRequired' => $elem['IS_REQUIRED'],'list'=>$listBool],
        'fieldsEntiyValue' => null
    ];

}

$newData = [
    'fields_table' => $optionsListFields,
    'optionsEntity' => $optionsUserFields
];


// Пишем содержимое обратно в файл

echo json_encode(['newData' => $newData, 'test' => $resultListFields], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
