<?
// $entityBody = file_get_contents('php://input');
// $requestData = json_decode($entityBody, true);
// $memberId = $requestData['memberId'];
// $path = pathinfo(__DIR__, PATHINFO_DIRNAME);
// include_once($path . '/overCRest.php');
// overCRest::setCurrentBitrix24($memberId);








$entityBody = file_get_contents('php://input');

$requestData = json_decode($entityBody, true);

$id = $requestData['activeButtonId'];
$domen = $requestData['domen'];
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);



// file_put_contents(__DIR__.'/result91.log', var_export($requestData, true), FILE_APPEND);


// $arr = [];
// $resultId;
// $result_entity = overCRest::call('entity.item.get', [
//     'ENTITY' => 'customButton',
//     'FILTER' => ['ID' => $id]
// ])['result'][0]['PROPERTY_VALUES'];
// if ($result_entity['customField_FIELDS'] == '') {
//     $newPath = pathinfo($path, PATHINFO_DIRNAME);
//     $bytes = random_string(32);
//     $file = $domen . '_button|' . $id . '|.php';
//     $file_path = $newPath.'/fieldTypeHandlers/' . $file;
//     $versions = basename(dirname(__DIR__));
//     file_put_contents($file_path, '<? include("../'.$versions.'/buttonHandlers/button.php");');
//     $resultId = overCRest::call('userfieldtype.add', [
//         'USER_TYPE_ID' => $bytes,
//         'HANDLER' => 'https://app.overplan.ru/applications/crmButtons/fieldTypeHandlers/' . $file,
//         'TITLE' => 'Кнопка - ' . $result_entity['buttonName_FIELDS'],
//         'DESCRIPTION' => 'Приложение по добавлению кнопки',
//         'OPTIONS' => ['height' => 65]
//     ]);
//     $arr['customField_FIELDS'] = $bytes;
// }
// $arr['buttonInCRM_FIELDS'] = 'true';
// $result = overCRest::call('entity.item.update', [
//     'ENTITY' => 'customButton',
//     'ID' => $id,
//     'PROPERTY_VALUES' => $arr
// ]);

// // Пишем содержимое обратно в файл

// echo json_encode([
//     'error' =>  $result_entity,
// ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


// function random_string($str_length)
// {
//     $str_characters = array('a', 'b', 'b', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y');

//     // Возвращаем ложь, если первый параметр равен нулю или не является целым числом
//     if (!is_int($str_length) || $str_length < 0) {
//         return false;
//     }

//     // Подсчитываем реальное количество символов, участвующих в формировании случайной строки и вычитаем 1
//     $characters_length = count($str_characters) - 1;

//     // Объявляем переменную для хранения итогового результата
//     $string = '';

//     // Формируем случайную строку в цикле
//     for ($i = $str_length; $i > 0; $i--) {
//         $string .= $str_characters[mt_rand(0, $characters_length)];
//     }

//     // Возвращаем результат
//     return $string;
// }
