<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// получаю данные 1 хранилища
$result = overCRest::call("entity.item.add", [
    "ENTITY" => "customButton",
    'NAME' => 'Hello, world!',
    'PROPERTY_VALUES' => [
                    'buttonName_FIELDS'     => 12,

                ],

]);
// file_put_contents(__DIR__.'/result91.log', var_export($result, true), FILE_APPEND);


