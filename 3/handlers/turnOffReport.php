<?php
    $path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
    include_once($path . '/overCRest.php');    
    include_once($path . '/settings.php');    

    $database = APP_DATABASE;
    $tableName = $database['table'];

    $request = json_decode(file_get_contents('php://input'), true);
    $memberId = $request['memberId'];
    $reportCheck = $request['reportCheck'];


    $mysqli = new mysqli($database['host'], $database['login'], $database['password'], $database['database']);
    if ($mysqli->connect_error) {
        echo "Не удалось подключиться к базе данных: " . $mysqli->connect_error;
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE $tableName SET application_report=? WHERE member_id=?");
    $stmt->bind_param("ds", $reportCheck, $memberId);
    $update = $stmt->execute();
    
    if($update) $updateMessage = $reportCheck == '1' ? 'Отписаться от рассылки' : 'Подписаться на рассылку';

    echo json_encode([
        'result' => $updateMessage
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



    // if(!$botCheck) {
    //     $result = overCRest::call("im.chat.user.delete", [
    //         "CHAT_ID" => $getChatAndBot["findChat"][0]["id"],
    //         "USER_ID" => $botId
    //     ])['result'];
    //     $answer = "Удалить чат-бота";
    // } else {
    //     $result = overCRest::call("im.chat.user.add", [
    //         "CHAT_ID" => $getChatAndBot["findChat"][0]["id"],
    //         "USERS" => [$botId]
    //     ])['result'];
    //     $answer = "Добавить чат-бота";
    // }

    // echo json_encode([
    //     "result" => [
    //         "botId" => $result,
    //         "answer" => $answer
    //     ]
    // ]);    