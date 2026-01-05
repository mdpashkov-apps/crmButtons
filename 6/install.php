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





// $findChat = overCRest::call("im.search.chat.list", [
//     "FIND" => "ALLChat Overplan",
// ]);
// if ($findChat['total'] == 0) {


// $chatAdd = overCRest::call("im.chat.add", [
//             "TYPE" => "CHAT",
//             "TITLE" => 'ALLChat Overplan',
//             "USERS" => [1]
// ]);


// $chatId = $chatAdd['result'];

// } else {
//     $chatId = $findChat['result']['0']['id'];

// }

// $findBot = overCRest::call("imbot.bot.list", [
    
// ]);



//     foreach ($findBot['result'] as $bot) {
//         if ($bot["CODE"] == 'OVERPLAN_REPORT_CRMBUTTONS') {
//             $botId = $bot["ID"];
//         }


// else {

//  $botReg = overCRest::call('imbot.register', [
//             'CODE' => 'OVERPLAN_REPORT_CRMBUTTONS',
//             'EVENT_MESSAGE_ADD' => 'https://app.overplan/applications/crmButtons/handler.php',
//             // 'EVENT_WELCOME_MESSAGE' => 'https://app.overplan/applications/chatbot_demo/handler.php',
//             // 'EVENT_BOT_DELETE' => 'https://app.overplan/applications/chatbot_demo/handler.php',
//             'PROPERTIES' => [ // Bot personality (req.)
//                 'NAME' => 'Overplan Report',
//                 'COLOR' => 'AQUA',
//                 // 'EMAIL' => 'no@example.com',
//                 // 'PERSONAL_BIRTHDAY' => '2020-07-18',
//                 // 'WORK_POSITION' => 'Report on affairs',
//                 // 'PERSONAL_WWW' => '',
//                 'PERSONAL_PHOTO' => 'iVBORw0KGgoAAAANSUhEUgAAAFYAAABWCAYAAABVVmH3AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAKsSURBVHhe7ZwxcuowEIbFqygpoYPjcAMo6bgJR6CEW8CRKOmgI9438sziOJbk3T9OnP+b2YkNWsl8FrKRPJm8KgJx51/8S5yhWBAUC4JiQVAsCIoFQbEgKBYExYKgWBAUC4JiQVAsCIoFQbEgKBYExYKgWBAUC4JiQVAsCIoFQbEgKBYExYL4M2Kv12tYrVZhMpl8GfL++XyOGUbkEaOxc7lcXrPZTB6lSsZ0On2dTqeY2Z/RP7slPXW73Yb7/R5fSVPJDY/HI+71Y9RDQZvU/X4v39JPcTgcYokQns9n3DJQVTpa5vP529e8khrfaUeXtTJasTKualEpqYIubwUiVj7Ucrl8O9CukLIeFwxNs7emaJ4IK+5iS67AOuRq7ImuO9Vb5aQ2j9mKu9hmTykNr96r6+zieDy+lZXIGTZSuIotGddyhgqLZF1PydDkIVVwE9s2BHRR3d68lU1FqWSdmzs0VfeuMduOi9g2qX3OfEp2yTjclt8VXj21xkVs6f1iDl9JzqUt11teFy5i0Qev68+lT44n5rkC+dm4Xq/j3v9PEbf8kJmnmtz6++R4Yha7WCzC7XaLexRbY56E0VKrYSBuEXOP/Y6e8Sd7LGmHYkFQLAiKBUGxIH68WPkB8htxFeu2Jq/Y7XZx65ch97EWZMZJqpHwXgUomd9tovOGwNxqcxbKawVAKF230vTN88KlVd1r67AKtvRWQecOgUurqQnqUsmlqxFtWHI9cG01d7mlS7S87rEaofOHANJqruCc6DtxroenIYC3apHcV6pQt2upw8K3n84c0ZvNxnXFdAj4L/hAcK4ABMWCoFgQFAuCYkFQLAiKBUGxICgWBMWCoFgQFAuCYkFQLAiKBUGxICgWBMWCoFgQFAuCYkFQLAiKBUGxICgWBMVCCOEDxdAfAdyPiCsAAAAASUVORK5CYII=',
//             ]
//             ]);

// $botId = $botReg["result"];




// }

// $addBotinChat = overCRest::call("im.chat.user.add", [
//      "CHAT_ID" => $chatId,
//     "USERS" => $botId
// ]);


//     }













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
