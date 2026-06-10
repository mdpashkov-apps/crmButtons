<?php
include_once(__DIR__ . '/overCRest.php');

$search_string = $_REQUEST['DOMAIN'];
$dir = dirname(__DIR__) . '/fieldTypeHandlers';
$files = scandir($dir);
$versions = basename(__DIR__);
foreach ($files as $file) {
    if (strpos(basename($file), $search_string) !== false) {
        file_put_contents(
            $dir . '/' . basename($file),
            '<? include("../' . $versions . '/buttonHandlers/button.php");'
        );
    }
}

$install_result = overCRest::installApp();

$eventBind = overCRest::call(
    'event.bind',
    [
        'event'   => 'OnAppUninstall',
        'handler' => 'https://app.overplan.ru/applications/crmButtons/7/uninstall.php'
    ]
);

overCRest::setLog($install_result, 'installation');

if ($install_result['install'] === true) {
    // Создаем сущность если не существует
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

    // Поля для хранения настроек кнопок
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
        'buttonInChat_FIELDS' => 'Кнопка в чате',
        'chatCommandId_FIELDS' => 'ID команды чата',
        'crmLinkFields_FIELDS' => 'Ссылка из поля crm',
        'botToken_FIELDS' => 'Токен чат-бота',
        'botId_FIELDS' => 'ID чат-бота',
        'chatId_FIELDS' => 'ID чата',
        'botRegistered' => 'Бот зарегистрирован',
        'buttonActionType_FIELDS' => 'Тип действия кнопки',
        'workflowFromFeed_FIELDS' => 'БП из ленты новостей',
        'workflowTemplateId_FIELDS' => 'ID шаблона БП',
        'workflowDocumentId_FIELDS' => 'ID документа для БП',
        'bpChainValue_FIELDS' => 'Цепочка БП (PRO)',
        'linkWithParams_FIELDS' => 'Ссылка с параметрами (PRO)',
        'installedByUserId' => 'ID установившего приложение'
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

    // ========== РАБОТА С ЧАТОМ (ТОЛЬКО СОЗДАНИЕ ЧАТА, БЕЗ БОТА) ==========
    
    $findChat = overCRest::call("im.search.chat.list", ["FIND" => "ALLChat Overplan"]);

    if (empty($findChat['result'])) {
        $chatAdd = overCRest::call("im.chat.add", [
            "TYPE" => "CHAT",
            "TITLE" => 'ALLChat Overplan',
            "USERS" => [1]
        ]);
        $chatId = $chatAdd['result'];
        overCRest::setLog(['chat_created' => $chatId], 'chat_creation');
    } else {
        $chatId = $findChat['result'][0]['id'];
        overCRest::setLog(['chat_found' => $chatId], 'chat_creation');
    }

    $settingsCheck = overCRest::call('entity.item.get', [
        'ENTITY' => 'customButton',
        'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true']
    ]);

    // Захват установившего: токен установки принадлежит установившему → user.current = он
    $installerId = 0;
    try {
        $me = overCRest::call('user.current', []);
        $installerId = (int)($me['result']['ID'] ?? 0);
    } catch (\Throwable $e) {}

    $settingsData = [
        'botToken_FIELDS' => '',
        'botId_FIELDS' => '',
        'chatId_FIELDS' => $chatId,
        'botRegistered' => '0',
        'installedByUserId' => $installerId
    ];

    if (empty($settingsCheck['result'])) {
        overCRest::call('entity.item.add', [
            'ENTITY' => 'customButton',
            'PROPERTY_VALUES' => array_merge([
                'buttonName_FIELDS' => 'PORTAL_SETTINGS',
                'isPortalSettings' => 'true',
            ], $settingsData)
        ]);
    } else {
        $settingsId = $settingsCheck['result'][0]['ID'];
        overCRest::call('entity.item.update', [
            'ENTITY' => 'customButton',
            'ID' => (int)$settingsId,
            'PROPERTY_VALUES' => $settingsData
        ]);
    }
}

?>

<?php
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
if ($install_result['install'] === true):
?>
    <p>✅ Установка успешно завершена!</p>
    <p>💬 Чат "ALLChat Overplan": <?= $chatId ? 'создан/найден (ID: ' . $chatId . ')' : 'не создан' ?></p>
    <p>🤖 Для работы кнопок в чате необходимо добавить чат-бота в разделе "Настройка уведомлений"</p>
    <script>
        BX24.init(function () {
            BX24.installFinish();
        });
    </script>
<?php else: ?>
    <pre><?php print_r($install_result); ?></pre>
    <p>❌ Ошибка установки</p>
<?php endif; ?>
</body>
</html>
<?php
endif;
?>