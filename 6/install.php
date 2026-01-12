<?php
include_once(__DIR__ . '/overCRest.php');

$install_result = overCRest::installApp();

$eventBind = overCRest::call(
    'event.bind',
    [
        'event'   => 'OnAppUninstall',
        'handler' => 'https://app.overplan.ru/applications/crmButtons/6/uninstall.php'
    ]
);

overCRest::setLog($install_result, 'installation');

if ($install_result['install'] === true) {

    $entitysGet = overCRest::call('entity.get', [
        'ENTITY' => 'customButton'
    ]);

    if (isset($entitysGet['error'])) {
        overCRest::call('entity.add', [
            'ENTITY' => 'customButton',
            'NAME'   => 'customButton',
            'ACCESS' => [
                'AU' => 'W'
            ]
        ]);
    }

    $fields = [
        'buttonName_FIELDS' => 'Название кнопки',
        'customField_FIELDS' => 'Пользовательский тип поля',
        'buttonColor_FIELDS' => 'Цвет кнопки',
        'textColor_FIELDS' => 'Цвет текста на кнопке',
        'buttonRadius_FIELDS' => 'Радиус кнопки',
        'buttonBorder_FIELDS' => 'Использование границы кнопки',
        'buttonBorderWidth_FIELDS' => 'Высота границы кнопки',
        'buttonBorderColor_FIELDS' => 'Цвет границы кнопки',
        'textOnTheButton_FIELDS' => 'Текст кнопки',
        'usingTheIcon_FIELDS' => 'Использование иконки',
        'iconOnTheButton_FIELDS' => 'Иконка на кнопке',
        'entitySelection_FIELDS' => 'Выбор сущности',
        'buttonActionsId_FIELDS' => 'Выбранные действия',
        'businessProcessesValue_FIELDS' => 'Выбранный БП',
        'documentTemplatesValue_FIELDS' => 'Выбранный шаблон документа',
        'listsValue_FIELDS' => 'Выбранный список',
        'fieldsTable_FIELDS' => 'Поля таблицы',
        'link_FIELDS' => 'Ссылка произвольная',
        'buttonInCRM_FIELDS' => 'Кнопка в карточке',
        'crmLinkFields_FIELDS' => 'Ссылка из поля crm',

    ];

    $existFields = [];

    $fieldsGet = overCRest::call('entity.item.property.get', [
        'ENTITY' => 'customButton'
    ]);

    if (!isset($fieldsGet['error']) && !empty($fieldsGet['result'])) {
        foreach ($fieldsGet['result'] as $field) {
            $existFields[] = $field['PROPERTY'];
        }
    }

    foreach ($fields as $code => $name) {
        if (in_array($code, $existFields, true)) {
            continue;
        }

        overCRest::call('entity.item.property.add', [
            'ENTITY'   => 'customButton',
            'PROPERTY' => $code,
            'NAME'     => $name,
            'TYPE'     => 'S'
        ]);
    }





$findChat = overCRest::call("im.search.chat.list", [
    "FIND" => "ALLChat Overplan",
]);

if ($findChat['total'] == 0) {


$chatAdd = overCRest::call("im.chat.add", [
            "TYPE" => "CHAT",
            "TITLE" => 'ALLChat Overplan',
            "USERS" => [1]
]);


$chatId = $chatAdd['result'];

} else {
    $chatId = $findChat['result']['0']['id'];

}

$findBot = overCRest::call("imbot.bot.list", [
    
]);


    foreach ($findBot['result'] as $bot) {
        if ($bot["CODE"] == 'OVERPLAN_REPORT_CRMBUTTONS') {
            $botId = $bot["ID"];
        }


else {

 $botReg = overCRest::call('imbot.register', [
            'CODE' => 'OVERPLAN_REPORT_CRMBUTTONS',
            'EVENT_MESSAGE_ADD' => 'https://app.overplan/applications/crmButtons/6/handler.php',
            'EVENT_WELCOME_MESSAGE' => 'https://app.overplan/applications/crmButtons/6/handler.php',
            'EVENT_BOT_DELETE' => 'https://app.overplan/applications/crmButtons/6/handler.php',
            'PROPERTIES' => [ // Bot personality (req.)
                'NAME' => 'Overplan Report',
                'COLOR' => 'AQUA',
                // 'EMAIL' => 'no@example.com',
                // 'PERSONAL_BIRTHDAY' => '2020-07-18',
                // 'WORK_POSITION' => 'Report on affairs',
                // 'PERSONAL_WWW' => '',
                'PERSONAL_PHOTO' => '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',
            ]
            ]);

$botId = $botReg["result"];

// file_put_contents(__DIR__.'/result91.log', var_export($botReg, true), FILE_APPEND);



}

$addBotinChat = overCRest::call("im.chat.user.add", [
     "CHAT_ID" => $chatId,
    "USERS" => $botId
]);


    }













}
?>

<?php if ($install_result['rest_only'] === false): ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>

<?php if ($install_result['install'] === true): ?>
    <p>Installation has been finished</p>
    <script>
        BX24.init(function () {
            BX24.installFinish();
        });
    </script>
<?php else: ?>
    <pre><?php print_r($install_result); ?></pre>
    <p>Installation error</p>
<?php endif; ?>

</body>
</html>
<?php endif; ?>
