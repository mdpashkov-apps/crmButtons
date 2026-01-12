<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = $requestData['activeButtonId'];
$domen = $requestData['domen'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);


if ($requestData['activeButtonId'] === null) {

// получаем массив настроек которые надо сохранить в entity для кнопки
$btnSettings = $requestData['btnSettings'];

/*
| С фронта fieldsTable_FIELDS приходит как массив строк таблицы,
| где каждая строка содержит:
|  - value            → поле списка
|  - entField.value   → связанное CRM-поле (может отсутствовать)
|
| На выходе формируем компактную структуру из двух массивов:
|  [0] → CRM-поля
|  [1] → поля списка
| Индексы массивов совпадают и образуют пары
*/

$crmFields  = []; // массив CRM-полей
$listFields = []; // массив полей списка

foreach ($btnSettings['fieldsTable_FIELDS'] as $row) {

    // если CRM-поле выбрано — сохраняем его value
    // если не выбрано — сохраняем строку "null"
    if (
        isset($row['entField']) &&
        is_array($row['entField']) &&
        isset($row['entField']['value'])
    ) {
        $crmFields[] = $row['entField']['value'];
    } else {
        $crmFields[] = "null";
    }

    // поле списка (всегда есть)
    // берём value поля списка
    $listFields[] = $row['value'];
}

// перезаписываем fieldsTable_FIELDS в виде:
// [
//   [CRM_FIELD_1, CRM_FIELD_2, ...],
//   [LIST_FIELD_1, LIST_FIELD_2, ...]
// ]


$btnSettings['fieldsTable_FIELDS'] = [
    $crmFields,
    $listFields
];

//массивы преобразуем в JSON-строки

function encodeAllArrays(array $data): array
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
            );
        }
    }
    return $data;
}

$btnSettings = encodeAllArrays($btnSettings);

// искомый элемент (кнопка)
$searchableButtonID = $requestData['activeButtonId'];

// если пришло id кнопки, то уже есть такая кнопка и надо обновить настройки элемента
if ($searchableButtonID) {
    $updateItem = overCRest::call('entity.item.update', [
        'ENTITY' => 'customButton',
        'ID' => $searchableButtonID,
        'PROPERTY_VALUES' => $btnSettings
    ]);
// иначе создаем новый элемент
} else {
    $itemAdd = overCRest::call("entity.item.add", [
        "ENTITY" => "customButton",
        'NAME' => $btnSettings['buttonName_FIELDS'],
        'PROPERTY_VALUES' => $btnSettings
    ]);

    // возвращаем созданную кнопку
    echo json_encode([
        'result' => $itemAdd['result'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
$id = $itemAdd['result'];

}


// Массив для обновления свойств кнопки
$arr = [];

// Получаем данные кнопки
$getButton = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['ID' => $id]
])['result'][0]['PROPERTY_VALUES'];

// Если пользовательское поле еще не создано
if ($getButton['customField_FIELDS'] == '') {
    // Задаем путь к папке с обработчиками типов полей, генерируем уникальный USER_TYPE_ID
    $newPath = pathinfo($path, PATHINFO_DIRNAME);
    $bytes = random_string(32);
    // Задаем имя PHP-файла обработчика
    $file = $domen . '_button|' . $id . '|.php';
    $file_path = $newPath.'/fieldTypeHandlers/' . $file;
    // Получаем версию приложения (папка версии) и создаем PHP-файл обработчика пользовательского поля
    $versions = basename(dirname(__DIR__));
    file_put_contents($file_path, '<? include("../6/buttonHandlers/button.php");');

    // Регистрируем новый тип пользовательского поля в Bitrix24
    $addBtnInCrm = overCRest::call('userfieldtype.add', [
        'USER_TYPE_ID' => $bytes,
        'HANDLER' => 'https://app.overplan.ru/applications/crmButtons/fieldTypeHandlers/' . $file,
        'TITLE' => 'Кнопка - ' . $getButton['buttonName_FIELDS'],
        'DESCRIPTION' => 'Приложение по добавлению кнопки',
        'OPTIONS' => ['height' => 65]
    ]);
    // Сохраняем идентификатор пользовательского поля в сущность кнопки
    $arr['customField_FIELDS'] = $bytes;
}

// Отмечаем, что кнопка добавлена в CRM и обновляем запись
$arr['buttonInCRM_FIELDS'] = 'true';
$updateItem = overCRest::call('entity.item.update', [
    'ENTITY' => 'customButton',
    'ID' => $id,
    'PROPERTY_VALUES' => $arr
]);

echo json_encode([
    'error' =>  $getButton,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

/**
 * Генерация случайной строки
 * Используется для создания USER_TYPE_ID пользовательского поля
 */
function random_string($str_length)
{
    $str_characters = array('a', 'b', 'b', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y');

    // Возвращаем ложь, если первый параметр равен нулю или не является целым числом
    if (!is_int($str_length) || $str_length < 0) {
        return false;
    }
    // Подсчитываем реальное количество символов, участвующих в формировании случайной строки и вычитаем 1
    $characters_length = count($str_characters) - 1;
    // Объявляем переменную для хранения итогового результата
    $string = '';
    // Формируем случайную строку в цикле
    for ($i = $str_length; $i > 0; $i--) {
        $string .= $str_characters[mt_rand(0, $characters_length)];
    }
    // Возвращаем результат
    return $string;
}
