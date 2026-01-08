<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = $requestData['activeButtonId'];
$domen = $requestData['domen'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);

// Массив для обновления свойств кнопки
$arr = [];
// Получаем текущие данные кнопки
$getButton = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['ID' => $id]
])['result'][0]['PROPERTY_VALUES'];

// Если у кнопки есть созданное пользовательское поле
if ($getButton['customField_FIELDS'] != '') {
    // Удаляем тип пользовательского поля из Bitrix24
    $resultId = overCRest::call('userfieldtype.delete', [
        'USER_TYPE_ID' => $getButton['customField_FIELDS']
    ]);
    // Удаляем PHP-файл обработчика пользовательского поля и очищаем поле с идентификатором пользовательского поля
    unlink('../../../fieldTypeHandlers/' . $domen . '_button|' . $id . '|.php');
    $arr['customField_FIELDS'] = '';
}

// Отмечаем, что кнопка больше не добавлена в CRM и обновляем запись кнопки
$arr['buttonInCRM_FIELDS'] = 'false';
$updateItem = overCRest::call('entity.item.update', [
    'ENTITY' => 'customButton',
    'ID' => $id,
    'PROPERTY_VALUES' => $arr
])['result'];
