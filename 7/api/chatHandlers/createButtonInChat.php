<?php
// api/chatHandlers/createButtonInChat.php

$logFile = __DIR__ . '/create-button-debug.log';

function writeLog($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}";
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $logEntry .= "\n" . print_r($data, true);
        } else {
            $logEntry .= " - {$data}";
        }
    }
    $logEntry .= "\n" . str_repeat('-', 80) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

writeLog('=== СОЗДАНИЕ КНОПКИ В ЧАТЕ ===');

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$buttonId = $requestData['activeButtonId'];
$buttonSettings = $requestData['btnSettings'] ?? [];

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');

overCRest::setCurrentBitrix24($memberId);

// гейтинг PRO: «Кнопка в чатах» доступна только при активной подписке
require_once($path . '/api/billing/BillingClient.php');
if (!BillingClient::canUseFeature((string)$memberId, 'chat_button')) {
    echo json_encode(['error' => 'feature_locked', 'feature' => 'chat_button', 'message' => 'Кнопки в чате доступны только на тарифе PRO.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once(__DIR__ . '/ensure-bot.php');

// Получаем данные кнопки из БД
$getButton = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['ID' => $buttonId]
]);

if (empty($getButton['result'])) {
    writeLog("ERROR: Button not found: {$buttonId}");
    echo json_encode(['error' => 'Кнопка не найдена']);
    exit;
}

$buttonData = $getButton['result'][0]['PROPERTY_VALUES'];

// Обновляем данные из настроек (если они пришли с фронта)
if (!empty($buttonSettings['buttonColor_FIELDS'])) {
    $buttonData['buttonColor_FIELDS'] = $buttonSettings['buttonColor_FIELDS'];
}
if (!empty($buttonSettings['textColor_FIELDS'])) {
    $buttonData['textColor_FIELDS'] = $buttonSettings['textColor_FIELDS'];
}
if (!empty($buttonSettings['textOnTheButton_FIELDS'])) {
    $buttonData['textOnTheButton_FIELDS'] = $buttonSettings['textOnTheButton_FIELDS'];
}
if (!empty($buttonSettings['link_FIELDS'])) {
    $buttonData['link_FIELDS'] = $buttonSettings['link_FIELDS'];
}

// Получаем настройки бота из портальных настроек
$settingsCheck = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true']
]);

$botId = null;
$botToken = null;
$chatId = null;

if (!empty($settingsCheck['result'])) {
    $portalSettings = $settingsCheck['result'][0]['PROPERTY_VALUES'];
    $botId = $portalSettings['botId_FIELDS'] ?? null;
    $botToken = $portalSettings['botToken_FIELDS'] ?? null;
    $chatId = $portalSettings['chatId_FIELDS'] ?? null;
}

// Лениво создаём бота+чат, если их ещё нет (раньше это делала страница "Настройка уведомлений")
if (!$botId) {
    writeLog("Бот не найден в настройках — создаём лениво через ensureBotAndChat");
    $ensured = ensureBotAndChat($memberId);
    $botId    = $ensured['botId'] ?? null;
    $botToken = $ensured['botToken'] ?? null;
    $chatId   = $ensured['chatId'] ?? null;
}
if (!$botId) {
    writeLog("ERROR: Не удалось создать бота");
    echo json_encode(['error' => 'Не удалось создать чат-бота. Повторите попытку.']);
    exit;
}

writeLog("Bot ID: {$botId}, Chat ID: {$chatId}");

// Получаем текст кнопки и ссылку
$buttonText = trim($buttonData['textOnTheButton_FIELDS'] ?? $buttonData['buttonName_FIELDS'] ?? 'Кнопка');
$link = trim($buttonData['link_FIELDS'] ?? '');
$buttonActionType = $buttonData['buttonActionType_FIELDS'] ?? 'url';
// гейтинг PRO: режим «Запустить БП из ленты» доступен только при активной подписке
if ($buttonActionType === 'workflow' && !BillingClient::canUseFeature((string)$memberId, 'bp_from_feed')) {
    echo json_encode(['error' => 'feature_locked', 'feature' => 'bp_from_feed', 'message' => 'Запуск БП из ленты доступен только на тарифе PRO.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$workflowTemplateId = $buttonData['workflowTemplateId_FIELDS'] ?? null;
$workflowDocumentId = $buttonData['workflowDocumentId_FIELDS'] ?? null;

// Сохраняем ссылку в БД, если она новая
if (!empty($buttonSettings['link_FIELDS']) && empty($buttonData['link_FIELDS'])) {
    $updateLink = overCRest::call('entity.item.update', [
        'ENTITY' => 'customButton',
        'ID' => (int)$buttonId,
        'PROPERTY_VALUES' => ['link_FIELDS' => $buttonSettings['link_FIELDS']]
    ]);
    writeLog("Saved link to DB: {$buttonSettings['link_FIELDS']}", $updateLink);
    $link = $buttonSettings['link_FIELDS'];
}

// Удаляем старую команду, если есть
if (!empty($buttonData['chatCommandId_FIELDS'])) {
    $unregisterResult = overCRest::call('imbot.v2.Command.unregister', [
        'botId' => (int)$botId,
        'commandId' => (int)$buttonData['chatCommandId_FIELDS']
    ]);
    writeLog("Deleted old command: {$buttonData['chatCommandId_FIELDS']}", $unregisterResult);
}

// Регистрируем новую команду
$commandName = 'overplan_button_' . $buttonId;

$registerCommand = overCRest::call('imbot.v2.Command.register', [
    'botId' => (int)$botId,
    'botToken' => $botToken,
    'command' => $commandName,
    'common' => 'Y',
    'hidden' => 'Y',
    'extranetSupport' => 'Y'
]);

writeLog("Command registration result:", $registerCommand);

if (isset($registerCommand['error'])) {
    $registerCommand = overCRest::call('imbot.command.register', [
        'BOT_ID' => (int)$botId,
        'COMMAND' => $commandName,
        'COMMON' => 'Y',
        'EVENT_COMMAND_ADD' => 'https://app.overplan.ru/applications/crmButtons/7/api/chatHandlers/chat-command-handler.php',
        'LANG' => [
            [
                'LANGUAGE_ID' => 'ru',
                'TITLE' => $buttonText,
                'PARAMS' => ''
            ]
        ]
    ]);
    writeLog("Fallback command registration result:", $registerCommand);
}

$commandId = null;
if (!isset($registerCommand['error'])) {
    if (isset($registerCommand['result']['command']['id'])) {
        $commandId = $registerCommand['result']['command']['id'];
    } elseif (isset($registerCommand['result']['id'])) {
        $commandId = $registerCommand['result']['id'];
    } elseif (isset($registerCommand['result'])) {
        $commandId = $registerCommand['result'];
    }
}

if (!$commandId) {
    writeLog("ERROR: Failed to register command");
    echo json_encode(['error' => 'Не удалось зарегистрировать команду']);
    exit;
}

writeLog("Command registered with ID: {$commandId}");

// Получаем цвета кнопки
$buttonColor = $buttonData['buttonColor_FIELDS'] ?: '#2fc6f6';
$textColor = $buttonData['textColor_FIELDS'] ?: '#ffffff';

// Формируем сообщение о создании кнопки (для чата)
if ($buttonActionType === 'workflow') {
    $creationMessage = "🤖 *Доступен новый бизнес-процесс!*\n\n";
    $creationMessage .= "📌 *Название:* {$buttonText}\n";
    if ($workflowTemplateId) {
        $creationMessage .= "🔄 *Тип:* Запуск БП из ленты\n\n";
    }
    $creationMessage .= "👇 Нажмите на кнопку ниже, чтобы запустить бизнес-процесс.";
} else {
    $creationMessage = "🤖 *Доступна новая команда!*\n\n";
    $creationMessage .= "📌 *Название:* {$buttonText}\n";
    if (!empty($link)) {
        $creationMessage .= "🔗 *Ссылка:* {$link}\n\n";
    }
    $creationMessage .= "👇 Нажмите на кнопку ниже, чтобы перейти по ссылке.";
}

// Отправляем сообщение с кнопкой в чат
$keyboard = [
    'BUTTONS' => [
        [
            'TEXT' => $buttonText,
            'COMMAND' => $commandName,
            'BG_COLOR' => $buttonColor,
            'TEXT_COLOR' => $textColor,
        ]
    ]
];

$messageResult = overCRest::call('imbot.v2.Chat.Message.send', [
    'botId' => (int)$botId,
    'botToken' => $botToken,
    'dialogId' => 'chat' . $chatId,
    'fields' => [
        'message' => $creationMessage,
        'keyboard' => $keyboard
    ]
]);

writeLog("Message send result:", $messageResult);

if (isset($messageResult['error'])) {
    $messageResult = overCRest::call('imbot.message.add', [
        'BOT_ID' => (int)$botId,
        'DIALOG_ID' => 'chat' . $chatId,
        'MESSAGE' => $creationMessage,
        'KEYBOARD' => $keyboard
    ]);
    writeLog("Fallback message send result:", $messageResult);
}

// Обновляем данные кнопки в БД
$updateResult = overCRest::call('entity.item.update', [
    'ENTITY' => 'customButton',
    'ID' => (int)$buttonId,
    'PROPERTY_VALUES' => [
        'buttonInChat_FIELDS' => '1',
        'chatCommandId_FIELDS' => $commandId,
        'link_FIELDS' => $link,
        'buttonColor_FIELDS' => $buttonColor,
        'textColor_FIELDS' => $textColor,
        'textOnTheButton_FIELDS' => $buttonText,
        'buttonActionType_FIELDS' => $buttonActionType,
        'workflowTemplateId_FIELDS' => $workflowTemplateId,
        'workflowDocumentId_FIELDS' => $workflowDocumentId,
        'workflowFromFeed_FIELDS' => $buttonActionType === 'workflow' ? '1' : '0'
    ]
]);

writeLog("Button update result:", $updateResult);
writeLog('=== ГОТОВО ===');

echo json_encode(['success' => true, 'commandId' => $commandId]);