<?php
// api/indexReport/addUsersInChat.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// гейтинг PRO: чат-кнопки (и добавление участников чата) доступны только при активной подписке
require_once($path . '/api/billing/BillingClient.php');
if (!BillingClient::canUseFeature((string)$memberId, 'chat_button')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'feature_locked', 'feature' => 'chat_button', 'message' => 'Кнопки в чате доступны только на тарифе PRO.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once(__DIR__ . '/../chatHandlers/ensure-bot.php');

$selectedUsers = $requestData['selectedUsers'];

$userIds = [];
foreach ($selectedUsers as $user) {
    if (!empty($user['value'])) {
        $userIds[] = (int)$user['value'];
    }
}

// Логируем для отладки
file_put_contents(__DIR__ . '/add-users-debug.log', 
    date('Y-m-d H:i:s') . " - Добавление пользователей: " . print_r($userIds, true) . "\n", 
    FILE_APPEND);

// Находим чат
$findChat = overCRest::call("im.search.chat.list", [
    "FIND" => "ALLChat Overplan",
]);

if (empty($findChat['result'])) {
    // лениво создаём бота+чат (раньше чат создавала страница "Настройка уведомлений")
    $ensured = ensureBotAndChat($memberId);
    $chatId = $ensured['chatId'] ?? null;
} else {
    $chatId = $findChat['result'][0]['id'];
}

if (!$chatId) {
    echo json_encode(['error' => 'Не удалось создать чат'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Добавляем пользователей в чат
$addUsersinChat = overCRest::call("im.chat.user.add", [
    "CHAT_ID" => $chatId,
    "USERS" => $userIds
]);

// Возвращаем результат
echo json_encode([
    'result' => $addUsersinChat,
    'success' => empty($addUsersinChat['error']),
    'chatId' => $chatId,
    'usersAdded' => count($userIds)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>