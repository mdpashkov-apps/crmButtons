<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$btnSettings = $requestData['btnSettings'];

// 🔥 если entitySelection_FIELDS — массив → кодируем в JSON
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

file_put_contents(__DIR__.'/result91.log', var_export($btnSettings, true), FILE_APPEND);

$searchableButtonID = $requestData['activeButtonId'];

// получаю данные 1 хранилища
$result = overCRest::call("entity.item.get", [
    "ENTITY" => "customButton",
    'FILTER' => [
        'ID' => $searchableButtonID,
    ]
]);


if ($searchableButtonID) {
     $updateSecond = overCRest::call('entity.item.update', [
        'ENTITY' => 'customButton',
        'ID' => $searchableButtonID,
        'PROPERTY_VALUES' => $btnSettings

    ]);




} else {
    $itemAdd = overCRest::call("entity.item.add", [
    "ENTITY" => "customButton",
    'NAME' => $btnSettings['buttonName_FIELDS'],
   'PROPERTY_VALUES' => $btnSettings


]);
 $resultId = $itemAdd['result'];


echo json_encode([
    'result' => $resultId,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

}

