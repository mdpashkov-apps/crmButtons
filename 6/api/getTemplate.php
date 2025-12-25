<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

$row = [
    'buttonName_FIELDS' => 'Новая кнопка 1',
    'buttonColor_FIELDS' => '#000000',
    'textColor_FIELDS' => '#ffffff',
    'buttonRadius_FIELDS' => 5,
    'textOnTheButton_FIELDS' => 'Кнопка',
    'buttonBorder_FIELDS' => 'false',
    'buttonBorderWidth_FIELDS' => '5',
    'buttonBorderColor_FIELDS' => '#000000',
    'usingTheIcon_FIELDS' => 'true',
    'iconOnTheButton_FIELDS' => '',
];


// file_put_contents(__DIR__.'/result91.log', var_export($result, true), FILE_APPEND);


echo json_encode([
    'result' => $row,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);