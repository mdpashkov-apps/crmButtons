<?php
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');

$conn = new mysqli("localhost", "bitrix0", "Ji]T@sq[IvSs=0b6ZHRz","sitemanager");
if($conn->connect_error){
    die("Ошибка: " . $conn->connect_error);
}
$sql = "SELECT client_endpoint,member_id FROM crmButtons";
if($result = $conn->query($sql)){
	foreach($result as $row){
		$domain = str_replace('/rest/','',str_replace('https://','',$row['client_endpoint']));
		if($domain == 'crm.panpartner.ru'){
			// работа по каждому порталу
			overCRest::setCurrentBitrix24($row['member_id']);			
			$getEntity = overCRest::call('entity.item.get', [
				'ENTITY' => 'customButton'
			])['result'];
			print_r($getEntity);
			// foreach($getEntity as $elemEntity){	
			// 	if($elemEntity['PROPERTY_VALUES']['buttonInCRM_FIELDS'] == 'true'){
			// 		$newPath = pathinfo($path, PATHINFO_DIRNAME);
			// 		$file = $domain . '_button|' . $elemEntity['ID'] . '|.php';
			// 		$file_path = $newPath.'/fieldTypeHandlers/' . $file;
			// 		file_put_contents($file_path, '<? include("../1/buttonHandlers/button.php");');
			// 	}
			// }
		}
    }    
}