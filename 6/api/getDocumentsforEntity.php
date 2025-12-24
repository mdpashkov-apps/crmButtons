<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$entity = $requestData['current_button']['value'];


$entityMap = [
    'Lead'    => 1,
    'Deal'    => 2,
    'Contact' => 3,
    'Company' => 4,
];



$entityTypeId = $entityMap[$entity];


$entityTypeIds = [];

if ($entityTypeId === 2) {

    $categoriesResponse = overCRest::call(
        'crm.category.list',
        [
            'entityTypeId' => 2
        ]
    );

    $categories = $categoriesResponse['result']['categories'] ?? [];

    foreach ($categories as $category) {
        $entityTypeIds[] = '2_category_' . $category['id'];
    }

} else {
    // Lead / Contact / Company
    $entityTypeIds[] = (string)$entityTypeId;
}


$allTemplates = [];

foreach ($entityTypeIds as $entityTypeId) {

    $response = overCRest::call(
        'crm.documentgenerator.template.list',
        [
            'select' => ['id', 'name'],
            'filter' => [
                'entityTypeId' => $entityTypeId
            ]
        ]
    );

    if (!empty($response['result']['templates'])) {
        $allTemplates = array_merge(
            $allTemplates,
            $response['result']['templates']
        );
    }
}


$finalResult = [];

foreach ($allTemplates as $tmp) {
    $finalResult[] = [
        'value' => $tmp['id'],
        'name'  => $tmp['name'],
    ];
}

echo json_encode([
    'result' => $finalResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
