<?php
$rows =  [];
// Путь и подключение overCRest
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
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
            $batchParamsSP['params']['start'] =  $i * 50;
            $bacthArrSP[(int)($i / 49)]["list_" . $i] =  $batchParamsSP;
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
            $entityBP = 'CCrmDocumentLead';
            $entityFields = 'crm.lead.fields';
            $resultDocument = getContracts(0, ['1']);
            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "Lead",
                    "name" => "Лид"
                ];
            break;
        case 'Contact':
            $entityBP = 'CCrmDocumentContact';
            $entityFields = 'crm.contact.fields';
            $resultDocument = getContracts(0, ['3']);
            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "Contact",
                    "name" => "Контакт"
                ];
            break;
        case 'Company':
            $entityBP = 'CCrmDocumentCompany';
            $entityFields = 'crm.company.fields';
            $resultDocument = getContracts(0, ['4']);
            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "Company",
                    "name" => "Компания"
                ];
            break;
        case 'Deal':
            $entityBP = 'CCrmDocumentDeal';
            $entityFields = 'crm.deal.fields';
            $resultDocument = getContracts(1, ['2']);
            // Выбранная сущность
            $newObject['array_entities_value'] =
                [
                    "value" => "Deal",
                    "name" => "Сделка"
                ];
            break;
        case '31':
            $entityBP = 'SMART_INVOICE';
            $entityFields = 'crm.type.fields';
            $resultDocument = getContracts(1, ['31']);
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
        $entityBP = 'DYNAMIC_'. $elem['PROPERTY_VALUES']['entitySelection_FIELDS'];
        $entityFields = 'crm.type.fields';
          // получение списка документов смарт-процесса
        $resultDocument = getContracts(1, [$elem['PROPERTY_VALUES']['entitySelection_FIELDS']]);
        // вывод в приложении смарт-процессов
          $newObject['array_entities_value'] =
              [
                  "value" => $elem['PROPERTY_VALUES']['entitySelection_FIELDS'],
                  "name" => $current_SP['title']
              ];
          break;   
            }  
     $entity_list = ["CCrmDocumentLead", "CCrmDocumentDeal", "CCrmDocumentContact", "CCrmDocumentCompany" ];
if (in_array($entityBP, $entity_list) ){
    // БП
    // Получаем список БП
    $bacthBP = [
       'method' => 'bizproc.workflow.template.list',
        'params' => [
            'select' => ['ID', 'NAME'],
            'filter' => ['ENTITY' => $entityBP]
        ]
    ];
    $totalBP = overCRest::call('bizproc.workflow.template.list', ['filter' => ['ENTITY' => $entityBP]])["total"];
    $listBP = ceil($totalBP / 50); //Количество необходимых листов +1 тк от нуля
    $bacthArrBP = [];
    for ($i = 0; $i < $listBP; $i++) {
        $batchParams = $bacthBP;
        $batchParams['params']['start'] =  $i * 50;
        $bacthArrBP[(int)($i / 49)]["list_" . $i] =  $batchParams;
    }
    $resultBP = [];
    foreach ($bacthArrBP as $key => $cmd_arr) {
        sleep(2); //Щадяший режим лучше ставить 2 секунды
        $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
        foreach ($batchResult as $elementBP) {
            $resultBP = array_merge($resultBP, $elementBP);
        }
    } 
}
    else {
    if ($entityBP == '31') {
        $bacthBP = [
            'method' => 'bizproc.workflow.template.list',
            'params' => [
                'select' => ['ID', 'NAME'],
                'filter' => ['DOCUMENT_TYPE' => 'SMART_INVOICE']
            ]
        ];

        $totalBP = overCRest::call('bizproc.workflow.template.list', ['filter' => ['DOCUMENT_TYPE' => 'SMART_INVOICE']])["total"];
        $listBP = ceil($totalBP / 50); //Количество необходимых листов +1 тк от нуля
        $bacthArrBP = [];
        for ($i = 0; $i < $listBP; $i++) {
            $batchParams = $bacthBP;
            $batchParams['params']['start'] =  $i * 50;
            $bacthArrBP[(int)($i / 49)]["list_" . $i] =  $batchParams;
        }
        $resultBP = [];
        foreach ($bacthArrBP as $key => $cmd_arr) {
            sleep(2); //Щадяший режим лучше ставить 2 секунды
            $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
            foreach ($batchResult as $elementBP) {
                $resultBP = array_merge($resultBP, $elementBP);
            }
        } 
    }
    else {
    $bacthBP = [
        'method' => 'bizproc.workflow.template.list',
         'params' => [
             'select' => ['ID', 'NAME'],
             'filter' => ['DOCUMENT_TYPE' => $entityBP]
         ]
     ];
     $totalBP = overCRest::call('bizproc.workflow.template.list', ['filter' => ['DOCUMENT_TYPE' => $entityBP]])["total"];
     $listBP = ceil($totalBP / 50); //Количество необходимых листов +1 тк от нуля
     $bacthArrBP = [];
     for ($i = 0; $i < $listBP; $i++) {
         $batchParams = $bacthBP;
         $batchParams['params']['start'] =  $i * 50;
         $bacthArrBP[(int)($i / 49)]["list_" . $i] =  $batchParams;
     }
     $resultBP = [];
     foreach ($bacthArrBP as $key => $cmd_arr) {
         sleep(2); //Щадяший режим лучше ставить 2 секунды
         $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
         foreach ($batchResult as $elementBP) {
             $resultBP = array_merge($resultBP, $elementBP);
         }
     } 
    }
}
    // ID выбранных БП
    $idBP  = json_decode($elem['PROPERTY_VALUES']['businessProcessesValue_FIELDS']);
    // Выбранные БП
    $valueBP = [];
    // список БП
    $optionsBP = [];
    foreach ($resultBP as $key => $element) {
        $optionsBP[] = ['value' => $element['ID'], 'name' => $element['NAME']];
        if (in_array($element['ID'], $idBP)) {
            $valueBP[] = ['value' => $element['ID'], 'name' => $element['NAME']];
        }
    }
    // сохраняем выбранные БП    
    $newObject['button_actions']['business_processes']['value'] = $valueBP;
    // сохраняем список БП
    $newObject['button_actions']['business_processes']['options'] = $optionsBP;

    // Документы
    // id Выбраных документов
    $idDoc = json_decode($elem['PROPERTY_VALUES']['documentTemplatesValue_FIELDS']);
    // выбранные документы
    $valueDocument = [];
    // Спсок документов
    $optionsDocument = [];
    foreach ($resultDocument as $key => $element) {
        $optionsDocument[] = ['value' => $element['id'], 'name' => $element['name']];
        if (in_array($element['id'], $idDoc)) {
            $valueDocument[] = ['value' => $element['id'], 'name' => $element['name']];
        }
    }
    $newObject['button_actions']['document_templates']['value'] = $valueDocument;
    $newObject['button_actions']['document_templates']['options'] = $optionsDocument;

    $idList = $elem['PROPERTY_VALUES']['listsValue_FIELDS']; // поменял тут

    // Списки    
    if ($elem['PROPERTY_VALUES']['entitySelection_FIELDS']) {
        // Список
        $resultLists = overCRest::call('lists.get', [
            'IBLOCK_TYPE_ID' => 'lists',
        ])['result'];
        $valuelists = [];
        $optionsLists = [];
        foreach ($resultLists as $key => $element) {
            $optionsLists[] = ['value' => $element['ID'], 'name' => $element['NAME']];
            if ($elem['PROPERTY_VALUES']['listsValue_FIELDS'] == $element['ID']) { // поменял тут
                $valuelists[] = ['value' => $element['ID'], 'name' => $element['NAME']];
                $idLists = $element['ID'];
            }
           
        }
        $newObject['button_actions']['lists'] = [
            "options" => $optionsLists,
            "value" => $valuelists
        ];
    } else {
        $newObject['button_actions']['lists'] = [
            "options" => [],
            "value" => []
        ];
    }
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
        if($element["type"] == 'enumeration'){
            $listBool = true;
        }
        if ($element['listLabel']) {
            $deleteUserFields[$key] = $element['listLabel'];
            $optionsUserFields[] = ['value' => $key, 'name' => $element['listLabel'],'list'=>$listBool];
            $deleteUserFieldsTypeList[$key] = $listBool;
        } else {
            $optionsUserFields[] = ['value' => $key, 'name' => $element['title'],'list'=>$listBool];
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
        if($element["TYPE"]== "L"){
            $listBool = true;
        }
        $deleteListField[$key] = ['name' => $element['NAME'], 'isRequired' => $element['IS_REQUIRED'],'list'=> $listBool];
    }
    $deleteUserFields['null'] = '';
    // исходный массив для таблицы
    $tableFields = [];
    // Создание нового массива данных для таблицы
    $resultFieldsTable = json_decode($elem['PROPERTY_VALUES']['fieldsTable_FIELDS']);

    foreach ($resultFieldsTable[0] as $keytable => $element) {
        if (array_key_exists($resultFieldsTable[1][$keytable], $deleteListField) && array_key_exists($element, $deleteUserFields)) {
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
else {
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

            if($element["type"] == 'enumeration'){
                $listBool = true;
            }
            if ($element['listLabel']) {
                $deleteUserFields[$key] = $element['listLabel'];
                $optionsUserFields[] = ['value' => $key, 'name' => $element['listLabel'],'list'=>$listBool];
                $deleteUserFieldsTypeList[$key] = $listBool;
            } else {
                $optionsUserFields[] = ['value' => $key, 'name' => $element['title'],'list'=>$listBool];
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
            if($element["TYPE"]== "L"){
                $listBool = true;
            }
            $deleteListField[$key] = ['name' => $element['NAME'], 'isRequired' => $element['IS_REQUIRED'],'list'=> $listBool];
        }
        $deleteUserFields['null'] = '';
        // исходный массив для таблицы
        $tableFields = [];
        // Создание нового массива данных для таблицы
        $resultFieldsTable = json_decode($elem['PROPERTY_VALUES']['fieldsTable_FIELDS']);
        foreach ($resultFieldsTable[0] as $keytable => $element) {
            if (array_key_exists($resultFieldsTable[1][$keytable], $deleteListField) && array_key_exists($element, $deleteUserFields)) {
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

function getContracts($tip, $request)
{
    $result = [];
    if ($tip == 1) {
        $resultCategorys = overCRest::call('crm.category.list', [
            'entityTypeId' => $request[0]
        ])['result']['categories'];
        $resultBatchDocuments = [];
        foreach ($resultCategorys as $elem) {
            if ($request[0] == 2) {
                $ent = '2_category_' . (string)$elem['id'];
            }
            else {
                $ent = $request[0] . "_" . (string)$elem['id'];
            }
            $bacthDocument = [
                'method' => 'crm.documentgenerator.template.list',
                'params' => [
                    'filter' => ['entityTypeId' => $ent],
                    'select' => ['id', 'name', 'entityTypeId']
                ]
            ];
            $totalDocument = overCRest::call('crm.documentgenerator.template.list', ['filter' => ['entityTypeId' => $ent]])["total"];
            $listDocument = ceil($totalDocument / 50); //Количество необходимых листов +1 тк от нуля
            $bacthArrDocument = [];
            for ($i = 0; $i < $listDocument; $i++) {
                $batchParams = $bacthDocument;
                $batchParams['params']['start'] =  $i * 50;
                $bacthArrDocument[(int)($i / 49)]["list_" . $i] =  $batchParams;
            }
            foreach ($bacthArrDocument as $cmd_arr) {
                sleep(2); //Щадяший режим лучше ставить 2 секунды
                $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
                foreach ($batchResult as $elementDocument) {
                    $resultBatchDocuments = array_merge($resultBatchDocuments, $elementDocument['templates']);
                }
            }
        }
        $resultDocuments = [];
        foreach ($resultBatchDocuments as $element) {
            $resultDocuments[] = ['id' => $element['id'], 'name' => $element['name']];
        }
        $result = unique_multidim_array($resultDocuments, 'id');
    } else {
        $bacthDocument = [
            'method' => 'crm.documentgenerator.template.list',
            'params' => [
                'select' => ['id', 'name'],
                'filter' => ['entityTypeId' => $request]
            ]
        ];
        $totalDocument = overCRest::call('crm.documentgenerator.template.list', ['filter' => ['entityTypeId' => $request]])["total"];
        $listDocument = ceil($totalDocument / 50); //Количество необходимых листов +1 тк от нуля
        $bacthArrDocument = [];
        for ($i = 0; $i < $listDocument; $i++) {
            $batchParams = $bacthDocument;
            $batchParams['params']['start'] =  $i * 50;
            $bacthArrDocument[(int)($i / 49)]["list_" . $i] =  $batchParams;
        }
        foreach ($bacthArrDocument as $key => $cmd_arr) {
            sleep(2); //Щадяший режим лучше ставить 2 секунды
            $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
            foreach ($batchResult as $elementDocument) {
                $result = array_merge($result, $elementDocument['templates']);
            }
        }
    }
    return $result;
}


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
