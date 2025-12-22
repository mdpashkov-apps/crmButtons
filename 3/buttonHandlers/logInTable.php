<?php
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
$id = $requestData['id'];
$userId = $requestData['userId'];
$prop = $requestData['prop'];
$domain = $requestData['domain'];
$memberId = $requestData['memberId'];
$newProp = [
	'buttonActionsId_FIELDS'=>json_decode($prop['buttonActionsId_FIELDS'], 1),
	'businessProcessesValue_FIELDS'=>json_decode($prop['businessProcessesValue_FIELDS'], 1),
	'documentTemplatesValue_FIELDS'=>json_decode($prop['documentTemplatesValue_FIELDS'], 1),
	'listsValue_FIELDS'=>json_decode($prop['listsValue_FIELDS'], 1),
	'fieldsTable_FIELDS'=>json_decode($prop['fieldsTable_FIELDS'], 1),
	'link_FIELDS'=>$prop['link_FIELDS'],
	'buttonInCRM_FIELDS'=>$prop['buttonInCRM_FIELDS'],
	'buttonName_FIELDS' => $prop['buttonName_FIELDS'],
	'id' => $id,
	'user' => $userId
];

$conn = new mysqli("localhost", "bitrix0", "Ji]T@sq[IvSs=0b6ZHRz","sitemanager");
if($conn->connect_error){
    die("Ошибка: " . $conn->connect_error);
} 
$sql = "INSERT INTO log_applications (app_name,app_id,type,full_url,member_id,json) VALUES ('crmButtons',27,'logs','https://".$domain."','".$memberId."','".json_encode($newProp)."')";
// $sql = "INSERT INTO log_applications (app_name,app_id,type,full_url, json, member_id ) VALUES ('crmButtons',27,'logs','".$domain."','".json_encode($newProp, 1)."',".$memberId.")";
$error = '';
if($conn->query($sql)){
	$error = "Добавление Данные успешно добавлены";
} else{
	$error = "Добавление Ошибка: " . $conn->error;
} 

echo json_encode([
    'result' => $newProp,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);