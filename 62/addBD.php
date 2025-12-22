<?
$dataBaseName = $_GET['name'];
$path = $_GET['path'];
// $path = '/home/bitrix/www/applications/crm-buttons/newvue/settings';/
$database = [
    'host' => "localhost",
    'login' => 'bitrix0',
    'password' => 'Ji]T@sq[IvSs=0b6ZHRz',
    'database' => 'sitemanager',
    'table' => 	$dataBaseName
];

$mysqli = new mysqli($database['host'], $database['login'], $database['password'], $database['database']);
if ($mysqli->connect_error) {
	echo "Не удалось подключиться к базе данных: " . $mysqli->connect_error;
	exit();
}
$arrFiles = scandir($path);
foreach($arrFiles as $element){
	if($element != '.' && $element != '..'){
		$infoFiles = json_decode(file_get_contents($path.'/'.$element),1);	
		$memberId = stristr($element, '.',true);	
		if($infoFiles['member_id']){
			$memberId = $infoFiles['member_id'];
		}
		$sql = "INSERT INTO ".$dataBaseName." (access_token, application_token, refresh_token, domain, client_endpoint, member_id) VALUES ('".$infoFiles['access_token']."', '".$infoFiles['application_token']."', '".$infoFiles['refresh_token']."', '".$infoFiles['domain']."','".$infoFiles['client_endpoint']."','".$memberId."')";
		$error = '';
		if($mysqli->query($sql)){
			$error = "Добавление Данные успешно добавлены";
		} else{
			$error = "Добавление Ошибка: " . $mysqli->error;
		} 
		print_r($error.' - '.$infoFiles['domain'].' '.$memberId);
		echo "<br>";
	}	
}	
$mysqli->close();
// print_r($arrFiles);

