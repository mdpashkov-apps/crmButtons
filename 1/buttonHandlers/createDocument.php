<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = json_decode($requestData['id']);
$idEntity = $requestData['idEntity'];
$entity = $requestData['entity'];
$entityTypeId = 0;
$path =  pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$resultSP = [];
        foreach ($bacthArrSP as $key => $cmdSP_arr) {
            sleep(2); //Щадяший режим лучше ставить 2 секунды
            $batchResultSP = overCRest::callBatch($cmdSP_arr, false)['result']['result'];
            foreach ($batchResultSP as $elementSP) {
                $resultSP = array_merge($resultSP, $elementSP);
            }
        }
        foreach ($resultSP['types'] as $SP) {
            array_push($newObject['array_entities'], ["value" => $SP['entityTypeId'], "name" => $SP['title']]);
        }
$result = [];
switch ($entity) {
	case 'Lead':
		$entityTypeId = '1';
		break;
	case 'Contact':
		$entityTypeId = '3';
		break;
	case 'Company':
		$entityTypeId = '4';
		break;
	case 'Deal':
		$entityTypeId = '2';
		break;
		default:
		$current_SP = [];
		foreach ($resultSP['types'] as $SP) {
		  if ($SP['entityTypeId'] == $entity) {
			  $current_SP = $SP;
		  }
		}
		$entityTypeId = $entity;
            break;

}
foreach($id as $elem){
	$result[] = overCRest::call('crm.documentgenerator.document.add', [
		'templateId' => $elem,
		'entityTypeId'=>$entityTypeId,
		'entityId'=>$idEntity
	]);		
}

echo json_encode([
    'result' => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);