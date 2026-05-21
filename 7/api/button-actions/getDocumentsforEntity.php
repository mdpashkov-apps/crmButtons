<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$entity = $requestData['current_button']['value'];
// Соответствие названий сущностей Bitrix24 их числовым ID
$entityMap = [
    'Lead'    => 1,
    'Deal'    => 2,
    'Contact' => 3,
    'Company' => 4,
];
// Получаем entityTypeId: либо из мапы, либо используем значение как есть
$entityTypeId = $entityMap[$entity] ?? $entity;

// Массив всех entityTypeId, по которым будем искать шаблоны документов
$entityTypeIds = [];

// Проверяем, является ли это смарт-процессом (числовой ID больше 4).
// Предложение (7) и Счёт (31) — числовые, но НЕ смарт-процессы с воронками.
$nonSmartNumericIds = ['7', '31'];
$isSmartProcess = is_numeric($entityTypeId) && $entityTypeId > 4
    && !in_array((string)$entityTypeId, $nonSmartNumericIds, true);

if ($entityTypeId == 2 || $isSmartProcess) {
    // Для сделок и смарт-процессов получаем список категорий
    $categoriesResponse = overCRest::call('crm.category.list', [
        'entityTypeId' => $entityTypeId
    ]);
    
    $categories = $categoriesResponse['result']['categories'] ?? [];
    
    if (!empty($categories)) {
        foreach ($categories as $category) {
            if ($entityTypeId == 2) {
                // Для сделок специальный формат
                $entityTypeIds[] = '2_category_' . $category['id'];
            } else {
                // Для смарт-процессов формат: ENTITY_TYPE_ID_CATEGORY_ID
                $entityTypeIds[] = $entityTypeId . '_' . $category['id'];
            }
        }
    } else {
        // Если нет категорий, используем просто entityTypeId
        $entityTypeIds[] = (string)$entityTypeId;
    }
} else {
    // Для остальных сущностей используем просто ID
    $entityTypeIds[] = (string)$entityTypeId;
}

// Итоговый список шаблонов документов
$documents = [];

foreach ($entityTypeIds as $currentEntityTypeId) {
    // Получаем общее количество шаблонов документов
    $response = overCRest::call('crm.documentgenerator.template.list', [
        'filter' => [
            'entityTypeId' => $currentEntityTypeId
        ],
    ]);
    
    $total = $response['total'] ?? 0;

    if ($total <= 0) {
        continue;
    }

    $pages = ceil($total / 50);
    $batch = [];

    for ($i = 0; $i < $pages; $i++) {
        $batch["list_{$i}"] = [
            'method' => 'crm.documentgenerator.template.list',
            'params' => [
                'select' => ['id', 'name'],
                'filter' => [
                    'entityTypeId' => $currentEntityTypeId
                ],
                'start' => $i * 50,
            ],
        ];
    }

    $batchChunks = array_chunk($batch, 50, true);
    foreach ($batchChunks as $chunk) {
        sleep(2); // щадящий режим
        $result = overCRest::callBatch($chunk, false);
        
        if (isset($result['result']['result'])) {
            foreach ($result['result']['result'] as $page) {
                if (empty($page['templates'])) {
                    continue;
                }
                foreach ($page['templates'] as $tpl) {
                    $documents[] = [
                        'value' => $tpl['id'],
                        'name'  => $tpl['name'],
                    ];
                }
            }
        }
    }
}

// Удаляем дубликаты по ID
$uniqueDocuments = [];
foreach ($documents as $doc) {
    $uniqueDocuments[$doc['value']] = $doc;
}
$documents = array_values($uniqueDocuments);

echo json_encode([
    'result' => $documents,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);