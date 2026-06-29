<?php
// api/button-crud/getTemplate.php

$templateParam = [
    'buttonName_FIELDS' => 'Новая кнопка 1',
    'buttonColor_FIELDS' => '#2fc6f6',
    'textColor_FIELDS' => '#ffffff',
    'buttonRadius_FIELDS' => 5,
    'textOnTheButton_FIELDS' => 'Кнопка',
    'buttonBorder_FIELDS' => false,
    'buttonBorderWidth_FIELDS' => '1',
    'buttonBorderColor_FIELDS' => '#ffffff',
    'usingTheIcon_FIELDS' => false,
    'iconOnTheButton_FIELDS' => '',
    'buttonInCRM_FIELDS' => false,
    'buttonInChat_FIELDS' => false,
    'chatCommandId_FIELDS' => '',
    'buttonActionType_FIELDS' => 'url', // 'url' или 'workflow'
    'workflowFromFeed_FIELDS' => false,
    'workflowTemplateId_FIELDS' => null,
    'workflowDocumentId_FIELDS' => null
];

echo json_encode([
    'result' => $templateParam,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);