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
        'textColor_FIELDS' => 'БП сделок',
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
        'link_FIELDS' => 'Ссылка',
        'buttonInCRM_FIELDS' => 'Кнопка в карточке',

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
