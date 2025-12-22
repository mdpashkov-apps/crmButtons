<?php

// $entityBody = file_get_contents('php://input');
// $requestData = json_decode($entityBody, true);
// $allFields = $requestData['allFields'];
// $domen = $requestData['domen'];
// $path = pathinfo(__DIR__, PATHINFO_DIRNAME);
// include_once ($path . '/overCRest.php');
// $memberId = $requestData['memberId'];
// overCRest::setCurrentBitrix24($memberId);
// $result = [];
// $business_processes = [];
// $document_templates = [];
// $fields_table = [];
// $listValue = '';
// foreach ($allFields['button_actions']['business_processes']['value'] as $elem) {
//     $business_processes[] = $elem['value'];
// }
// foreach ($allFields['button_actions']['document_templates']['value'] as $elem) {
//     $document_templates[] = $elem['value'];
// }
// $crmLinkFilds = $allFields['button_actions']['crmLinkFilds']['value'];
// foreach ($allFields['button_actions']['fields_table'] as $elem) {
//     if ($elem['fieldsEntiyValue']['value'] == null) {
//         $elem['fieldsEntiyValue']['value'] = 'null';
//     }
//     if ($elem['fieldsLists']['value'] == null) {
//         $elem['fieldsLists']['value'] = 'null';
//     }
//     $fields_table[0][] = $elem['fieldsEntiyValue']['value'];
//     $fields_table[1][] = $elem['fieldsLists']['value'];
//     $fields_table[2][] = $elem['fieldsLists']['list'];
// }

// if ($allFields['array_entities_value']['value'] == null) {
//     $allFields['array_entities_value']['value'] = '';
// }

// $PROPERTY_VALUES = [
//     'buttonName_FIELDS' => $allFields['name'],
//     'buttonColor_FIELDS' => $allFields['color_btn'],
//     'textColor_FIELDS' => $allFields['color_text'],
//     'buttonRadius_FIELDS' => $allFields['radius_btn'],
//     'buttonBorder_FIELDS' => json_encode($allFields['buttonBorderSelection']),
//     'buttonBorderWidth_FIELDS' => $allFields['buttonBorderWidth'],
//     'buttonBorderColor_FIELDS' => $allFields['buttonBorderColor'],
//     'textOnTheButton_FIELDS' => $allFields['text_btn'],
//     'usingTheIcon_FIELDS' => json_encode($allFields['use_icon']),
//     'iconOnTheButton_FIELDS' => $allFields['icon_btn'],
//     'entitySelection_FIELDS' => $allFields['array_entities_value']['value'],
//     'buttonActionsId_FIELDS' => json_encode($allFields['button_actions']['id']),
//     'businessProcessesValue_FIELDS' => json_encode($business_processes),
//     'documentTemplatesValue_FIELDS' => json_encode($document_templates),
//     'listsValue_FIELDS' => $allFields['button_actions']['lists']['value']['value'],  // поменял тут
//     'fieldsTable_FIELDS' => json_encode($fields_table),
//     'link_FIELDS' => $allFields['button_actions']['link'],
//     'buttonInCRM_FIELDS' => json_encode($allFields['button_actions']['button_in_CRM']),
//     'crmLinkFields_FIELDS' => $crmLinkFilds,
// ];

// if ($allFields['id'] < 0) {
//     $result = overCRest::call('entity.item.add', [
//         'ENTITY' => 'customButton',
//         'NAME' => $allFields['name'],
//         'PROPERTY_VALUES' => $PROPERTY_VALUES
//     ])['result'];
// } else {
//     $result = overCRest::call('entity.item.update', [
//         'ENTITY' => 'customButton',
//         'ID' => $allFields['id'],
//         'NAME' => $allFields['name'],
//         'PROPERTY_VALUES' => $PROPERTY_VALUES
//     ])['result'];
// }
// $file = '';
// $resultCustomField = '';
// $resultId = '';
// if ($allFields['button_actions']['button_in_CRM']) {
//     if ($allFields['id'] > 0) {
//         $resultCustomField = overCRest::call('entity.item.get', [
//             'ENTITY' => 'customButton',
//             'FILTER' => ['ID' => $allFields['id']]
//         ])['result'][0]['PROPERTY_VALUES']['customField_FIELDS'];
//         $file = $domen . '_button|' . $allFields['id'] . '|.php';
//     } else {
//         $resultCustomField = overCRest::call('entity.item.get', [
//             'ENTITY' => 'customButton',
//             'FILTER' => ['ID' => $result]
//         ])['result'][0]['PROPERTY_VALUES']['customField_FIELDS'];
//         $file = $domen . '_button|' . $result . '|.php';
//     }
//     $resultId = overCRest::call('userfieldtype.update', [
//         'USER_TYPE_ID' => $resultCustomField,
//         'HANDLER' => 'https://app.overplan.ru/applications/crmButtons/fieldTypeHandlers/' . $file,
//         'TITLE' => 'Кнопка - ' . $allFields['name'],
//         'DESCRIPTION' => 'Приложение по добавлению кнопки',
//     ]);
// }

// // Пишем содержимое обратно в файл

// echo json_encode([
//     'result' => $result,  // поменял тут
// ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);





$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$allFields = $requestData['allFields'];
$domen = $requestData['domen'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once ($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$result = [];


// $business_processes = [];
// $document_templates = [];
// $fields_table = [];
// $listValue = '';
// foreach ($allFields['button_actions']['business_processes']['value'] as $elem) {
//     $business_processes[] = $elem['value'];
// }
// foreach ($allFields['button_actions']['document_templates']['value'] as $elem) {
//     $document_templates[] = $elem['value'];
// }
// $crmLinkFilds = $allFields['button_actions']['crmLinkFilds']['value'];
// foreach ($allFields['button_actions']['fields_table'] as $elem) {
//     if ($elem['fieldsEntiyValue']['value'] == null) {
//         $elem['fieldsEntiyValue']['value'] = 'null';
//     }
//     if ($elem['fieldsLists']['value'] == null) {
//         $elem['fieldsLists']['value'] = 'null';
//     }
//     $fields_table[0][] = $elem['fieldsEntiyValue']['value'];
//     $fields_table[1][] = $elem['fieldsLists']['value'];
//     $fields_table[2][] = $elem['fieldsLists']['list'];
// }







$business_processes = [];
$document_templates = [];
$fields_table = [];
$listValue = '';
$crmLinkFilds = '';

if (!empty($allFields['button_actions']['business_processes']['value'])
    && is_array($allFields['button_actions']['business_processes']['value'])) {
    foreach ($allFields['button_actions']['business_processes']['value'] as $elem) {
        if (isset($elem['value'])) {
            $business_processes[] = $elem['value'];
        }
    }
}

if (!empty($allFields['button_actions']['document_templates']['value'])
    && is_array($allFields['button_actions']['document_templates']['value'])) {
    foreach ($allFields['button_actions']['document_templates']['value'] as $elem) {
        if (isset($elem['value'])) {
            $document_templates[] = $elem['value'];
        }
    }
}

if (!empty($allFields['button_actions']['crmLinkFilds']['value'])) {
    $crmLinkFilds = $allFields['button_actions']['crmLinkFilds']['value'];
}

if (!empty($allFields['button_actions']['fields_table'])
    && is_array($allFields['button_actions']['fields_table'])) {
    foreach ($allFields['button_actions']['fields_table'] as $elem) {
        $fields_table[0][] = $elem['fieldsEntiyValue']['value'] ?? 'null';
        $fields_table[1][] = $elem['fieldsLists']['value'] ?? 'null';
        $fields_table[2][] = $elem['fieldsLists']['list'] ?? 'null';
    }
}



if ($allFields['array_entities_value']['value'] == null) {
    $allFields['array_entities_value']['value'] = '';
}

$PROPERTY_VALUES = [
    'buttonName_FIELDS' => $allFields['name'],
    'buttonColor_FIELDS' => $allFields['color_btn'],
    'textColor_FIELDS' => $allFields['color_text'],
    'buttonRadius_FIELDS' => $allFields['radius_btn'],
    'buttonBorder_FIELDS' => json_encode($allFields['buttonBorderSelection']),
    'buttonBorderWidth_FIELDS' => $allFields['buttonBorderWidth'],
    'buttonBorderColor_FIELDS' => $allFields['buttonBorderColor'],
    'textOnTheButton_FIELDS' => $allFields['text_btn'],
    'usingTheIcon_FIELDS' => json_encode($allFields['use_icon']),
    'iconOnTheButton_FIELDS' => $allFields['icon_btn'],
    'entitySelection_FIELDS' => $allFields['array_entities_value']['value'],
    'buttonActionsId_FIELDS' => json_encode($allFields['button_actions']['id']),
    'businessProcessesValue_FIELDS' => json_encode($business_processes),
    'documentTemplatesValue_FIELDS' => json_encode($document_templates),
    // 'listsValue_FIELDS' => $allFields['button_actions']['lists']['value']['value'],  // поменял тут
        'listsValue_FIELDS' => $allFields['button_actions']['lists']['value']['value'] ?? '',

    'fieldsTable_FIELDS' => json_encode($fields_table),
    'link_FIELDS' => $allFields['button_actions']['link'],
    'buttonInCRM_FIELDS' => json_encode($allFields['button_actions']['button_in_CRM']),
    'crmLinkFields_FIELDS' => $crmLinkFilds,
];

if ($allFields['id'] < 0) {
    $result = overCRest::call('entity.item.add', [
        'ENTITY' => 'customButton',
        'NAME' => $allFields['name'],
        'PROPERTY_VALUES' => $PROPERTY_VALUES
    ])['result'];
} else {
    $result = overCRest::call('entity.item.update', [
        'ENTITY' => 'customButton',
        'ID' => $allFields['id'],
        'NAME' => $allFields['name'],
        'PROPERTY_VALUES' => $PROPERTY_VALUES
    ])['result'];
}
$file = '';
$resultCustomField = '';
$resultId = '';
if ($allFields['button_actions']['button_in_CRM']) {
    if ($allFields['id'] > 0) {
        $resultCustomField = overCRest::call('entity.item.get', [
            'ENTITY' => 'customButton',
            'FILTER' => ['ID' => $allFields['id']]
        ])['result'][0]['PROPERTY_VALUES']['customField_FIELDS'];
        $file = $domen . '_button|' . $allFields['id'] . '|.php';
    } else {
        $resultCustomField = overCRest::call('entity.item.get', [
            'ENTITY' => 'customButton',
            'FILTER' => ['ID' => $result]
        ])['result'][0]['PROPERTY_VALUES']['customField_FIELDS'];
        $file = $domen . '_button|' . $result . '|.php';
    }
    $resultId = overCRest::call('userfieldtype.update', [
        'USER_TYPE_ID' => $resultCustomField,
        'HANDLER' => 'https://app.overplan.ru/applications/crmButtons/fieldTypeHandlers/' . $file,
        'TITLE' => 'Кнопка - ' . $allFields['name'],
        'DESCRIPTION' => 'Приложение по добавлению кнопки',
    ]);
}

// Пишем содержимое обратно в файл

echo json_encode([
    'result' => $result,  // поменял тут
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


