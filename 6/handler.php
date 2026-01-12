<?php

// логируем всё
// file_put_contents(
//     __DIR__ . '/joinchat.log',
//     "\n\n=== NEW EVENT ===\n" . var_export($_REQUEST, true),
//     FILE_APPEND
// );


$memberId = $_REQUEST['auth']['member_id'];
// $path = pathinfo(__DIR__, PATHINFO_DIRNAME);
// include_once($path . '/overCRest.php');

// include_once __DIR__ . '/overCRest.php';


// overCRest::setCurrentBitrix24($memberId);



// $dialogId = $_REQUEST['data']['PARAMS']['DIALOG_ID'];
// $botId    = $_REQUEST['data']['PARAMS']['BOT_ID'];




// $welcomeMessage = overCRest::call("imbot.message.add", [
//     'BOT_ID'    => $botId,
//     'DIALOG_ID' => $dialogId,
//     'MESSAGE'   => "👋 Привет!\nЯ бот Overplan.\nГотов к работе.",

// ]);

// file_put_contents(__DIR__.'/result91.log', var_export($welcomeMessage, true), FILE_APPEND);
