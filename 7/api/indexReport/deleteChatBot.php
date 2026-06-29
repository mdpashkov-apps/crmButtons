<?php
// api/indexReport/deleteChatBot.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$checkOnly = $requestData['checkOnly'] ?? false;

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

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

// Если только проверка - возвращаем статус
if ($checkOnly) {
    echo json_encode([
        'success' => true,
        'botId' => $botId,
        'exists' => !is_null($botId)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$botId) {
    echo json_encode(['error' => 'Бот не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Удаляем все команды бота через v2 API
$commands = overCRest::call("imbot.v2.Command.list", ['botId' => (int)$botId]);
if (!isset($commands['error']) && !empty($commands['result']['commands'])) {
    foreach ($commands['result']['commands'] as $command) {
        overCRest::call("imbot.v2.Command.unregister", [
            'botId' => (int)$botId,
            'commandId' => $command['id']
        ]);
    }
}

// Удаляем бота через v2 API
$deleteBot = overCRest::call('imbot.v2.Bot.unregister', [
    'botId' => (int)$botId
]);

// Очищаем настройки в сущности
$settingsCheck = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true']
]);

if (!empty($settingsCheck['result'])) {
    $settingsId = $settingsCheck['result'][0]['ID'];
    overCRest::call('entity.item.update', [
        'ENTITY' => 'customButton',
        'ID' => (int)$settingsId,
        'PROPERTY_VALUES' => [
            'botToken_FIELDS' => '',
            'botId_FIELDS' => '',
            'chatId_FIELDS' => '',
            'botRegistered' => '0'
        ]
    ]);
}

// Очищаем команды у всех кнопок
$allButtons = overCRest::call('entity.item.get', ['ENTITY' => 'customButton']);
if (!isset($allButtons['error']) && !empty($allButtons['result'])) {
    foreach ($allButtons['result'] as $button) {
        if (!empty($button['PROPERTY_VALUES']['chatCommandId_FIELDS'])) {
            overCRest::call('entity.item.update', [
                'ENTITY' => 'customButton',
                'ID' => (int)$button['ID'],
                'PROPERTY_VALUES' => [
                    'buttonInChat_FIELDS' => '0',
                    'chatCommandId_FIELDS' => ''
                ]
            ]);
        }
    }
}

echo json_encode([
    'result' => $deleteBot,
    'success' => empty($deleteBot['error']),
    'botId' => $botId,
    'commandsCleaned' => true
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);