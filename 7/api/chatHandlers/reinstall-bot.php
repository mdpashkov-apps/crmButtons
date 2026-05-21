<?php
// api/chatHandlers/reinstall-bot.php

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');

$memberId = '0884d82728e90322d71f0b8584889754';
overCRest::setCurrentBitrix24($memberId);

echo "<pre>";

// 1. Находим существующего бота
$findBot = overCRest::call("imbot.bot.list", []);
$oldBotId = null;

foreach ($findBot['result'] as $bot) {
    if ($bot["CODE"] == 'OVERPLAN_REPORT_CRMBUTTONS') {
        $oldBotId = $bot["ID"];
        echo "Найден старый бот, ID: {$oldBotId}\n";
        break;
    }
}

// 2. Удаляем старого бота
if ($oldBotId) {
    echo "Удаление старого бота...\n";
    $deleteBot = overCRest::call("imbot.unregister", [
        "BOT_ID" => $oldBotId
    ]);
    print_r($deleteBot);
}

// 3. Регистрируем нового бота
echo "\nРегистрация нового бота...\n";
$botReg = overCRest::call('imbot.register', [
    'CODE' => 'OVERPLAN_REPORT_CRMBUTTONS',
    'TYPE' => 'BOT',  // Важно: тип BOT, а не OPENLINE
    'EVENT_MESSAGE_ADD' => 'https://app.overplan.ru/applications/crmButtons/7/api/chatHandlers/chat-command-handler.php',
    'EVENT_WELCOME_MESSAGE' => 'https://app.overplan.ru/applications/crmButtons/7/api/chatHandlers/chat-command-handler.php',
    'EVENT_BOT_DELETE' => 'https://app.overplan.ru/applications/crmButtons/7/api/chatHandlers/chat-command-handler.php',
    'PROPERTIES' => [
        'NAME' => 'Overplan Report',
        'COLOR' => 'AQUA',
        'EMAIL' => 'hello@overplan.ru',
        'PERSONAL_WWW' => 'overplan.ru'
    ]
]);

print_r($botReg);

echo "\n✅ Бот переустановлен!\n";
echo "Новый ID бота: " . ($botReg['result'] ?? 'неизвестно') . "\n";

echo "</pre>";
?>