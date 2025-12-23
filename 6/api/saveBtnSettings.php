<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$btnSettings = $requestData['btnSettings'];


$searchableButtonID = $requestData['activeButtonId'];
// $searchableButtonID = 100;

// получаю данные 1 хранилища
$result = overCRest::call("entity.item.get", [
    "ENTITY" => "customButton",
    'FILTER' => [
        'ID' => $searchableButtonID,
    ]
]);
// file_put_contents(__DIR__.'/result91.log', var_export($searchableButtonID, true), FILE_APPEND);


if ($searchableButtonID) {
     $updateSecond = overCRest::call('entity.item.update', [
        'ENTITY' => 'customButton',
        'ID' => $searchableButtonID,
        'PROPERTY_VALUES' => [
            'buttonName_FIELDS' => $btnSettings['buttonName_FIELDS'],
        ]
    ]);




} else {
    $itemAdd = overCRest::call("entity.item.add", [
    "ENTITY" => "customButton",
    'NAME' => $btnSettings['buttonName_FIELDS'],
    'PROPERTY_VALUES' => [
        'buttonName_FIELDS' => $btnSettings['buttonName_FIELDS'],

    ],

]);
 $resultId = $itemAdd['result'];

file_put_contents(__DIR__.'/result91.log', var_export($resultId, true), FILE_APPEND);

echo json_encode([
    'result' => $resultId,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

}

