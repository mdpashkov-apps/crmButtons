<?
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$idsBP = json_decode($requestData['id']);
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
$memberId = $requestData['memberId'];
$options = $requestData['options'];
overCRest::setCurrentBitrix24($memberId);


$result = overCRest::call('bizproc.workflow.template.list', [
	'filter' => ['ID' => $idsBP],
	'select' => ['NAME','PARAMETERS']
]);

$bacthBP = [
	'method' => 'bizproc.workflow.template.list',
	'params' => [
		'select' => ['NAME','PARAMETERS','ID'],
		'filter' => ['ID' => $idsBP]
	]
];

$totalBP = overCRest::call('bizproc.workflow.template.list', ['filter' => ['ID' => $idsBP]])["total"];
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
$businessProcessWithoutParameters = [];
$businessProcessWithParameters = [];

foreach($resultBP as $elem){
	if(count($elem['PARAMETERS']) != 0){		
		foreach($elem['PARAMETERS'] as $key => $item){
			if($item['Required'] == '1'){
				$elem['PARAMETERS'][$key]['Required'] = true;
			} else{
				$elem['PARAMETERS'][$key]['Required'] = false;
			}
			if(in_array($item['Type'],['string','text'])){
				if($item['Multiple'] == '1'){
					if(!empty($item['Default'])){
						$elem['PARAMETERS'][$key]['value'] = [];
						foreach($item['Default'] as $elemDefault){
							$elem['PARAMETERS'][$key]['value'][] = $elemDefault;
						}
					} else{
						$elem['PARAMETERS'][$key]['value'] = [''];
					}
				} else{
					if($item['Default'] != ''){
						$elem['PARAMETERS'][$key]['value'] = $item['Default'];
					}else{
						$elem['PARAMETERS'][$key]['value'] = '';
					}
				}				
			}		
			if(in_array($item['Type'],['email','phone'])){
				if($item['Default'] != ''){
					$elem['PARAMETERS'][$key]['value'] = [];
					$elem['PARAMETERS'][$key]['additionalField'] = [];
					foreach($item['Default'] as $element){
						foreach($element as $elemDefault){
							$elem['PARAMETERS'][$key]['value'][] = $elemDefault['VALUE'];							
							foreach($options[$item['Type']] as $type){
								if($type['value'] == $elemDefault['VALUE_TYPE']){
									$elem['PARAMETERS'][$key]['additionalField'][] = $type;
								}
							}	
						}
					}
				} else{
					$elem['PARAMETERS'][$key]['value'] = [''];
					$elem['PARAMETERS'][$key]['additionalField'] = [$options[$item['Type']][0]];
				}
			}
			if(in_array($item['Type'],['datetime'])){				
				if($item['Multiple'] == '1'){
					if(!empty($item['Default'])){
						$elem['PARAMETERS'][$key]['value'] = [];
						$elem['PARAMETERS'][$key]['additionalField'] = [];
						foreach($item['Default'] as $elemDefault){
							$date = explode(' ',$elemDefault);
							$elem['PARAMETERS'][$key]['value'][] = date('Y-m-d', strtotime($date[0])).'T'.$date[1];
							$elem['PARAMETERS'][$key]['additionalField'][] = ['name'=>'Время сервера','value'=>''];
						}
					} else{
						$elem['PARAMETERS'][$key]['value'] = [date('Y-m-d', strtotime('now')).'T00:00'];
						$elem['PARAMETERS'][$key]['additionalField'] = [['name'=>'Время сервера','value'=>'']];
					}
				} else{
					if($item['Default'] != ''){		
						$date = explode(' ',$item['Default']);						
						$elem['PARAMETERS'][$key]['value'] = date('Y-m-d', strtotime($date[0])).'T'.$date[1];
						$elem['PARAMETERS'][$key]['additionalField'] = [['name'=>'Время сервера','value'=>'']];						
					} else{
						$elem['PARAMETERS'][$key]['value'] = date('Y-m-d', strtotime('now')).'T00:00';
						$elem['PARAMETERS'][$key]['additionalField'] = [['name'=>'Время сервера','value'=>'']];
					}					
				}					
			}		
			if(in_array($item['Type'],['int','double'])){				
				if($item['Multiple'] == '1'){
					if(!empty($item['Default'])){
						$elem['PARAMETERS'][$key]['value'] = [];
						foreach($item['Default'] as $elemDefault){
							$elem['PARAMETERS'][$key]['value'][] = $elemDefault;
						}
					} else{
						$elem['PARAMETERS'][$key]['value'] = [''];
					}					
				} else{
					if($item['Default'] != ''){
						$elem['PARAMETERS'][$key]['value'] = $item['Default'];
					} else{
						$elem['PARAMETERS'][$key]['value'] = '';
					}
					
				}
			}		
			if(in_array($item['Type'],['web'])){
				$elem['PARAMETERS'][$key]['value'] = [''];
				$elem['PARAMETERS'][$key]['additionalField'] = [['name'=>'Корпоративный','value'=>'WORK']];

				if($item['Default'] != ''){
					$elem['PARAMETERS'][$key]['value'] = [];
					$elem['PARAMETERS'][$key]['additionalField'] = [];
					foreach($item['Default'] as $element){
						foreach($element as $elemDefault){
							$elem['PARAMETERS'][$key]['value'][] = $elemDefault['VALUE'];							
							foreach($options['site'] as $type){
								if($type['value'] == $elemDefault['VALUE_TYPE']){
									$elem['PARAMETERS'][$key]['additionalField'][] = $type;
								}
							}	
						}
					}
				} else{
					$elem['PARAMETERS'][$key]['value'] = [''];
					$elem['PARAMETERS'][$key]['additionalField'] = [$options['site'][0]];
				}
			}		
			if(in_array($item['Type'],['select'])){
				$newOptions = [];
				$forDefault = [];
				foreach($elem['PARAMETERS'][$key]['Options'] as $keyOptions => $elemOptions){
					$newOptions[] = ['name'=>$elemOptions,'value'=>$keyOptions];
					$forDefault[$elemOptions] = ['name'=>$elemOptions,'value'=>$keyOptions];
				}
				$elem['PARAMETERS'][$key]['Options'] = $newOptions;
				if($item['Multiple'] == '1'){
					if(!empty($item['Default'])){
						$elem['PARAMETERS'][$key]['value'] = [];
						foreach($item['Default'] as $elemDefault){
							$elem['PARAMETERS'][$key]['value'][] = $forDefault[$elemDefault];
						}
					} else{
						$elem['PARAMETERS'][$key]['value'] = [];
					}					
				} else{
					if($item['Default'] != ''){
						$elem['PARAMETERS'][$key]['value'] = $forDefault[$item['Default']];
					} else{
						$elem['PARAMETERS'][$key]['value'] = [];
					}					
				}
			}		
			if(in_array($item['Type'],['user'])){
				$forDefault = [];
				foreach($options['user'] as $user){
					$forDefault[$user['value']] = $user;
				}
				if($item['Multiple'] == '1'){
					$elem['PARAMETERS'][$key]['value'] = [];
					if(!empty($item['Default'])){
						foreach($item['Default'] as $elemDefault){
							if(strpos($elemDefault, 'user') !== false){
								$elem['PARAMETERS'][$key]['value'][] = $forDefault[explode('_',$elemDefault)[1]];
							}							
						}
					}
				} else{
					if($item['Default'] != ''){
						if(strpos($item['Default'], 'user') !== false){
							$elem['PARAMETERS'][$key]['value'] = $forDefault[explode('_',$item['Default'])[1]];
						}
					} else{
						$elem['PARAMETERS'][$key]['value'] = [];
					}					
				}
			}		
			if(in_array($item['Type'],['bool'])){
				if($item['Multiple'] == '1'){					
					if(!empty($item['Default'])){
						$elem['PARAMETERS'][$key]['value'] = [];
						foreach($item['Default'] as $elemDefault){
							if($elemDefault == 'Y'){
								$elem['PARAMETERS'][$key]['value'][] = true;
							} else{
								$elem['PARAMETERS'][$key]['value'][] = false;
							}
						}						
					} else{
						$elem['PARAMETERS'][$key]['value'] = [true];
					}
				} else{
					if($item['Default'] != ''){
						if($item['Default'] == 'Y'){
							$elem['PARAMETERS'][$key]['value'] = true;
						} else{
							$elem['PARAMETERS'][$key]['value'] = false;
						}
					} else{
						$elem['PARAMETERS'][$key]['value'] = true;
					}					
				}
			}			
		}
		$businessProcessWithParameters[] = $elem;
	} else{
		$businessProcessWithoutParameters[] = $elem['ID'];
	}
}

echo json_encode([
    'businessProcessWithParameters' => $businessProcessWithParameters,
    'businessProcessWithoutParameters' => $businessProcessWithoutParameters
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

