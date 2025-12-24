<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$entity = $requestData['entity']['value'];
$list = $requestData['list']['value'];

$getListFields = overCRest::call("lists.field.get", [
    "IBLOCK_TYPE_ID" => "lists",
    'IBLOCK_ID' => $list
]);




$finalResult = [];

foreach ($getListFields['result'] as $field) {
    $finalResult[] = [
        'value' => $field['FIELD_ID'],
        'name'  => $field['NAME'],
    ];
}




echo json_encode([
    'result' => $finalResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


// file_put_contents(__DIR__.'/result91.log', var_export($finalResult, true), FILE_APPEND);
