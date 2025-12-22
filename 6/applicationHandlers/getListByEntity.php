<?php

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
include_once($path . '/overCRest.php');

$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);

$result = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton'
])['result'];
$elem = null;
foreach ($result as $item) {
    if ($item['ACTIVE'] === 'Y') {
        $elem = $item;
    }
}
if ($elem['PROPERTY_VALUES']['entitySelection_FIELDS']) {
    // Список
    $resultLists = overCRest::call('lists.get', [
        'IBLOCK_TYPE_ID' => 'lists',
    ])['result'];
    $valuelists = [];
    $optionsLists = [];
    foreach ($resultLists as $key => $element) {
        $optionsLists[] = ['value' => $element['ID'], 'name' => $element['NAME']];
        if ($elem['PROPERTY_VALUES']['listsValue_FIELDS'] == $element['ID']) { // поменял тут
            $valuelists[] = ['value' => $element['ID'], 'name' => $element['NAME']];
            $idLists = $element['ID'];
        }
    }
    $response = [
        "options" => $optionsLists,
        "value" => $valuelists
    ];
} else {
    $response = [
        "options" => [],
        "value" => []
    ];
}
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
