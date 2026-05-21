<?php
// api/button-actions/getFeedWorkflowsList.php

$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// Получаем все бизнес-процессы для ленты новостей
$result = overCRest::call('bizproc.workflow.template.list', [
    'select' => ['ID', 'NAME', 'MODULE_ID', 'ENTITY', 'DOCUMENT_TYPE'],
    'filter' => [
        'MODULE_ID' => 'lists',
    ]
]);

$workflows = [];
if (!isset($result['error']) && !empty($result['result'])) {
    foreach ($result['result'] as $workflow) {
        // Получаем название списка для DOCUMENT_TYPE
        $listName = '';
        if (isset($workflow['DOCUMENT_TYPE'][2])) {
            $listInfo = overCRest::call('lists.get', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $workflow['DOCUMENT_TYPE'][2]
            ]);
            if (!empty($listInfo['result'][0]['NAME'])) {
                $listName = ' (' . $listInfo['result'][0]['NAME'] . ')';
            }
        }
        
        $workflows[] = [
            'id' => $workflow['ID'],
            'name' => $workflow['NAME'] . $listName,
            'document_type' => $workflow['DOCUMENT_TYPE'] ?? '',
            'iblock_id' => $workflow['DOCUMENT_TYPE'][2] ?? null
        ];
    }
}

echo json_encode([
    'result' => $workflows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>