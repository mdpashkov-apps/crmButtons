<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$memberId = $requestData['memberId'];
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($memberId);
$entity = $requestData['current_button']['value'];


$getBizProc = overCRest::call(
    'bizproc.workflow.template.list',
    [
        'select' => [
            'ID',
            'NAME',
            // 'PARAMETERS',
            // 'VARIABLES',
            // 'CONSTANTS'
        ],
        'filter' => [
            'MODULE_ID'    => 'crm',
            'DOCUMENT_TYPE'=> $entity
        ],
       
    ]
);


$bizProcList = [];

    foreach ($getBizProc['result'] as $bp) {
        $bizProcList[] = [
            'value' => $bp['ID'],
            'name'  => $bp['NAME'],
        ];
    }


    echo json_encode([
    'result' => $bizProcList,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


// file_put_contents(__DIR__.'/result91.log', var_export($bizProcList, true), FILE_APPEND);

