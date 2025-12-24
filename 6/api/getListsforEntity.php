<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$entity = $requestData['current_button']['value'];

// получаю данные 1 хранилища
$getLists = overCRest::call("lists.get", [
    "IBLOCK_TYPE_ID" => "lists",
]);


$finalResult = [];

foreach ($getLists['result'] as $list) {
    $finalResult[] = [
        'value' => $list['ID'],
        'name'  => $list['NAME'],
    ];
}

echo json_encode([
    'result' => $finalResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  


