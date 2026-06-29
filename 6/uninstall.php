<?php
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME); 
$pattern = $_POST['auth']['domain']."*";
$folder = $path.'/fieldTypeHandlers';
$files = glob($folder. '/' . $pattern, GLOB_BRACE);
foreach($files as $filename) {
	unlink($filename);
}

$member_id = $_REQUEST['auth']['member_id'];
if($member_id) {
    $database = [
        'host' => "localhost",
        'login' => 'bitrix0',
        'password' => 'Ji]T@sq[IvSs=0b6ZHRz',
        'database' => 'sitemanager',
        'table' => 	'crmButtons'
    ];
    $mysqli = new mysqli($database['host'], $database['login'], $database['password'], $database['database']);

    $request = "SELECT * FROM ".$database["table"]." WHERE member_id = '$member_id'";
    $result = mysqli_query($mysqli, $request);

    $rows_to_delete = [];

    while($row = mysqli_fetch_array($result)) {
        $rows_to_delete[] = $row['ID'];
    }

    foreach ($rows_to_delete as $id) {
        $request = "DELETE FROM ".$database["table"]." WHERE member_id = '$member_id'";
        $result = mysqli_query($mysqli, $request);
    }
}