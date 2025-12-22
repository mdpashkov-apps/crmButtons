<?php
    $path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
    include_once($path . '/overCRest.php');    
    include_once($path . '/settings.php');    

    $request = json_decode(file_get_contents('php://input'), true);
    $memberId = $request['memberId'];
    overCRest::setCurrentBitrix24($memberId);

    $getChatAndBot = overCRest::call("im.search.chat.list", [
        "FIND"=> CHAT_REPORT
    ])["result"];

    $removeUser = overCRest::call("im.chat.user.delete", [
        "CHAT_ID" => $getChatAndBot[0]["id"],
        "USER_ID" => $request["user"]["ID"]
    ]);
    
    echo json_encode([
        "result" => $removeUser
    ]);