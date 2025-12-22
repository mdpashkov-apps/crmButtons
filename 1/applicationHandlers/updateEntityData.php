
<?php
$entityBPBody = file_get_contents('php://input');
$requestData = json_decode($entityBPBody, true);
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$valueEntity = $requestData['valueEntity'];
// $idList = $requestData['idList'];
$newData = [];
$entityBP = '';
$entityFields = '';
$resultDocument = [];
$optionsBP = [];
$optionsDocument = [];
$optionsLists = [];
// $optionsUserFields = [];
$optionsListFields = [];
switch ($valueEntity) {
    case 'Lead':
        $entityBP = 'CCrmDocumentLead';
        $entityFields = 'crm.lead.fields';
        $resultDocument = getContracts(0, ['1']);
        break;
    case 'Contact':
        $entityBP = 'CCrmDocumentContact';
        $entityFields = 'crm.contact.fields';
        $resultDocument = getContracts(0, ['3']);
        break;
    case 'Company':
        $entityBP = 'CCrmDocumentCompany';
        $entityFields = 'crm.company.fields';
        $resultDocument = getContracts(0, ['4']);
                    // file_put_contents(__DIR__.'/result.log', var_export( $resultDocument, 1), FILE_APPEND);

        break;
    case 'Deal':
        $entityBP = 'CCrmDocumentDeal';
        $entityFields = 'crm.deal.fields';
        $resultDocument = getContracts(1, ['2']);
        file_put_contents(__DIR__.'/result.log', var_export( $resultDocument, 1), FILE_APPEND);

        break;
    default:
    // file_put_contents(__DIR__.'/result.log', var_export($valueEntity, 1), FILE_APPEND);

          $current_SP = [];
          foreach ($resultSP['types'] as $SP) {
            if ($SP['entityTypeId'] == $valueEntity) {
                $current_SP = $SP;
            }
          }
         //получение бп смарт-процесса
        $entityBP = 'DYNAMIC_'. $valueEntity;
        $entityFields = 'crm.type.fields';
          // получение списка документов смарт-процесса
        $resultDocument = getContracts(1, [$valueEntity]);
        
        // вывод в приложении смарт-процессов
          $newObject['array_entities_value'] =
              [
                  "value" => $valueEntity,
                  "name" => $current_SP['title']
              ];
          break;       
}


$entity_list = ["CCrmDocumentLead", "CCrmDocumentDeal", "CCrmDocumentContact", "CCrmDocumentCompany"];
if (in_array($entityBP, $entity_list) ){
    // БП
    // Получаем список БП
    $bacthBP = [
       'method' => 'bizproc.workflow.template.list',
        'params' => [
            'select' => ['ID', 'NAME'],
            'filter' => ['ENTITY' => $entityBP]
        ]
    ];
    $totalBP = overCRest::call('bizproc.workflow.template.list', ['filter' => ['ENTITY' => $entityBP]])["total"];
    $listBP = ceil($totalBP / 50); //Количество необходимых листов +1 тк от нуля
    $bacthArrBP = [];
    for ($i = 0; $i < $listBP; $i++) {
        $batchParams = $bacthBP;
        $batchParams['params']['start'] =  $i * 50;
        $bacthArrBP[(int)($i / 49)]["list_" . $i] =  $batchParams;
    }
    $resultBP = [];
    foreach ($bacthArrBP as $key => $cmd_arr) {
        sleep(2); //Щадяший режим лучше ставить 2 секунды
        $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
        foreach ($batchResult as $elementBP) {
            $resultBP = array_merge($resultBP, $elementBP);
        }
    } 
}
else {
    $bacthBP = [
        'method' => 'bizproc.workflow.template.list',
         'params' => [
             'select' => ['ID', 'NAME'],
             'filter' => ['DOCUMENT_TYPE' => $entityBP]
         ]
     ];
     $totalBP = overCRest::call('bizproc.workflow.template.list', ['filter' => ['DOCUMENT_TYPE' => $entityBP]])["total"];
     $listBP = ceil($totalBP / 50); //Количество необходимых листов +1 тк от нуля
     $bacthArrBP = [];
     for ($i = 0; $i < $listBP; $i++) {
         $batchParams = $bacthBP;
         $batchParams['params']['start'] =  $i * 50;
         $bacthArrBP[(int)($i / 49)]["list_" . $i] =  $batchParams;
     }
     $resultBP = [];
     foreach ($bacthArrBP as $key => $cmd_arr) {
         sleep(2); //Щадяший режим лучше ставить 2 секунды
         $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
         foreach ($batchResult as $elementBP) {
             $resultBP = array_merge($resultBP, $elementBP);
         }
     } 

}

foreach ($resultBP as $key => $elem) {
    $optionsBP[] = ['value' => $elem['ID'], 'name' => $elem['NAME']];
}

// Документы
foreach ($resultDocument as $key => $elem) {
    $optionsDocument[] = ['value' => $elem['id'], 'name' => $elem['name']];
}
$totalUserFields = [];
// Если выбран список
$resultUserFields = [];
$resultListsTest = overCRest::call('lists.get', [
    'IBLOCK_TYPE_ID' => 'lists',
]);

$bacthLists = [
    'method' => 'lists.get',
    'params' => [
        'IBLOCK_TYPE_ID' => 'lists'
    ]
];
$totalLists = overCRest::call('lists.get', ['IBLOCK_TYPE_ID' => 'lists'])["total"];

$listLists = ceil($totalLists / 50); //Количество необходимых листов +1 тк от нуля
$bacthArrLists = [];
for ($i = 0; $i < $listLists; $i++) {
    $batchParams = $bacthLists;
    $batchParams['params']['start'] =  $i * 50;
    $bacthArrLists[(int)($i / 49)]["list_" . $i] =  $batchParams;
}
$resultLists = [];
foreach ($bacthArrLists as $key => $cmd_arr) {
    sleep(2); //Щадяший режим лучше ставить 2 секунды
    $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
    foreach ($batchResult as $elementLists) {
        $resultLists = array_merge($resultLists, $elementLists);
    }
}

foreach ($resultLists as $key => $elem) {
    $optionsLists[] = ['value' => $elem['ID'], 'name' => $elem['NAME']];
}

$newData["lists"] = [
    "options" => $optionsLists,
    "value" => null
];
$newData['businessProcesses'] = [
    "options" => $optionsBP,
    "value" => []
];
$newData['documentTemplates'] = [
    "options" => $optionsDocument,
    "value" => []
];

// Пишем содержимое обратно в файл

echo json_encode([
    'newData' => $newData,
    'test' => $resultLists,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


function getContracts($tip, $request)
{
    $result = [];
    if ($tip == 1) {
        $resultCategorys = overCRest::call('crm.category.list', [
            'entityTypeId' => $request[0]
        ])['result']['categories'];
        $resultBatchDocuments = [];
        foreach ($resultCategorys as $elem) {
              if ($request[0] == 2) {
                $ent = '2_category_' . (string)$elem['id'];
            }
            else {
                $ent = $request[0] . "_" . (string)$elem['id'];
            }
            $bacthDocument = [
                'method' => 'crm.documentgenerator.template.list',
                'params' => [
                    'filter' => ['entityTypeId' => $ent],
                    'select' => ['id', 'name', 'entityTypeId']
                ]
            ];
            $totalDocument = overCRest::call('crm.documentgenerator.template.list', ['filter' => ['entityTypeId' => $ent]])["total"];

            $listDocument = ceil($totalDocument / 50); //Количество необходимых листов +1 тк от нуля
            $bacthArrDocument = [];
            for ($i = 0; $i < $listDocument; $i++) {
                $batchParams = $bacthDocument;
                $batchParams['params']['start'] =  $i * 50;
                $bacthArrDocument[(int)($i / 49)]["list_" . $i] =  $batchParams;
            }
            foreach ($bacthArrDocument as $cmd_arr) {
                sleep(2); //Щадяший режим лучше ставить 2 секунды
                $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
                foreach ($batchResult as $elementDocument) {
                    $resultBatchDocuments = array_merge($resultBatchDocuments, $elementDocument['templates']);
                }
            }
        }
        $resultDocuments = [];
        foreach ($resultBatchDocuments as $element) {
            $resultDocuments[] = ['id' => $element['id'], 'name' => $element['name']];
        }
        $result = unique_multidim_array($resultDocuments, 'id');
    } else {
        $bacthDocument = [
            'method' => 'crm.documentgenerator.template.list',
            'params' => [
                'select' => ['id', 'name'],
                'filter' => ['entityTypeId' => $request]
            ]
        ];
        $totalDocument = overCRest::call('crm.documentgenerator.template.list', ['filter' => ['entityTypeId' => $request]])["total"];
        $listDocument = ceil($totalDocument / 50); //Количество необходимых листов +1 тк от нуля
        $bacthArrDocument = [];
        for ($i = 0; $i < $listDocument; $i++) {
            $batchParams = $bacthDocument;
            $batchParams['params']['start'] =  $i * 50;
            $bacthArrDocument[(int)($i / 49)]["list_" . $i] =  $batchParams;
        }
        foreach ($bacthArrDocument as $key => $cmd_arr) {
            sleep(2); //Щадяший режим лучше ставить 2 секунды
            $batchResult = overCRest::callBatch($cmd_arr, false)['result']['result'];
            foreach ($batchResult as $elementDocument) {
                $result = array_merge($result, $elementDocument['templates']);
            }
        }
    }
    return $result;
}


function unique_multidim_array($array, $key)
{
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}
