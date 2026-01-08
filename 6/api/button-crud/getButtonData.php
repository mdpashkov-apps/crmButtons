<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);

// ID кнопки, которую нужно получить
$searchableButtonID = $requestData['button_ID'];

// Пытается декодировать JSON-строку
function tryJsonDecode($value) {
    if (!is_string($value)) {
        return $value;
    }
    $value = trim($value);
    // быстрый отсев
    if ($value === '' || ($value[0] !== '{' && $value[0] !== '[')) {
        return $value;
    }
    $decoded = json_decode($value, true);
    return (json_last_error() === JSON_ERROR_NONE)
        ? $decoded
        : $value;
}

// получаю данные по кнопке
$result = overCRest::call("entity.item.get", [
    "ENTITY" => "customButton",
    'FILTER' => [
        'ID' => $searchableButtonID,
    ]
]);
$button = $result['result'][0]['PROPERTY_VALUES'];

//Проходим по всем свойствам кнопки и пробуем декодировать json строки в массив
foreach ($button as $key => $value) {
    $button[$key] = tryJsonDecode($value);
}

// возвращаем данные на фронт
echo json_encode([
    'result' => $button,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);