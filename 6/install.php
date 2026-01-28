<?php
include_once(__DIR__ . '/overCRest.php');

// Устанавливаем приложение
$install_result = overCRest::installApp();

// Подписываемся на событие удаления приложения
$eventBind = overCRest::call(
    'event.bind',
    [
        'event'   => 'OnAppUninstall',
        'handler' => 'https://app.overplan.ru/applications/crmButtons/6/uninstall.php'
    ]
);

// Логируем результат установки
overCRest::setLog($install_result, 'installation');

// Если установка прошла успешно
if ($install_result['install'] === true) {
    // Проверяем, существует ли хранилище customButton
    $entitysGet = overCRest::call('entity.get', [
        'ENTITY' => 'customButton'
    ]);

    // Если сущности нет — создаём её
    if (isset($entitysGet['error'])) {
        overCRest::call('entity.add', [
            'ENTITY' => 'customButton',
            'NAME'   => 'customButton',
            'ACCESS' => [
                'AU' => 'W'
            ]
        ]);
    }

    // Описание полей сущности
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

    // Список уже существующих полей
    $existFields = [];

    // Получаем все свойства сущности
    $fieldsGet = overCRest::call('entity.item.property.get', [
        'ENTITY' => 'customButton'
    ]);

    // Если свойства найдены — сохраняем их коды
    if (!isset($fieldsGet['error']) && !empty($fieldsGet['result'])) {
        foreach ($fieldsGet['result'] as $field) {
            $existFields[] = $field['PROPERTY'];
        }
    }

    // Добавляем недостающие поля
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

    // Ищем чат ALLChat Overplan
    $findChat = overCRest::call("im.search.chat.list", [
        "FIND" => "ALLChat Overplan",
    ]);

    // Если чат не найден — создаём его
    if ($findChat['total'] == 0) {
        $chatAdd = overCRest::call("im.chat.add", [
            "TYPE" => "CHAT",
            "TITLE" => 'ALLChat Overplan',
            "USERS" => [1]
        ]);
        $chatId = $chatAdd['result'];
    } else {
        // Если найден — берем id существующего
        $chatId = $findChat['result']['0']['id'];
    }

    // Получаем список зарегистрированных ботов
    $findBot = overCRest::call("imbot.bot.list", []);

    foreach ($findBot['result'] as $bot) {
        // Если бот нашей компании уже существует — сохраняем его ID
        if ($bot["CODE"] == 'OVERPLAN_REPORT_CRMBUTTONS') {
            $botId = $bot["ID"];
        } else {
            // Если бота нет — регистрируем нового
            $botReg = overCRest::call('imbot.register', [
                'CODE' => 'OVERPLAN_REPORT_CRMBUTTONS',
                'EVENT_MESSAGE_ADD' => 'https://app.overplan/applications/crmButtons/6/handler.php',
                'EVENT_WELCOME_MESSAGE' => 'https://app.overplan/applications/crmButtons/6/handler.php',
                'EVENT_BOT_DELETE' => 'https://app.overplan/applications/crmButtons/6/handler.php',
                'PROPERTIES' => [
                    'NAME' => 'Overplan Report',
                    'COLOR' => 'AQUA',
                    'EMAIL' => 'hello@overplan.ru',
                    'PERSONAL_WWW' => 'overplan.ru',
                    'PERSONAL_PHOTO' => 'iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHL...'
                ]
            ]);
            $botId = $botReg["result"];
        }

        // Добавляем бота в чат
        $addBotinChat = overCRest::call("im.chat.user.add", [
            "CHAT_ID" => $chatId,
            "USERS" => $botId
        ]);
    }
}
?>

<?php
// Проверяем, что установка приложения выполняется не в режиме rest_only (rest_only = true означает, что HTML-часть выводить не нужно)
if ($install_result['rest_only'] === false):
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>

<?php
// Проверяем, успешно ли установилось приложение
if ($install_result['install'] === true):
?>
    <!-- Сообщение об успешной установке -->
    <p>Installation has been finished</p>
    <script>
        // Инициализация Bitrix24 JS API
        BX24.init(function () {
            // Сообщаем Bitrix24, что установка приложения завершена
            // Без этого установка будет считаться незаконченной
            BX24.installFinish();

        });
    </script>
<?php else: ?>
    <!-- Если установка завершилась с ошибкой -->
    <!-- Выводим полный массив результата для отладки -->
    <pre><?php print_r($install_result); ?></pre>
    <!-- Текстовое сообщение об ошибке -->
    <p>Installation error</p>
<?php endif; ?>
</body>
</html>
<?php
// Закрываем проверку rest_only
endif;
?>
