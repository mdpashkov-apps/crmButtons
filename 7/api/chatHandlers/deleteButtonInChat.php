<?php
// api/chatHandlers/deleteButtonInChat.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$buttonId = $requestData['activeButtonId'];

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// Получаем данные кнопки
$getButton = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['ID' => $buttonId]
]);

if (empty($getButton['result'])) {
    echo json_encode(['error' => 'Кнопка не найдена'], JSON_UNESCAPED_UNICODE);
    exit;
}

$buttonData = $getButton['result'][0]['PROPERTY_VALUES'];

// Находим бота через v2 API
$findBot = overCRest::call("imbot.v2.Bot.list", []);
$botId = null;

if (!isset($findBot['error']) && !empty($findBot['result']['bots'])) {
    foreach ($findBot['result']['bots'] as $bot) {
        if ($bot['code'] == 'OVERPLAN_REPORT_CRMBUTTONS') {
            $botId = $bot['id'];
            break;
        }
    }
}

if (!$botId) {
    $findBotOld = overCRest::call("imbot.bot.list", []);
    if (!isset($findBotOld['error']) && !empty($findBotOld['result'])) {
        foreach ($findBotOld['result'] as $bot) {
            if (($bot['CODE'] ?? null) == 'OVERPLAN_REPORT_CRMBUTTONS') {
                $botId = $bot['ID'];
                break;
            }
        }
    }
}

if ($botId && !empty($buttonData['chatCommandId_FIELDS'])) {
    // Удаляем команду бота через v2 API
    $unregisterResult = overCRest::call('imbot.v2.Command.unregister', [
        'botId' => (int)$botId,
        'commandId' => (int)$buttonData['chatCommandId_FIELDS']
    ]);

    if (isset($unregisterResult['error'])) {
        $unregisterResult = overCRest::call('imbot.command.unregister', [
            'BOT_ID' => (int)$botId,
            'COMMAND_ID' => (int)$buttonData['chatCommandId_FIELDS']
        ]);
    }
    
    file_put_contents(
        __DIR__ . '/chat-command.log',
        "\n\n=== UNREGISTER COMMAND ===\n" . date('Y-m-d H:i:s') . "\n" . var_export($unregisterResult, true),
        FILE_APPEND
    );
}

// Обновляем свойства кнопки
$updateData = [
    'buttonInChat_FIELDS' => '0',
    'chatCommandId_FIELDS' => ''
];

$updateItem = overCRest::call('entity.item.update', [
    'ENTITY' => 'customButton',
    'ID' => (int)$buttonId,
    'PROPERTY_VALUES' => $updateData
]);

echo json_encode([
    'result' => 'Кнопка удалена из чата'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);