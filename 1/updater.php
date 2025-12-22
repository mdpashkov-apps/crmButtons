<?php
include_once(__DIR__.'/overCRest.php');

$member_id = $_POST['member_id'];
if($member_id) {
overCRest::setCurrentBitrix24($member_id);
$res = overCRest::call('scope', [
    'select' => ['id', 'title']
    ] );
}