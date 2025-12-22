<?php 
    $path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
    include_once($path . '/overCRest.php');
    include_once($path . '/settings.php');  

    $memberId = $_REQUEST['memberId'];
    overCRest::setCurrentBitrix24($memberId);

    $users = getUsers();
    $userInChat = getUserInChat();
    echo json_encode([
        'result' => [
            'users' => $users,
            'userInChat' => $userInChat
        ]
    ]);

    function getUsers() {

        $currentUser = overCRest::call(
            'user.current',
            []
        )['result'];

        $total = overCRest::call(
            'user.get',
            []
        )['total'];
        $total = floor($total / 50);

        $getUserBatch = [];
        for ($i = 0; $i <= $total; $i++) {
            $getUserBatch['List '.$i] = [
                'method' => 'user.get',
                'params' => [
                    'FILTER' => [
                        '!ID' => $currentUser['ID']
                    ],
                    'start' => $i * 50
                ]
            ];
        }

        $users = [];
        while(count($getUserBatch) != 0) {
            $getUsers = overCRest::callBatch(array_splice($getUserBatch, 0, 50))['result']['result'];

            foreach($getUsers as $list) {
                foreach($list as $user) {
                    $users[] = $user;
                }
            }
        }
        return $users;
    }

    function getUserInChat() {

        $batch = overCRest::callBatch([
            'currentUser' => [
                'method' => 'user.current',
                'params' => []
            ],
            'chatId' => [
                'method' => 'im.search.chat.list',
                'params' => [
                    "FIND"=> CHAT_REPORT
                ]
            ]

        ])['result']['result'];

        $currentUser = $batch['currentUser'];


        $total = overCRest::call(
            'im.chat.user.list',
            [
                "CHAT_ID" => $batch['chatId'][0]['id']
            ]
        )['total'];
        $total = floor($total / 50);

        $getUserBatch = [];
        for ($i = 0; $i <= $total; $i++) {
            $getUserBatch['List '.$i] = [
                'method' => 'im.chat.user.list',
                'params' => [
                    "CHAT_ID" => $batch['chatId'][0]['id'],
                    'start' => $i * 50
                ]
            ];
        }

        $users = [];
        while(count($getUserBatch) != 0) {
            $getUsers = overCRest::callBatch(array_splice($getUserBatch, 0, 50))['result']['result'];

            foreach($getUsers as $list) {
                foreach($list as $user) {
                    $users[] = $user;
                }
            }
        }
        return $users;
    }