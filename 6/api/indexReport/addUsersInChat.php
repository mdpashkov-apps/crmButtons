<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);

include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$selectedUsers = $requestData['selectedUsers'];


$userIds = [];
foreach ($selectedUsers as $user) {
    if (!empty($user['value'])) {
        $userIds[] = (int)$user['value'];
    }
}




$findChat = overCRest::call("im.search.chat.list", [
    "FIND" => "ALLChat Overplan",
]);

$chatId = $findChat['result']['0']['id'];


$addUsersinChat = overCRest::call("im.chat.user.add", [
     "CHAT_ID" => $chatId,
    "USERS" => $userIds
]);