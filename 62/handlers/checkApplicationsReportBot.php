<?php
    $path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
    include_once($path . '/settings.php');

    $database = APP_DATABASE;
    
    $mysqli = new mysqli($database['host'], $database['login'], $database['password'], $database['database']);
    if ($mysqli->connect_error) {
        echo "Не удалось подключиться к базе данных: " . $mysqli->connect_error;
        exit();
    }

    $tableName = $database['table'];
    $query = "SELECT application_report FROM $tableName";
    $check = $mysqli->query($query)->fetch_assoc();

    echo json_encode([
        'result' => $check
    ]);
    // overCRest::setCurrentBitrix24($memberId);

    // $getChatAndBot = overCRest::callBatch([
	// 	"findChat" => [
	// 		"method"=> "im.search.chat.list",
	// 		"params"=> [
	// 			"FIND"=> CHAT_REPORT
	// 		]   
	// 	],
	// 	"findBot"=> [
	// 		"method"=> "imbot.bot.list"
    //     ],
	// ])["result"]["result"];

    // foreach($getChatAndBot["findBot"] as $bot) {
	// 	if ($bot["CODE"] == BOT_REPORT_CODE) {
	// 		$botId = $bot["ID"];
	// 	}
	// }

    // $chatUsers = overCRest::call('im.chat.user.list', [
    //     'CHAT_ID' => $getChatAndBot['findChat'][0]['id']
    // ])['result'];

    // foreach ($chatUsers as $userId) {
    //     if($userId == $botId) echo json_encode([
    //         'result' => [
    //             'botId' => $botId,
    //         ]
    //     ]);    
    // }

