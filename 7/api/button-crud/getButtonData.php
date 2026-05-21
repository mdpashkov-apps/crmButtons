<?php
// api/button-crud/getButtonData.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// ID кнопки, которую нужно получить
$searchableButtonID = $requestData['button_ID'];

// Пытается декодировать JSON-строку
function tryJsonDecode($value) {
    if (!is_string($value)) {
        return $value;
    }
    $value = trim($value);
    // быстрый отсев
    if ($value === '' || ($value[0] !== '{' && $value[0] !== '[')) {
        return $value;
    }
    $decoded = json_decode($value, true);
    return (json_last_error() === JSON_ERROR_NONE)
        ? $decoded
        : $value;
}

// получаю данные по кнопке
$result = overCRest::call("entity.item.get", [
    "ENTITY" => "customButton",
    'FILTER' => [
        'ID' => $searchableButtonID,
    ]
]);
$button = $result['result'][0]['PROPERTY_VALUES'];

// Инициализируем поля, если их нет
if (!isset($button['buttonInChat_FIELDS'])) {
    $button['buttonInChat_FIELDS'] = 'false';
}
if (!isset($button['chatCommandId_FIELDS'])) {
    $button['chatCommandId_FIELDS'] = '';
}

if (is_string($button['entitySelection_FIELDS'])) {
    $entityMap = [
        'Lead'    => 'Лид',
        'Deal'    => 'Сделка',
        'Contact' => 'Контакт',
        'Company' => 'Компания',
        '31'      => 'Счёт',
        'chat_bot'=> '🤖 Бот в чате',
    ];

    $rawValue = trim($button['entitySelection_FIELDS']);

    // 1. Стандартные сущности и бот в чате
    if (isset($entityMap[$rawValue])) {
        $button['entitySelection_FIELDS'] = json_encode([
            'value' => $rawValue,
            'name'  => $entityMap[$rawValue],
        ], JSON_UNESCAPED_UNICODE);

    }
    // 2. Число, но не стандартная сущность → смарт-процесс
    elseif (ctype_digit($rawValue)) {

        $totalSP = overCRest::call('crm.type.list', [])['total'];
        $listSP  = (int)ceil($totalSP / 50);

        $batchArrSP = [];
        for ($i = 0; $i < $listSP; $i++) {
            $batchArrSP[(int)($i / 49)]['list_' . $i] = [
                'method' => 'crm.type.list',
                'params' => [
                    'select' => ['ENTITY_TYPE_ID', 'TITLE'],
                    'start'  => $i * 50,
                ],
            ];
        }

        $found = false;
        foreach ($batchArrSP as $cmdSPArr) {
            sleep(2);
            $batchResult = overCRest::callBatch($cmdSPArr, false)['result']['result'];
            foreach ($batchResult as $chunk) {
                foreach ($chunk['types'] as $SP) {
                    if ((string)$SP['entityTypeId'] === $rawValue) {
                        $button['entitySelection_FIELDS'] = json_encode([
                            'value' => (int)$SP['entityTypeId'],
                            'name'  => $SP['title'],
                        ], JSON_UNESCAPED_UNICODE);
                        $found = true;
                        break 3;
                    }
                }
            }
        }
    }
}

$bpRaw = $button['businessProcessesValue_FIELDS'] ?? '';
$bpDecoded = json_decode($bpRaw, true);

$needLoadBpName =
    is_array($bpDecoded)
    && isset($bpDecoded[0])
    && !isset($bpDecoded[0]['value']);

if ($needLoadBpName && !empty($button['entitySelection_FIELDS'])) {

    $entityData = json_decode($button['entitySelection_FIELDS'], true);
    $entity     = $entityData['value'];

    $documentTypeMap = [
        'Lead'    => 'LEAD',
        'Deal'    => 'DEAL',
        'Contact' => 'CONTACT',
        'Company' => 'COMPANY',
        '31'      => 'SMART_INVOICE',
    ];

    if (is_numeric($entity)) {
        $documentType = 'DYNAMIC_' . $entity;
    } else {
        $documentType = $documentTypeMap[$entity] ?? null;
    }

    if ($documentType) {

        $bpId = (int)$bpDecoded[0];

        $bpList = overCRest::call(
            'bizproc.workflow.template.list',
            [
                'select' => ['ID', 'NAME'],
                'filter' => [
                    'MODULE_ID'     => 'crm',
                    'DOCUMENT_TYPE' => $documentType,
                    'ID'            => $bpId,
                ],
            ]
        );

        if (!empty($bpList['result'][0])) {
            $button['businessProcessesValue_FIELDS'] = json_encode([
                [
                    'value' => $bpId,
                    'name'  => $bpList['result'][0]['NAME'],
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

$linkRaw = $button['crmLinkFields_FIELDS'] ?? '';
$linkDecoded = json_decode($linkRaw, true);

$needLoadLinkName =
    is_string($linkRaw)
    || (is_array($linkDecoded) && !isset($linkDecoded['value']));

if ($needLoadLinkName && !empty($button['entitySelection_FIELDS'])) {

    $entityData = json_decode($button['entitySelection_FIELDS'], true);
    $entity     = $entityData['value'];
    $fieldCode  = is_string($linkRaw) ? $linkRaw : ($linkDecoded['value'] ?? null);

    if ($fieldCode) {

        if (is_numeric($entity) || $entity === '31') {

            $getEntityFields = overCRest::call('crm.item.fields', [
                'entityTypeId' => (int)$entity
            ]);

            if (!empty($getEntityFields['result']['fields'][$fieldCode])) {
                $field = $getEntityFields['result']['fields'][$fieldCode];

                $button['crmLinkFields_FIELDS'] = json_encode([
                    'value' => $fieldCode,
                    'name'  => $field['title'],
                ], JSON_UNESCAPED_UNICODE);
            }

        } else {

            $getEntityFields = overCRest::call('crm.' . strtolower($entity) . '.fields', []);

            if (!empty($getEntityFields['result'][$fieldCode])) {
                $field = $getEntityFields['result'][$fieldCode];

                $button['crmLinkFields_FIELDS'] = json_encode([
                    'value' => $fieldCode,
                    'name'  => $field['formLabel'],
                ], JSON_UNESCAPED_UNICODE);
            }
        }
    }
}

/* ===================== DOCUMENT TEMPLATES ===================== */

$docRaw = $button['documentTemplatesValue_FIELDS'] ?? '';
$docDecoded = json_decode($docRaw, true);

$needLoadDocName =
    is_array($docDecoded)
    && isset($docDecoded[0])
    && !isset($docDecoded[0]['value']);

if ($needLoadDocName && !empty($button['entitySelection_FIELDS'])) {

    $templateId = (int)$docDecoded[0];

    $entityData = json_decode($button['entitySelection_FIELDS'], true);
    $entity     = $entityData['value'];

    $entityMap = [
        'Lead'    => 1,
        'Deal'    => 2,
        'Contact' => 3,
        'Company' => 4,
    ];

    $entityTypeId = $entityMap[$entity] ?? $entity;

    $entityTypeIds = [];

    if ((string)$entityTypeId === '2') {

        $categoriesResponse = overCRest::call('crm.category.list', [
            'entityTypeId' => 2
        ]);

        $categories = $categoriesResponse['result']['categories'] ?? [];

        foreach ($categories as $category) {
            $entityTypeIds[] = '2_category_' . $category['id'];
        }

    } else {
        $entityTypeIds[] = (string)$entityTypeId;
    }

    foreach ($entityTypeIds as $entityTypeId) {

        $list = overCRest::call(
            'crm.documentgenerator.template.list',
            [
                'select' => ['id', 'name'],
                'filter' => [
                    'entityTypeId' => $entityTypeId
                ],
            ]
        );

        if (empty($list['total']) || empty($list['result']['templates'])) {
            continue;
        }

        $pages = ceil($list['total'] / 50);

        for ($i = 0; $i < $pages; $i++) {

            $page = overCRest::call(
                'crm.documentgenerator.template.list',
                [
                    'select' => ['id', 'name'],
                    'filter' => [
                        'entityTypeId' => $entityTypeId
                    ],
                    'start' => $i * 50,
                ]
            );

            foreach ($page['result']['templates'] ?? [] as $tpl) {

                if ((int)$tpl['id'] === $templateId) {

                    $button['documentTemplatesValue_FIELDS'] = json_encode([
                        [
                            'value' => (string)$tpl['id'],
                            'name'  => $tpl['name'],
                        ]
                    ], JSON_UNESCAPED_UNICODE);

                    break 3;
                }
            }
        }
    }
}

/* ===================== LISTS ===================== */

$listsRaw = $button['listsValue_FIELDS'] ?? '';
$listsDecoded = json_decode($listsRaw, true);

$needLoadListName =
    is_string($listsRaw)
    || (is_array($listsDecoded) && !isset($listsDecoded['value']));

if ($needLoadListName && !empty($listsRaw)) {

    $listId = is_string($listsRaw)
        ? (int)$listsRaw
        : (int)($listsDecoded['value'] ?? 0);

    if ($listId > 0) {

        $listsGet = overCRest::call("lists.get", [
            "IBLOCK_TYPE_ID" => "lists",
        ]);

        $total = $listsGet['total'] ?? 0;
        $pages = (int)ceil($total / 50);

        $batch = [];
        for ($i = 0; $i < $pages; $i++) {
            $batch["lists_{$i}"] = [
                'method' => 'lists.get',
                'params' => [
                    'IBLOCK_TYPE_ID' => 'lists',
                    'start' => $i * 50,
                ],
            ];
        }

        $batchChunks = array_chunk($batch, 50, true);
        foreach ($batchChunks as $chunk) {

            sleep(2);
            $result = overCRest::callBatch($chunk, false)['result']['result'];

            foreach ($result as $lists) {
                foreach ($lists as $list) {

                    if ((int)$list['ID'] === $listId) {

                        $button['listsValue_FIELDS'] = json_encode([
                            'value' => (string)$list['ID'],
                            'name'  => $list['NAME'],
                        ], JSON_UNESCAPED_UNICODE);

                        break 3;
                    }
                }
            }
        }
    }
}

//Проходим по всем свойствам кнопки и пробуем декодировать json строки в массив
foreach ($button as $key => $value) {
    $button[$key] = tryJsonDecode($value);
}

// возвращаем данные на фронт
echo json_encode([
    'result' => $button,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);