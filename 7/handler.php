<?php
// handler.php - Обработчик событий бота

// Логируем ВСЕ входящие запросы для отладки
$logData = "\n\n=== " . date('Y-m-d H:i:s') . " ===\n" . 
    "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n" .
    "REQUEST:\n" . print_r($_REQUEST, true) . "\n" .
    "INPUT:\n" . file_get_contents('php://input');

file_put_contents(__DIR__ . '/handler_debug.log', $logData, FILE_APPEND);

// Для прямого доступа через браузер - показываем статус
if ($_SERVER['REQUEST_METHOD'] === 'GET' || (empty($_REQUEST) && empty(file_get_contents('php://input')))) {
    http_response_code(200);
    echo "✅ Handler is working. Bot is ready.";
    exit;
}

$memberId = $_REQUEST['auth']['member_id'] ?? null;
if (!$memberId) {
    // Проверяем, может быть member_id в другом месте
    $input = json_decode(file_get_contents('php://input'), true);
    $memberId = $input['auth']['member_id'] ?? $input['memberId'] ?? null;
    
    if (!$memberId) {
        file_put_contents(__DIR__ . '/handler_errors.log', date('Y-m-d H:i:s') . " - No member_id\n", FILE_APPEND);
        http_response_code(200); // Всегда возвращаем 200, чтобы Bitrix24 не повторял запросы
        echo "OK";
        exit;
    }
}

// Подключаем overCRest
require_once(__DIR__ . '/overCRest.php');

try {
    overCRest::setCurrentBitrix24($memberId);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/handler_errors.log', date('Y-m-d H:i:s') . " - CRest error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(200);
    echo "OK";
    exit;
}

// Получаем данные события из разных возможных источников
$event = $_REQUEST['event'] ?? $_REQUEST['EVENT'] ?? '';
$data = $_REQUEST['data'] ?? [];

// Если данные пришли в виде JSON строки
if (empty($data)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $event = $input['event'] ?? $input['EVENT'] ?? '';
    $data = $input['data'] ?? [];
}

// Обработка разных типов событий
$handled = false;

// Событие - новое сообщение
if ($event === 'ONIMBOTMESSAGEADD' || $event === 'ONIMBotMessageAdd' || isset($data['PARAMS']['MESSAGE'])) {
    $dialogId = $data['PARAMS']['DIALOG_ID'] ?? $data['dialogId'] ?? null;
    $botId = $data['PARAMS']['BOT_ID'] ?? $data['botId'] ?? null;
    $message = $data['PARAMS']['MESSAGE'] ?? $data['message'] ?? '';
    $command = $data['PARAMS']['COMMAND'] ?? $data['command'] ?? '';
    
    file_put_contents(__DIR__ . '/handler_result.log', 
        date('Y-m-d H:i:s') . " - Получено сообщение\n" .
        "DialogId: {$dialogId}\nBotId: {$botId}\nMessage: {$message}\nCommand: {$command}\n",
        FILE_APPEND
    );
    
    if ($dialogId && $botId) {
        // Обработка команды
        if (!empty($command) && strpos($command, 'overplan_button_') === 0) {
            $buttonId = str_replace('overplan_button_', '', $command);
            
            // Получаем данные кнопки
            $getButton = overCRest::call('entity.item.get', [
                'ENTITY' => 'customButton',
                'FILTER' => ['ID' => $buttonId]
            ]);
            
            if (!empty($getButton['result'])) {
                $buttonData = $getButton['result'][0]['PROPERTY_VALUES'];
                $actions = json_decode($buttonData['buttonActionsId_FIELDS'] ?? '[]', true);
                
                if (in_array(3, $actions) && !empty($buttonData['link_FIELDS'])) {
                    $link = $buttonData['link_FIELDS'];
                    if (!preg_match('/^https?:\/\//', $link)) {
                        $link = 'https://' . $link;
                    }
                    
                    // Получаем токен бота
                    $settingsCheck = overCRest::call('entity.item.get', [
                        'ENTITY' => 'customButton',
                        'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true']
                    ]);
                    
                    $botToken = null;
                    if (!empty($settingsCheck['result'])) {
                        $botToken = $settingsCheck['result'][0]['PROPERTY_VALUES']['botToken_FIELDS'] ?? null;
                    }
                    
                    // Отправляем ссылку
                    overCRest::call('imbot.v2.Chat.Message.send', [
                        'botId' => $botId,
                        'botToken' => $botToken,
                        'dialogId' => $dialogId,
                        'fields' => ['message' => "🔗 Ссылка: {$link}"]
                    ]);
                } else {
                    overCRest::call('imbot.message.add', [
                        'BOT_ID' => $botId,
                        'DIALOG_ID' => $dialogId,
                        'MESSAGE' => "❌ Действие для этой кнопки не настроено"
                    ]);
                }
            }
        } else {
            // Обычное сообщение - отвечаем приветствием
            $settingsCheck = overCRest::call('entity.item.get', [
                'ENTITY' => 'customButton',
                'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true']
            ]);
            
            $botToken = null;
            if (!empty($settingsCheck['result'])) {
                $botToken = $settingsCheck['result'][0]['PROPERTY_VALUES']['botToken_FIELDS'] ?? null;
            }
            
            $welcomeMessage = overCRest::call('imbot.v2.Chat.Message.send', [
                'botId' => $botId,
                'botToken' => $botToken,
                'dialogId' => $dialogId,
                'fields' => [
                    'message' => "👋 Привет!\nЯ бот Overplan.\nГотов к работе.\n\nВы написали: " . $message
                ]
            ]);
            
            file_put_contents(__DIR__ . '/handler_result.log', 
                date('Y-m-d H:i:s') . " - Ответ отправлен\n",
                FILE_APPEND
            );
        }
        $handled = true;
    }
}

// Событие - приветствие нового пользователя
if ($event === 'ONIMBOTWELCOMEMESSAGE' || $event === 'ONIMBotWelcomeMessage') {
    $dialogId = $data['PARAMS']['DIALOG_ID'] ?? $data['dialogId'] ?? null;
    $botId = $data['PARAMS']['BOT_ID'] ?? $data['botId'] ?? null;
    
    if ($dialogId && $botId) {
        $settingsCheck = overCRest::call('entity.item.get', [
            'ENTITY' => 'customButton',
            'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true']
        ]);
        
        $botToken = null;
        if (!empty($settingsCheck['result'])) {
            $botToken = $settingsCheck['result'][0]['PROPERTY_VALUES']['botToken_FIELDS'] ?? null;
        }
        
        overCRest::call('imbot.v2.Chat.Message.send', [
            'botId' => $botId,
            'botToken' => $botToken,
            'dialogId' => $dialogId,
            'fields' => [
                'message' => "👋 Добро пожаловать!\nЯ бот Overplan.\nИспользуйте кнопки для выполнения действий."
            ]
        ]);
        $handled = true;
    }
}

// Всегда отвечаем 200 OK, чтобы Bitrix24 не повторял запросы
http_response_code(200);
echo 'OK';