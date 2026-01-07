<?
$templateParam = [
    'buttonName_FIELDS' => 'Новая кнопка 1',
    'buttonColor_FIELDS' => '#000000',
    'textColor_FIELDS' => '#ffffff',
    'buttonRadius_FIELDS' => 5,
    'textOnTheButton_FIELDS' => 'Кнопка',
    'buttonBorder_FIELDS' => false,
    'buttonBorderWidth_FIELDS' => '5',
    'buttonBorderColor_FIELDS' => '#000000',
    'usingTheIcon_FIELDS' => true,
    'iconOnTheButton_FIELDS' => '',
    'buttonInCRM_FIELDS' => 'false',
];

echo json_encode([
    'result' => $templateParam,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);