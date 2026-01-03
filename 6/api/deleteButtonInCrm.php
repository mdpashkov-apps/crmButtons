<?



$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = $requestData['activeButtonId'];
$domen = $requestData['domen'];
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);

// file_put_contents(__DIR__.'/result91.log', var_export($requestData, true), FILE_APPEND);


$arr = [];
$result_entity = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['ID' => $id]
])['result'][0]['PROPERTY_VALUES'];
if ($result_entity['customField_FIELDS'] != '') {
    $resultId = overCRest::call('userfieldtype.delete', [
        'USER_TYPE_ID' => $result_entity['customField_FIELDS']
    ]);
    unlink('../../fieldTypeHandlers/' . $domen . '_button|' . $id . '|.php');
    $arr['customField_FIELDS'] = '';
}
$arr['buttonInCRM_FIELDS'] = 'false';
$result = overCRest::call('entity.item.update', [
    'ENTITY' => 'customButton',
    'ID' => $id,
    'PROPERTY_VALUES' => $arr
])['result'];


