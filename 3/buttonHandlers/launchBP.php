<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = json_decode($requestData['id']);
$idEntity = $requestData['idEntity'];
$entity = $requestData['entity'];
$path =  pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$result = [];
$entity_list = ["Lead", "Deal", "Contact", "Company"];
if (in_array($entity, $entity_list) ){
$DOCUMENT_ID = ['crm','CCrmDocument'.$entity, strtoupper($entity).'_'.$idEntity];
} else {
	if ($entity == '31') {
		$DOCUMENT_ID = ['crm', 'Bitrix\Crm\Integration\BizProc\Document\SmartInvoice', $idEntity ];
	}
	else{
$DOCUMENT_ID = ['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic','DYNAMIC_' . $entity . '_' . $idEntity];
}
}
foreach($id as $elem){
	$result[] = overCRest::call('bizproc.workflow.start', [
		'TEMPLATE_ID' => $elem,
		'DOCUMENT_ID'=>$DOCUMENT_ID
	]);	
}
echo json_encode([
    'result' => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);