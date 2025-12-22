<?php
    $path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
    include_once($path . '/settings.php');
    $memberId = $_REQUEST['memberId'];

    $database = [
        'host' => "localhost",
        'login' => 'bitrix0',
        'password' => 'Ji]T@sq[IvSs=0b6ZHRz',
        'database' => 'sitemanager',
        'table' => 	TABLE_NAME
    ];
    
    $mysqli = new mysqli($database['host'], $database['login'], $database['password'], $database['database']);
    if ($mysqli->connect_error) {
        echo "Не удалось подключиться к базе данных: " . $mysqli->connect_error;
        exit();
    }

    $tableName = $database['table'];

    $query = "SELECT application_report FROM $tableName WHERE member_id='$memberId'";
    $check = $mysqli->query($query)->fetch_assoc();

    $checkMessage = $check['application_report'] == '1' ? 'Отписаться от рассылки' : 'Подписаться на рассылку';

    echo json_encode([
        'result' => [
            'check' => $check['application_report'],
            'checkMessage' => $checkMessage
        ]
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

