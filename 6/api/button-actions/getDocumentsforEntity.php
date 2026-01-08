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
if ($entityTypeId === 2) {
    $categoriesResponse = overCRest::call('crm.category.list',[
        'entityTypeId' => 2
    ]);
    $categories = $categoriesResponse['result']['categories'];
    foreach ($categories as $category) {
        $entityTypeIds[] = '2_category_' . $category['id'];
    }
} else {
    $entityTypeIds[] = (string)$entityTypeId;
}
// Итоговый список шаблонов документов
$documents = [];
foreach ($entityTypeIds as $entityTypeId) {
    // Получаем общее количество шаблонов документов
    $total = overCRest::call('crm.documentgenerator.template.list',[
        'filter' => [
            'entityTypeId' => $entityTypeId
        ],
    ])['total'];

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
                    'entityTypeId' => $entityTypeId
                ],
                'start' => $i * 50,
            ],
        ];
    }

    $batchChunks = array_chunk($batch, 50, true);
    foreach ($batchChunks as $chunk) {
        sleep(2); // щадящий режим
        $result = overCRest::callBatch($chunk, false)['result']['result'];
        foreach ($result as $page) {
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

// Удаляем дубликаты и переиндексируем массив
$documents = array_values(
    array_unique($documents, SORT_REGULAR)
);

echo json_encode([
    'result' => $documents,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
