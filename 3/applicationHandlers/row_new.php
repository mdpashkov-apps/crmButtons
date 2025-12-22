<?php
// Путь и подключение overCRest
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
// Получаем данные из хранилища
// $result = overCRest::call('entity.item.get', [
//     'ENTITY' => 'customButton'])['result'];

$row = [
    'name' => 'Новая кнопка 1',
    'lists_btn_bool' => true,
    'color_btn' => '#000000',
    'color_text' => '#ffffff',
    'radius_btn' => 5,
    'text_btn' => 'Кнопка',
    'use_icon' => true,
    'icon_btn' => '',
    "buttonBorderSelection" => false,
    "buttonBorderWidth" => "5",
    "buttonBorderColor" => "#000000",
    "styleButton" => [
        "color" => '#ffffff',
        "border" => true,
        "backgroundColor" => '#000000',
        "borderRadius" => '5',
        "borderWidth" => '5',
        "borderColor" => '#000000',
    ],
    'array_entities' =>
    [
        [
            'value' => 'Lead',
            'name' => 'Лид'
        ],
        [
            'value' => 'Deal',
            'name' => 'Сделка'
        ],
        [
            'value' => 'Contact',
            'name' => 'Контакт'
        ],
        [
            'value' => 'Company',
            'name' => 'Компания'
        ],
        [
            'value' => '31',
            'name' => 'Счета'
        ],
       
    ],
    "array_entities_value" => [],
    'button_actions' =>
    [
        'id' => [],
        'business_processes' => [
            "options" => [],
            "value" => []
        ],
        'document_templates' => [
            "options" => [],
            "value" => []
        ],
        'lists' => [
            "options" => [],
            "value" => []
        ],
        'fields_table' => [],
        "optionsEntity" => [
            [
                "name" => "шаблон 1",
                "value" => "BP1_1"
            ],
            [
                "name" => "шаблон 2",
                "value" => "BP1_2"
            ],
            [
                "name" => "шаблон 3",
                "value" => "BP1_3"
            ],
            [
                "name" => "шаблон 4",
                "value" => "BP1_4"
            ]
        ],
        'link' => '',
        'button_in_CRM' => false
    ]
];

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
        array_push($row['array_entities'], ["name" => $SP['title'], "value" => $SP['entityTypeId'] ]);
    }
    

echo json_encode([
    'row' => $row,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
