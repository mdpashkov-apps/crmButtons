<?php
// api/button-crud/deleteButton.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаем id кнопки которую надо удалить из хранилища
$searchableButtonID = $requestData['activeButtonId'];

// Сначала получаем данные кнопки, чтобы узнать ID пользовательского поля и команды чата
$getButton = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['ID' => $searchableButtonID]
]);

$buttonData = $getButton['result'][0]['PROPERTY_VALUES'] ?? [];

// Если есть пользовательское поле - удаляем его
if (!empty($buttonData['customField_FIELDS'])) {
    // Удаляем тип пользовательского поля из Bitrix24
    overCRest::call('userfieldtype.delete', [
        'USER_TYPE_ID' => $buttonData['customField_FIELDS']
    ]);
    
    // Удаляем PHP-файл обработчика
    $dir = dirname(__DIR__, 2) . '/fieldTypeHandlers';
    $pattern = $buttonData['customField_FIELDS'] . '*';
    $files = glob($dir . '/' . $pattern);
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// Если есть команда в чате - удаляем её
if (!empty($buttonData['chatCommandId_FIELDS']) && !empty($buttonData['buttonInChat_FIELDS']) && $buttonData['buttonInChat_FIELDS'] === 'true') {
    $findBot = overCRest::call("imbot.bot.list", []);
    $botId = null;
    
    foreach ($findBot['result'] as $bot) {
        if ($bot["CODE"] == 'OVERPLAN_REPORT_CRMBUTTONS') {
            $botId = $bot["ID"];
            break;
        }
    }
    
    if ($botId) {
        overCRest::call('imbot.command.unregister', [
            'BOT_ID' => $botId,
            'COMMAND_ID' => $buttonData['chatCommandId_FIELDS']
        ]);
    }
}

// Теперь удаляем саму кнопку из хранилища
$deleteItem = overCRest::call('entity.item.delete', [
    'ENTITY' => 'customButton',
    'ID' => $searchableButtonID,
]);

echo json_encode([
    'result' => $deleteItem,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>