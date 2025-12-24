<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$entity = $requestData['current_button']['value'];

$getEntityFields = overCRest::call('crm.' . $entity . '.fields', []);


$finalResult = [];

foreach ($getEntityFields['result'] as $key => $field) {
    $finalResult[] = [
        'value' => $key,
        'name'  => !empty($field['formLabel'])
            ? $field['formLabel']
            : $field['title'],
    ];
}



echo json_encode([
    'result' => $finalResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// file_put_contents(__DIR__.'/result91.log', var_export($finalResult, true), FILE_APPEND);
