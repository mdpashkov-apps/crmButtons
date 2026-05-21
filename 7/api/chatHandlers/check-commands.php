<?php
require_once(__DIR__ . '/overCRest.php');

$memberId = '0884d82728e90322d71f0b8584889754';
overCRest::setCurrentBitrix24($memberId);

// Получаем список команд бота
$commands = overCRest::call('imbot.v2.Command.list', [
    'botId' => 36
]);

echo "<pre>";
echo "=== ЗАРЕГИСТРИРОВАННЫЕ КОМАНДЫ ===\n";
print_r($commands);
echo "</pre>";
?>