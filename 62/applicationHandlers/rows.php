<?php
$rows = [];
// Путь и подключение overCRest
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
// Получаем данные из хранилища
$result = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton'
])['result'];

$newRows = [];
foreach ($result as $elem) {
    $newObject = [];
    // ID записи
    $newObject['id'] = (int)$elem['ID'];
    // Имя кнопки
    $newObject['name'] = $elem['NAME'];
    // Выбрана ли кнопка (для фронта)
    $newObject['lists_btn_bool'] = false;
    // Цвет кнопки 
    $newObject['color_btn'] = $elem['PROPERTY_VALUES']['buttonColor_FIELDS'];
    // Цвет текста кнопки
    $newObject['color_text'] = $elem['PROPERTY_VALUES']['textColor_FIELDS'];
    // Радиус кнопки
    $newObject['radius_btn'] = $elem['PROPERTY_VALUES']['buttonRadius_FIELDS'];
    // Текст кнопки
    $newObject['text_btn'] = $elem['PROPERTY_VALUES']['textOnTheButton_FIELDS'];
    // Использование иконки
    $newObject['use_icon'] = json_decode($elem['PROPERTY_VALUES']['usingTheIcon_FIELDS']);
    // Сама икнока
    $newObject['icon_btn'] = $elem['PROPERTY_VALUES']['iconOnTheButton_FIELDS'];
    // использование границы кнопки
    $newObject['buttonBorderSelection'] = json_decode($elem['PROPERTY_VALUES']['buttonBorder_FIELDS']);
    // ширина границы кнопки
    $newObject['buttonBorderWidth'] = $elem['PROPERTY_VALUES']['buttonBorderWidth_FIELDS'];
    // цвет границы кнопки
    $newObject['buttonBorderColor'] = $elem['PROPERTY_VALUES']['buttonBorderColor_FIELDS'];
    // сохраненные стили
    $newObject['styleButton'] = [
        "color" => $elem['PROPERTY_VALUES']['textColor_FIELDS'],
        "border" => json_decode($elem['PROPERTY_VALUES']['buttonBorder_FIELDS']),
        "backgroundColor" => $elem['PROPERTY_VALUES']['buttonColor_FIELDS'],
        "borderRadius" => $elem['PROPERTY_VALUES']['buttonRadius_FIELDS'],
        "borderWidth" => $elem['PROPERTY_VALUES']['buttonBorderWidth_FIELDS'],
        "borderColor" => $elem['PROPERTY_VALUES']['buttonBorderColor_FIELDS']
    ];
    // Список сущностей
    $newObject['array_entities'] = [
        [
            "value" => "Lead",
            "name" => "Лид"
        ],
        [
            "value" => "Deal",
            "name" => "Сделка"
        ],
        [
            "value" => "Contact",
            "name" => "Контакт"
        ],
        [
            "value" => "Company",
            "name" => "Компания"

        ],
        [
            'value' => '31',
            'name' => 'Счета'
        ],
    ];
// Смарт-процессы
    // Получаем список смарт-процессов
    $batchSP = [
        'method' => 'crm.type.list',
        'params' => [
            'select' => ['ID', 'NAME'],
            'filter' => []
        ]
    ];
    $totalSP = overCRest::call('crm.type.list', [])["total"];
    $listSP = ceil($totalSP / 50); //Количество необходимых листов +1 тк от нуля
    $bacthArrSP = [];
    for ($i = 0; $i < $listSP; $i++) {
        $batchParamsSP = $batchSP;
        $batchParamsSP['params']['start'] = $i * 50;
        $bacthArrSP[(int)($i / 49)]["list_" . $i] = $batchParamsSP;
    }
    $resultSP = [];
    foreach ($bacthArrSP as $key => $cmdSP_arr) {
        sleep(2); //Щадяший режим лучше ставить 2 секунды
        $batchResultSP = overCRest::callBatch($cmdSP_arr, false)['result']['result'];
        foreach ($batchResultSP as $elementSP) {
            $resultSP = array_merge($resultSP, $elementSP);
        }
    }
    foreach ($resultSP['types'] as $SP) {
        array_push($newObject['array_entities'], ["value" => $SP['entityTypeId'], "name" => $SP['title']]);
    }
    // id выбранных действий
    $newObject['button_actions']['id'] = json_decode($elem['PROPERTY_VALUES']['buttonActionsId_FIELDS']);
    // выбор методов каждой сущности для получения информации о них
    $entityBP = '';
    $entityFields = '';
    $resultDocument = [];
    switch ($elem['PROPERTY_VALUES']['entitySelection_FIELDS']) {
        case 'Lead':
            $entityFields = 'crm.lead.fields';

            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "Lead",
                    "name" => "Лид"
                ];
            break;
        case 'Contact':
            $entityFields = 'crm.contact.fields';
            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "Contact",
                    "name" => "Контакт"
                ];
            break;
        case 'Company':
            $entityFields = 'crm.company.fields';
            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "Company",
                    "name" => "Компания"
                ];
            break;
        case 'Deal':
            $entityFields = 'crm.deal.fields';
            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "Deal",
                    "name" => "Сделка"
                ];
            break;
        case '31':
            $entityFields = 'crm.type.fields';
            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "31",
                    "name" => "Счета"
                ];
            break;
        //смарт-процессы
        default:
            $current_SP = [];
            foreach ($resultSP['types'] as $SP) {
                if ($SP['entityTypeId'] == $elem['PROPERTY_VALUES']['entitySelection_FIELDS']) {
                    $current_SP = $SP;
                }
            }
            //получение бп смарт-процесса
            $entityFields = 'crm.type.fields';
            // получение списка документов смарт-процесса
            // вывод в приложении смарт-процессов
            $newObject['array_entities_value'] =
                [
                    "value" => $elem['PROPERTY_VALUES']['entitySelection_FIELDS'],
                    "name" => $current_SP['title']
                ];
            break;
    }

    $newObject['button_actions']['business_processes']['value'] = [];
    $newObject['button_actions']['business_processes']['options'] = [];

    $newObject['button_actions']['document_templates']['value'] = [];
    $newObject['button_actions']['document_templates']['options'] = [];
    $newObject['button_actions']['lists']['value'] = [];
    $newObject['button_actions']['lists']['options'] = [];
    $newObject['button_actions']['crmLinkFilds']['options'] = [];
    $newObject['button_actions']['crmLinkFilds']['value'] = [];
    $idList = $elem['PROPERTY_VALUES']['listsValue_FIELDS']; // поменял тут


    if (($entityFields == 'crm.type.fields') || ($entityFields == 'crm.item.fields')) {
        // Поля таблицы
        if ($idList) {
            // Поля сущности
            $resultUserFields = overCRest::call($entityFields, [])['result']['fields'];
            // массив полей сущности
            $optionsUserFields = [];
            // для исключения удаленых полей
            $deleteUserFields = [];
            $deleteUserFieldsTypeList = [];
            foreach ($resultUserFields as $key => $element) {
                $listBool = false;
                if ($element["type"] == 'enumeration') {
                    $listBool = true;
                }
                if ($element['listLabel']) {
                    $deleteUserFields[$key] = $element['listLabel'];
                    $optionsUserFields[] = ['value' => $key, 'name' => $element['listLabel'], 'list' => $listBool];
                    $deleteUserFieldsTypeList[$key] = $listBool;
                } else {
                    $optionsUserFields[] = ['value' => $key, 'name' => $element['title'], 'list' => $listBool];
                    $deleteUserFields[$key] = $element['title'];
                    $deleteUserFieldsTypeList[$key] = $listBool;
                }
            }
            // записываем поля сущности
            $newObject['button_actions']['optionsEntity'] = $optionsUserFields;

            // поля списка
            $resultListFields = overCRest::call('lists.field.get', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $idList,
            ])['result'];
            // исходный массив для таблицы
            $optionsListFields = [];
            // для исключения удаленых элементов списка
            $deleteListField = [];
            foreach ($resultListFields as $key => $element) {
                $listBool = false;
                if ($element["TYPE"] == "L") {
                    $listBool = true;
                }
                $deleteListField[$key] = [
                    'name' => $element['NAME'],
                    'isRequired' => $element['IS_REQUIRED'],
                    'list' => $listBool
                ];
            }
            $deleteUserFields['null'] = '';
            // исходный массив для таблицы
            $tableFields = [];
            // Создание нового массива данных для таблицы
            $resultFieldsTable = json_decode($elem['PROPERTY_VALUES']['fieldsTable_FIELDS']);

            foreach ($resultFieldsTable[0] as $keytable => $element) {
                if (array_key_exists($resultFieldsTable[1][$keytable], $deleteListField) && array_key_exists(
                        $element,
                        $deleteUserFields
                    )) {
                    $tableField = [];
                    $tableField['fieldsLists'] = [
                        'value' => $resultFieldsTable[1][$keytable],
                        'name' => $deleteListField[$resultFieldsTable[1][$keytable]]['name'],
                        'isRequired' => $deleteListField[$resultFieldsTable[1][$keytable]]['isRequired'],
                        'list' => $deleteListField[$resultFieldsTable[1][$keytable]]['list']
                    ];
                    if ($element == 'null') {
                        $tableField['fieldsEntiyValue'] = null;
                    } else {
                        $tableField['fieldsEntiyValue'] = [
                            'name' => $deleteUserFields[$element],
                            'value' => $element,
                            'list' => $deleteUserFieldsTypeList[$element]
                        ];
                    }
                    $tableFields[] = $tableField;
                }
            }
            $newObject['button_actions']["fields_table"] = $tableFields;
        } else {
            $newObject['button_actions']["fields_table"] = [];
        }
        $newObject['button_actions']['link'] = $elem['PROPERTY_VALUES']['link_FIELDS'];
        $newObject['button_actions']['button_in_CRM'] = json_decode($elem['PROPERTY_VALUES']['buttonInCRM_FIELDS']);
        $newRows[] = $newObject;
    } else {
        // Поля таблицы
        if ($idList) {
            // Поля сущности
            $resultUserFields = overCRest::call($entityFields, [])['result'];
            // массив полей сущности
            $optionsUserFields = [];
            // для исключения удаленых полей
            $deleteUserFields = [];
            $deleteUserFieldsTypeList = [];
            foreach ($resultUserFields as $key => $element) {
                $listBool = false;

                if ($element["type"] == 'enumeration') {
                    $listBool = true;
                }
                if ($element['listLabel']) {
                    $deleteUserFields[$key] = $element['listLabel'];
                    $optionsUserFields[] = ['value' => $key, 'name' => $element['listLabel'], 'list' => $listBool];
                    $deleteUserFieldsTypeList[$key] = $listBool;
                } else {
                    $deleteUserFields[$key] = $element['title'];
                    $deleteUserFieldsTypeList[$key] = $listBool;
                }
            }
            // записываем поля сущности
            $newObject['button_actions']['optionsEntity'] = $optionsUserFields;

            // поля списка
            $resultListFields = overCRest::call('lists.field.get', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $idList,
            ])['result'];
            // исходный массив для таблицы
            $optionsListFields = [];
            // для исключения удаленых элементов списка
            $deleteListField = [];
            foreach ($resultListFields as $key => $element) {
                $listBool = false;
                if ($element["TYPE"] == "L") {
                    $listBool = true;
                }
                $deleteListField[$key] = [
                    'name' => $element['NAME'],
                    'isRequired' => $element['IS_REQUIRED'],
                    'list' => $listBool
                ];
            }
            $deleteUserFields['null'] = '';
            // исходный массив для таблицы
            $tableFields = [];
            // Создание нового массива данных для таблицы
            $resultFieldsTable = json_decode($elem['PROPERTY_VALUES']['fieldsTable_FIELDS']);
            foreach ($resultFieldsTable[0] as $keytable => $element) {
                if (array_key_exists($resultFieldsTable[1][$keytable], $deleteListField) && array_key_exists(
                        $element,
                        $deleteUserFields
                    )) {
                    $tableField = [];
                    $tableField['fieldsLists'] = [
                        'value' => $resultFieldsTable[1][$keytable],
                        'name' => $deleteListField[$resultFieldsTable[1][$keytable]]['name'],
                        'isRequired' => $deleteListField[$resultFieldsTable[1][$keytable]]['isRequired'],
                        'list' => $deleteListField[$resultFieldsTable[1][$keytable]]['list']
                    ];
                    if ($element == 'null') {
                        $tableField['fieldsEntiyValue'] = null;
                    } else {
                        $tableField['fieldsEntiyValue'] = [
                            'name' => $deleteUserFields[$element],
                            'value' => $element,
                            'list' => $deleteUserFieldsTypeList[$element]
                        ];
                    }
                    $tableFields[] = $tableField;
                }
            }
            $newObject['button_actions']["fields_table"] = $tableFields;
        } else {
            $newObject['button_actions']["fields_table"] = [];
        }
        $newObject['button_actions']['link'] = $elem['PROPERTY_VALUES']['link_FIELDS'];
        $newObject['button_actions']['button_in_CRM'] = json_decode($elem['PROPERTY_VALUES']['buttonInCRM_FIELDS']);
        $newRows[] = $newObject;
    }
}
echo json_encode([
    'rows' => $newRows,

], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function unique_multidim_array($array, $key)
{
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}
