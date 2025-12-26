<?
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
$buttonId = explode('|', $_SERVER['SCRIPT_NAME'])[1];
?>
<head>
	<!-- <link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css"> -->
	<script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://gcore.jsdelivr.net/npm/vue/dist/vue.min.js"></script>
    <!-- <script src="https://unpkg.com/vue-multiselect@2.1.0"></script> -->
	<!-- <script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script> -->
</head>


	<?
$result_entity = overCRest::call('entity.item.get', [
	'ENTITY' => 'customButton',
	'FILTER' => ['ID' => $buttonId]
])['result'][0]['PROPERTY_VALUES'];




$viewButton = [
    'buttonColor_FIELDS'       => $result_entity['buttonColor_FIELDS'],
    'textColor_FIELDS'         => $result_entity['textColor_FIELDS'],
    'buttonRadius_FIELDS'      => $result_entity['buttonRadius_FIELDS'],
    'buttonBorder_FIELDS'      => $result_entity['buttonBorder_FIELDS'],
    'buttonBorderWidth_FIELDS' => $result_entity['buttonBorderWidth_FIELDS'],
    'buttonBorderColor_FIELDS' => $result_entity['buttonBorderColor_FIELDS'],
    'textOnTheButton_FIELDS'   => $result_entity['textOnTheButton_FIELDS'],
    'usingTheIcon_FIELDS'      => $result_entity['usingTheIcon_FIELDS'],
    'iconOnTheButton_FIELDS'   => $result_entity['iconOnTheButton_FIELDS'],
];

$crmActions = [
    'entitySelection_FIELDS'       => $result_entity['entitySelection_FIELDS'],
    'buttonActionsId_FIELDS'         => $result_entity['buttonActionsId_FIELDS'],
    'businessProcessesValue_FIELDS'      => $result_entity['businessProcessesValue_FIELDS'],
    'documentTemplatesValue_FIELDS'      => $result_entity['documentTemplatesValue_FIELDS'],
    'listsValue_FIELDS' => $result_entity['listsValue_FIELDS'],
    'fieldsTable_FIELDS' => $result_entity['fieldsTable_FIELDS'],
    'link_FIELDS' => $result_entity['link_FIELDS'],
    'crmLinkFields_FIELDS' => $result_entity['crmLinkFields_FIELDS'],

];
// file_put_contents(__DIR__.'/result91.log', var_export($crmActions, true), FILE_APPEND);

?>

<script>
window.crmActions = <? echo json_encode($crmActions, JSON_UNESCAPED_UNICODE); ?>;

	</script>


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
		background-color: <? echo $viewButton['buttonColor_FIELDS'];  ?>;
		color: <? echo $viewButton['textColor_FIELDS'];  ?>;
		border-radius: <? echo $viewButton['buttonRadius_FIELDS'] . 'px'; ?>;
		padding: 12px 0;
		width: 100%;
		<? if (json_decode($viewButton['buttonBorder_FIELDS'])) {
			echo 'border:' . $viewButton['buttonBorderWidth_FIELDS'] . 'px solid ' . $viewButton['buttonBorderColor_FIELDS'] . ';';
		} else{
			echo 'border:none';
		} ?>
	}

	.btn span {
		font-family: 'Font Awesome 5 Free', 'Font Awesome 5 Brands' !important;
		margin-right: 5px;
	}


/* Модальное окно Начало */
.modal-mask {
	position: fixed;
	z-index: 9998;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background-color: rgba(0, 0, 0, 0.5);
	display: table;
	transition: opacity 0.3s ease;
}

.modal-wrapper {
	display: table-cell;
	vertical-align: middle;
}

.loader {
	margin: 0 auto;
	border: 5px solid #f3f3f3;
	/* Light grey */
	border-top: 5px solid #3498db;
	/* Blue */
	border-radius: 50%;
	width: 30px;
	height: 30px;
	animation: spin 2s linear infinite;
}

@keyframes spin {
	0% {
		transform: rotate(0deg);
	}

	100% {
		transform: rotate(360deg);
	}
}

.modal {
	margin: 0 auto;
	position: relative;
}
	
</style>
<div id="app">
<button data-id="<? echo $buttonId;?>" data-idEntity="<?echo $idEntity;?>" class="btn btnAplicaton" @click="runActions">
<? if ($viewButton['usingTheIcon_FIELDS'] == "true") {
	echo '<span>' . $viewButton['iconOnTheButton_FIELDS'] . '</span>';
}
echo $viewButton['textOnTheButton_FIELDS']; ?>
</button>



<div v-if="loader" class="modal-mask">
            <div class="modal-wrapper">
                <div class="loader"></div>
            </div>
        </div>	

</div>

<script src="https://kit.fontawesome.com/c9f5eeb571.js" crossorigin="anonymous"></script>
<script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

	<script type="module" src="../6/buttonHandlers/script.js"></script>
