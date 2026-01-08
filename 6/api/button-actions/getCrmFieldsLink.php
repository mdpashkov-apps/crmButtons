<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$entity = $requestData['current_button']['value'];

if (is_numeric($entity) || $entity ==='31') {
    $getEntityFields = overCRest::call('crm.item.fields', [
        'entityTypeId' => (int)$entity
    ]);

    foreach ($getEntityFields['result']['fields'] as $key => $field) {
        if (($field['type'] ?? '') === 'url') {
            $finalResult[] = [
                'value' => $key,
                'name'  => $field['title'],
            ];
        }
    }
} else {
    $getEntityFields = overCRest::call('crm.' . $entity . '.fields', []);
    $finalResult = [];
    foreach ($getEntityFields['result'] as $key => $field) {
        if ($field['type'] === 'url'){
            $finalResult[] = [
                'value' => $key,
                'name'  => $field['formLabel'],
            ];
        }
    }
}

echo json_encode([
    'result' => $finalResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
