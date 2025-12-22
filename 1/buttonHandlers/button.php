<?
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
$id = explode('|', $_SERVER['SCRIPT_NAME'])[1];
$result_entity = overCRest::call('entity.item.get', [
	'ENTITY' => 'customButton',
	'FILTER' => ['ID' => $id]
])['result'][0]['PROPERTY_VALUES'];

?>
<script src="//api.bitrix24.com/api/v1/"></script>

	    <? 
		$member_id = $_REQUEST['member_id'];
		$domain = $_REQUEST['DOMAIN'];
		?>
    <script>	
        window.memberId = '<?echo $member_id?>'		
        window.domain = '<?echo $domain?>'		
		BX24.callMethod('user.current', {}, function(res){
			window.userId = res.answer.result.ID
		});
    </script>

<?

$idEntity = (array)json_decode($_REQUEST['PLACEMENT_OPTIONS']);
$idEntity = $idEntity['ENTITY_VALUE_ID'];



?>
<style>
	.btn {
		margin-top: 5px;
		cursor: pointer;
		background-color: <? echo $result_entity['buttonColor_FIELDS'];  ?>;
		color: <? echo $result_entity['textColor_FIELDS'];  ?>;
		border-radius: <? echo $result_entity['buttonRadius_FIELDS'] . 'px'; ?>;
		padding: 12px 0;
		width: 100%;
		<? if (json_decode($result_entity['buttonBorder_FIELDS'])) {
			echo 'border:' . $result_entity['buttonBorderWidth_FIELDS'] . 'px solid ' . $result_entity['buttonBorderColor_FIELDS'] . ';';
		} else{
			echo 'border:none';
		} ?>
	}

	.btn span {
		font-family: 'Font Awesome 5 Free', 'Font Awesome 5 Brands' !important;
		margin-right: 5px;
	}
</style>
<? ?><button data-id="<? echo $id;?>" data-idEntity="<?echo $idEntity;?>" class="btn btnAplicaton">
<? if ($result_entity['usingTheIcon_FIELDS'] == "true") {
	echo '<span>' . $result_entity['iconOnTheButton_FIELDS'] . '</span>';
}
echo $result_entity['textOnTheButton_FIELDS']; ?></button>


<script src="https://kit.fontawesome.com/c9f5eeb571.js" crossorigin="anonymous"></script>
<script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script type="module" src="../1/buttonHandlers/script.js"></script>
