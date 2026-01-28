<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);

include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);



$findBot = overCRest::call("imbot.bot.list", [
    
]);


    foreach ($findBot['result'] as $bot) {
        if ($bot["CODE"] == 'OVERPLAN_REPORT_CRMBUTTONS') {
            $botId = $bot["ID"];
        }
    }

$deleteBot = overCRest::call("imbot.unregister", [
        "BOT_ID" => $botId,

]);




