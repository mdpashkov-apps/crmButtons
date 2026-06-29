<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = json_decode($requestData['id']);
$idEntity = $requestData['idEntity'];
$entity = $requestData['entity'];
$parametrs = $requestData['parametrs'];

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

$paramFilter = [];
if($parametrs){
	foreach($parametrs as $key => $elem){		
		if($elem['Type'] == 'bool'){
			if($elem['Multiple'] == 0){
				if($elem['value']){
					$paramFilter[$key] = 'Y';
				} else{
					$paramFilter[$key] = 'N';
				}
			} else{
				$paramFilter[$key] = [];
				foreach($elem['value'] as $valueParam){
					if($valueParam){
						$paramFilter[$key][] = 'Y';
					} else{
						$paramFilter[$key][] = 'N';
					}
				}
			}		
		} else if(in_array($elem['Type'],['select','user'])){
			if($elem['Multiple'] == 0){
				if(!empty($elem['value']['value'])){
					if($elem['Type'] == 'user'){
						$paramFilter[$key] = 'user_'.$elem['value']['value'];
					} else{
						$paramFilter[$key] = $elem['value']['value'];
					}
				}			
			} else{
				$paramFilter[$key] = [];
				foreach($elem['value'] as $valueParam){
					if(!empty($valueParam)){
						if($elem['Type'] == 'user'){
							$paramFilter[$key][] = 'user_'.$valueParam['value'];
						} else{
							$paramFilter[$key][] = $valueParam['value'];
						}
					}
				}
			}
		} else if($elem['Type'] == 'datetime'){
			if($elem['Multiple'] == 0){
				$paramFilter[$key] = $elem['value'];
			} else{
				$paramFilter[$key] = [];
				foreach($elem['value'] as $keyParam => $valueParam){
					if(!empty($valueParam)){						
						array_push($paramFilter[$key],date('m.d.Y H:i:s',strtotime($valueParam)));						
					}
				}
			}
		} else{
			if(in_array($elem['Type'],['email','phone','web'])){
				// if($elem['Multiple'] == 0){
				// 	$paramFilter[$key] = $elem['value'];
				// } else{
				// 	if(array_key_exists($key,$paramFilter)){
				// 		$paramFilter[$key] = '';
				// 	}
				// 	foreach($elem['value'] as $keyParam => $valueParam){
				// 		if(!empty($valueParam)){
				// 			$paramFilter[$key] .= $valueParam.',';						
				// 			if(array_key_exists('additionalField',$elem)){
				// 				$paramFilter[$key] .= $elem['additionalField'][$keyParam]['value'].',';
				// 			}
				// 		}
				// 	}
				// }		
			}else{
				if($elem['Multiple'] == 0){
					$paramFilter[$key] = $elem['value'];
				} else{
					$paramFilter[$key] = [];
					foreach($elem['value'] as $keyParam => $valueParam){
						$paramFilter[$key][] = $valueParam;
					}
				}
				// if(array_key_exists($key,$paramFilter)){
				// 	$paramFilter[$key] = '';
				// }
				// foreach($elem['value'] as $keyParam => $valueParam){
				// 	if(!empty($valueParam)){
				// 		$paramFilter[$key] .= $valueParam.',';
				// 		if(array_key_exists('additionalField',$elem)){
				// 			$paramFilter[$key] .= $elem['additionalField'][$keyParam]['value'].',';				
				// 		}
				// 	}
				// }
			}
		}
		// if($elem['Type'] != 'datetime'){
		// 	if (!empty($paramFilter[$key]) && substr($paramFilter[$key], -1) == ',') {
		// 		$paramFilter[$key] = substr($paramFilter[$key], 0, -1);
		// 	}
		// }
	}
}

foreach($id as $elem){
	$filter = [
		'TEMPLATE_ID' => $elem,
		'DOCUMENT_ID'=>$DOCUMENT_ID
	];
	if($parametrs){
		$filter['PARAMETERS'] = $paramFilter;
	}
	$result[] = overCRest::call('bizproc.workflow.start', $filter);	
	
}

echo json_encode([
    'result' => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);