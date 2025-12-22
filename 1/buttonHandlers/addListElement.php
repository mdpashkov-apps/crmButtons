<?
$entityBody = file_get_contents('php://input');

$requestData = json_decode($entityBody, true);
$path =  pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$id = $requestData['id'];
$idEntity = $requestData['idEntity'];
$entity = $requestData['entity'];
$fieldsList = $requestData['fieldsList'];
$entity = mb_strtolower($entity);

$entity_list = ["lead", "deal", "contact", "company"];
if (in_array($entity, $entity_list) )
{
$result = overCRest::call('crm.'.$entity.'.get', [
	'id' => (int)$idEntity
])['result'];	
}
else {
    $result = overCRest::call('crm.item.get', [
        'id' => (int)$idEntity,
        'entityTypeId' => $entity
    ]
    
    )['result']['item'];	
}	
    file_put_contents(__DIR__.'/result.log', var_export( $result, 1), FILE_APPEND);

$entityFieldValue = [];
$entityFieldValueTypeList = [];
$entityFieldValueNAME = [];
foreach ($fieldsList[0] as $key => $elem){
    $entityFieldValue[] = $result[$elem];	
    $entityFieldValueTypeList[] = $elem;

}
$element_code = random_string(32);
$fields = [];
$test = 0;
$selectedListValues = [];
foreach ($fieldsList[1] as $key => $elem){
    file_put_contents(__DIR__.'/log.txt',var_export($entityFieldValue[$key],1),FILE_APPEND);
    if($elem == 'ACTIVE_FROM' || $elem == 'ACTIVE_TO'){
        $date = new \DateTime($entityFieldValue[$key]);
        $newDate = $date->format('d.m.Y H:i:s');
        $fields[$elem] = $newDate;
    } else{
        if(is_array($entityFieldValue[$key])){
            if(array_key_exists('id',$entityFieldValue[$key])){
                $fields[$elem] = (string)$entityFieldValue[$key]['id'];
            }            
        } else{
            if($fieldsList[2][$key] == true){
                $valueTheListField = overCRest::call('crm.'.$entity.'.list', [
                    'filter'=> [
                        'ID' => $idEntity
                    ],
                    'select' => [$entityFieldValueTypeList[$key]]
                ])['result'][0][$entityFieldValueTypeList[$key]];
                $entityFieldValueNAME = overCRest::call('crm.'.$entity.'.userfield.list', [
                    'filter'=> [
                        'FIELD_NAME' => $entityFieldValueTypeList[$key]
                    ]
                ])['result'];
                $entityFieldValueList = $entityFieldValueNAME[0]['LIST'];
                foreach($entityFieldValueList as $element){ 
                    if(in_array($element['ID'],$valueTheListField)){
                        $selectedListValues[] =$element['VALUE'];
                    }
                }
                $valueOfListOfList = overCRest::call('lists.field.get', [
                    'IBLOCK_TYPE_ID' => 'lists',
                    'IBLOCK_ID' => $id,
                    'FIELD_ID' => $elem
                ])['result']['L']['DISPLAY_VALUES_FORM'];
                $idOfTheSelectedItems = [];
                foreach($valueOfListOfList as $key => $element){ 
                    if(in_array($element,$selectedListValues)){
                        $idOfTheSelectedItems[] =$key;
                    }
                }
                $fields[$elem] = $idOfTheSelectedItems;

            }else{
                $fields[$elem] = $entityFieldValue[$key];

            }
            
        }
    } 
}


$resultLists = overCRest::call('lists.element.add', [
	'IBLOCK_TYPE_ID' => 'lists',
    'IBLOCK_ID' => $id,
    'ELEMENT_CODE' => $element_code,
	'FIELDS' => $fields
]);	
echo json_encode([
    'result' => [$resultLists,$valueOfListOfList,$idOfTheSelectedItems]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);





function random_string($str_length)
{
    $str_characters = array('a', 'b', 'b', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y','A', 'B', 'B', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y','0','1','2','3','4','5','6','7','8','9');

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

