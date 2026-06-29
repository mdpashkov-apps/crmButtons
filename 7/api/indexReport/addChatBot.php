<?php
// api/indexReport/addChatBot.php

$logFile = __DIR__ . '/add-bot-debug.log';

function writeLog($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $logEntry .= "\n" . print_r($data, true);
    }
    $logEntry .= "\n" . str_repeat('-', 80) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

writeLog('=== НАЧАЛО ДОБАВЛЕНИЯ ЧАТ-БОТА ===');

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');

overCRest::setCurrentBitrix24($memberId);

// ============================================================
// 1. ПОЛУЧАЕМ ИЛИ СОЗДАЁМ ЧАТ
// ============================================================

$findChat = overCRest::call("im.search.chat.list", ["FIND" => "ALLChat Overplan"]);
$chatId = null;

if (empty($findChat['result'])) {
    $chatAdd = overCRest::call("im.chat.add", [
        "TYPE" => "CHAT",
        "TITLE" => "ALLChat Overplan",
        "USERS" => [1]
    ]);
    $chatId = $chatAdd['result'];
    writeLog("Чат создан, ID: {$chatId}");
} else {
    $chatId = $findChat['result'][0]['id'];
    writeLog("Чат найден, ID: {$chatId}");
}

// ============================================================
// 2. ПОЛУЧАЕМ ИЛИ СОЗДАЁМ ПОРТАЛЬНЫЕ НАСТРОЙКИ
// ============================================================

$settingsCheck = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true']
]);

$portalSettingsId = null;
$portalSettings = [];
if (!empty($settingsCheck['result'])) {
    $portalSettingsId = $settingsCheck['result'][0]['ID'];
    $portalSettings = $settingsCheck['result'][0]['PROPERTY_VALUES'] ?? [];
    writeLog("Портальные настройки найдены, ID: {$portalSettingsId}");
} else {
    writeLog("Портальные настройки не найдены, нужно создать");
}

// ============================================================
// 3. ИЩЕМ СУЩЕСТВУЮЩЕГО БОТА
// ============================================================

$existingBotId = null;

// Способ 1: через imbot.v2.Bot.list
$findBotV2 = overCRest::call("imbot.v2.Bot.list", []);
writeLog("Поиск бота через v2:", $findBotV2);

if (!isset($findBotV2['error']) && !empty($findBotV2['result']['bots'])) {
    foreach ($findBotV2['result']['bots'] as $bot) {
        if (isset($bot['code']) && $bot['code'] == 'OVERPLAN_REPORT_CRMBUTTONS') {
            $existingBotId = $bot['id'];
            writeLog("Найден бот через v2, ID: {$existingBotId}");
            break;
        }
    }
}

// Способ 2: через imbot.bot.list (старый метод)
if (!$existingBotId) {
    $findBotOld = overCRest::call("imbot.bot.list", []);
    writeLog("Поиск бота через старый метод:", $findBotOld);
    
    if (!isset($findBotOld['error']) && !empty($findBotOld['result'])) {
        foreach ($findBotOld['result'] as $bot) {
            if ($bot['CODE'] == 'OVERPLAN_REPORT_CRMBUTTONS') {
                $existingBotId = $bot['ID'];
                writeLog("Найден бот через старый метод, ID: {$existingBotId}");
                break;
            }
        }
    }
}

// ============================================================
// 4. ЕСЛИ БОТ СУЩЕСТВУЕТ - ОБНОВЛЯЕМ НАСТРОЙКИ И ВЫХОДИМ
// ============================================================

if ($existingBotId) {
    writeLog("Бот уже существует, ID: {$existingBotId}");
    
    // Добавляем бота в чат, если его там нет
    $chatUsers = overCRest::call("im.chat.user.get", ["CHAT_ID" => $chatId]);
    $botInChat = false;
    
    if (!empty($chatUsers['result'])) {
        foreach ($chatUsers['result'] as $user) {
            if ($user['id'] == $existingBotId) {
                $botInChat = true;
                break;
            }
        }
    }
    
    if (!$botInChat) {
        overCRest::call("im.chat.user.add", [
            "CHAT_ID" => $chatId,
            "USERS" => [(int)$existingBotId]
        ]);
        writeLog("Бот добавлен в чат");
    }
    
    // Обновляем или создаём портальные настройки
    $settingsData = [
        'botId_FIELDS' => $existingBotId,
        'chatId_FIELDS' => $chatId,
        'botRegistered' => '1',
        'isPortalSettings' => 'true',
        'buttonName_FIELDS' => 'PORTAL_SETTINGS'
    ];
    if (!empty($portalSettings['botToken_FIELDS'])) {
        $settingsData['botToken_FIELDS'] = $portalSettings['botToken_FIELDS'];
    }
    
    if ($portalSettingsId) {
        $updateResult = overCRest::call('entity.item.update', [
            'ENTITY' => 'customButton',
            'ID' => (int)$portalSettingsId,
            'PROPERTY_VALUES' => $settingsData
        ]);
        writeLog("Обновлены портальные настройки:", $updateResult);
    } else {
        $addResult = overCRest::call('entity.item.add', [
            'ENTITY' => 'customButton',
            'NAME' => 'PORTAL_SETTINGS',
            'PROPERTY_VALUES' => $settingsData
        ]);
        writeLog("Созданы портальные настройки:", $addResult);
    }
    
    echo json_encode([
        'success' => true, 
        'botId' => $existingBotId, 
        'chatId' => $chatId, 
        'message' => 'Бот уже существует'
    ]);
    exit;
}

// ============================================================
// 5. СОЗДАЁМ НОВОГО БОТА
// ============================================================

writeLog("Создаём нового бота...");

// Генерируем токен
$botToken = hash('sha256', $memberId . 'OVERPLAN_SECRET_SALT_' . time() . rand(1000, 9999));
writeLog("Сгенерирован токен: {$botToken}");

// Пробуем зарегистрировать через v2 API
$botRegV2 = overCRest::call('imbot.v2.Bot.register', [
    'botToken' => $botToken,
    'fields' => [
        'code' => 'OVERPLAN_REPORT_CRMBUTTONS',
        'type' => 'bot',
        'eventMode' => 'webhook',
        'webhookUrl' => 'https://app.overplan.ru/applications/crmButtons/7/api/chatHandlers/chat-command-handler.php',
        'properties' => [
            'name' => 'Overplan Report',
            'workPosition' => 'Уведомления и команды',
            'color' => 'AQUA',
            'email' => 'hello@overplan.ru',
            'website' => 'overplan.ru'
        ]
    ]
]);

writeLog("Результат регистрации бота (v2):", $botRegV2);

$botId = null;

if (!isset($botRegV2['error'])) {
    if (isset($botRegV2['result']['bot']['id'])) {
        $botId = $botRegV2['result']['bot']['id'];
    } elseif (isset($botRegV2['result']['botId'])) {
        $botId = $botRegV2['result']['botId'];
    } elseif (isset($botRegV2['result'])) {
        $botId = $botRegV2['result'];
    }
}

// Если v2 не сработал, пробуем старый метод
if (!$botId) {
    writeLog("v2 не сработал, пробуем старый метод...");
    
    $botRegOld = overCRest::call('imbot.register', [
        'CODE' => 'OVERPLAN_REPORT_CRMBUTTONS',
        'TYPE' => 'BOT',
        'EVENT_MESSAGE_ADD' => 'https://app.overplan.ru/applications/crmButtons/7/api/chatHandlers/chat-command-handler.php',
        'EVENT_WELCOME_MESSAGE' => 'https://app.overplan.ru/applications/crmButtons/7/api/chatHandlers/chat-command-handler.php',
        'PROPERTIES' => [
            'NAME' => 'Overplan Report',
            'WORK_POSITION' => 'Уведомления и команды',
            'COLOR' => 'AQUA',
            'EMAIL' => 'hello@overplan.ru',
            'PERSONAL_WWW' => 'overplan.ru'
        ]
    ]);
    
    writeLog("Результат регистрации бота (старый метод):", $botRegOld);
    
    if (!isset($botRegOld['error']) && isset($botRegOld['result'])) {
        $botId = $botRegOld['result'];
    }
}

if ($botId) {
    writeLog("Бот успешно зарегистрирован, ID: {$botId}");
    
    // Добавляем бота в чат
    $addToChat = overCRest::call("im.chat.user.add", [
        "CHAT_ID" => $chatId,
        "USERS" => [(int)$botId]
    ]);
    writeLog("Добавление в чат:", $addToChat);
    
    // Отправляем приветственное сообщение
    overCRest::call('imbot.message.add', [
        'BOT_ID' => (int)$botId,
        'DIALOG_ID' => 'chat' . $chatId,
        'MESSAGE' => "🤖 Бот Overplan успешно активирован!\n\nТеперь вы можете создавать кнопки в чате через раздел 'Настройка приложения'."
    ]);
    
    // Сохраняем настройки бота в портальные настройки
    $settingsData = [
        'botToken_FIELDS' => $botToken,
        'botId_FIELDS' => $botId,
        'chatId_FIELDS' => $chatId,
        'botRegistered' => '1',
        'isPortalSettings' => 'true',
        'buttonName_FIELDS' => 'PORTAL_SETTINGS'
    ];
    
    if ($portalSettingsId) {
        $updateResult = overCRest::call('entity.item.update', [
            'ENTITY' => 'customButton',
            'ID' => (int)$portalSettingsId,
            'PROPERTY_VALUES' => $settingsData
        ]);
        writeLog("Обновлены портальные настройки:", $updateResult);
    } else {
        $addResult = overCRest::call('entity.item.add', [
            'ENTITY' => 'customButton',
            'NAME' => 'PORTAL_SETTINGS',
            'PROPERTY_VALUES' => $settingsData
        ]);
        writeLog("Созданы портальные настройки:", $addResult);
    }
    
    writeLog("=== БОТ УСПЕШНО ЗАРЕГИСТРИРОВАН ===");
    echo json_encode(['success' => true, 'botId' => $botId, 'chatId' => $chatId]);
    
} else {
    writeLog("=== НЕ УДАЛОСЬ ЗАРЕГИСТРИРОВАТЬ БОТА ===");
    echo json_encode([
        'success' => false, 
        'error' => 'Не удалось зарегистрировать бота',
        'debug' => ['v2' => $botRegV2 ?? null, 'old' => $botRegOld ?? null]
    ]);
}