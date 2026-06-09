<?php
include_once(__DIR__.'/overCRest.php');

$member_id = $_POST['member_id'];
if($member_id) {
    overCRest::setCurrentBitrix24($member_id);

    // сохраняем прежнее поведение (проверка доступного скоупа)
    $res = overCRest::call('scope', [
        'select' => ['id', 'title']
    ]);

    // ===== Доведение схемы сущности до актуальной =====
    // При обновлении приложения (без переустановки) install.php не выполняется,
    // поэтому новые поля сущности нужно досоздать здесь — идемпотентно.

    // 1. Убедимся, что сущность customButton существует
    $entitysGet = overCRest::call('entity.get', [
        'ENTITY' => 'customButton'
    ]);
    if (isset($entitysGet['error'])) {
        overCRest::call('entity.add', [
            'ENTITY' => 'customButton',
            'NAME'   => 'customButton',
            'ACCESS' => ['AU' => 'W'],
        ]);
    }

    // 2. Полный список полей (синхронизирован с install.php)
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
        'linkWithParams_FIELDS' => 'Ссылка с параметрами (PRO)'
    ];

    // 3. Какие поля уже заведены
    $existFields = [];
    $fieldsGet = overCRest::call('entity.item.property.get', [
        'ENTITY' => 'customButton'
    ]);
    if (!isset($fieldsGet['error']) && !empty($fieldsGet['result'])) {
        foreach ($fieldsGet['result'] as $field) {
            $existFields[] = $field['PROPERTY'];
        }
    }

    // 4. Досоздаём недостающие
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
