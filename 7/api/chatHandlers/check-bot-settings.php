// api/chatHandlers/check-bot-settings.php
<?php
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');

$memberId = '0884d82728e90322d71f0b8584889754';
overCRest::setCurrentBitrix24($memberId);

echo "<pre>";

// Получаем информацию о боте
$findBot = overCRest::call("imbot.bot.list", []);
foreach ($findBot['result'] as $bot) {
    if ($bot["CODE"] == 'OVERPLAN_REPORT_CRMBUTTONS') {
        echo "Информация о боте:\n";
        print_r($bot);
        break;
    }
}

// Проверяем все чаты бота
echo "\nЧаты, где есть бот:\n";
$chats = overCRest::call("imbot.chat.list", [
    'BOT_ID' => 14
]);
print_r($chats);

echo "</pre>";
?>