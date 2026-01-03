<?
$path =   pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
$buttonId = explode('|', $_SERVER['SCRIPT_NAME'])[1];
?>
<head>

    <script src="https://gcore.jsdelivr.net/npm/vue/dist/vue.min.js"></script>

  	<link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css">
  <script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script>
	<script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
	<script src="https://kit.fontawesome.com/c9f5eeb571.js" crossorigin="anonymous"></script>
	

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

$entityInBtnSettings = json_decode($result_entity['entitySelection_FIELDS'], true)['value'];

$entityTypeIdOpened = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true)['ENTITY_DATA']['entityTypeId'];
$entityMap = [
    '1' => 'Lead',
    '2' => 'Deal',
    '3' => 'Contact',
    '4' => 'Company',
];
$entityTypeIdMap = $entityMap[$entityTypeIdOpened] ?? $entityTypeIdOpened;

$member_id = $_REQUEST['member_id'];


// if ($entityTypeIdMap == $entityInBtnSettings) {
// 	$buttonActions = json_decode($crmActions['buttonActionsId_FIELDS'], true);

// 	if (is_array($buttonActions) && in_array(0, $buttonActions, true)) {



// 	}
// }





?>

<script>
window.memberId = '<?echo $member_id?>'		
window.crmActions = <? echo json_encode($crmActions, JSON_UNESCAPED_UNICODE); ?>;
window.entityData = <?= json_encode( json_decode($_REQUEST['PLACEMENT_OPTIONS'], true),JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="//api.bitrix24.com/api/v1/"></script>

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
	body {
  height: auto !important;
  overflow: visible !important;
}

#app {
  height: auto !important;
  overflow: visible !important;



/* INPUTS */
.param-block input {
  height: 42px;              /* ← фиксированная высота (потом поменяешь) */
  border-radius: 8px;        /* ← скругление */
  border: 1px solid #d1d5db;
  padding: 0 12px;
  width: 100%;
  box-sizing: border-box;
}

/* MULTIPLE INPUT WRAPPER */
.param-multiple-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.param-multiple-row input {
  flex: 1;
}


/* DELETE (X) BUTTON */
.remove-field {
  width: 24px;        /* фиксированная ширина */
  text-align: center;
  cursor: pointer;
  color: #ef4444;
  font-size: 18px;
  line-height: 1;
}

/* скрытый крестик (для первого) */
.remove-field--placeholder {
  visibility: hidden; /* место есть, но не видно */
  cursor: default;
}

/* ADD BUTTON */
.add-btn {
  margin-top: 6px;
  height: 36px;
  padding: 0 14px;
  border-radius: 8px;
  background-color: #3b82f6; /* голубая */
  color: #fff;
  border: none;
  cursor: pointer;
}

/* RUN BP BUTTON */
.run-bp-btn {
  background-color: #22c55e; /* зелёная */
}

/* DISABLED STATE */
button:disabled {
  background-color: #9ca3af !important;
  cursor: not-allowed;
  opacity: 0.7;
}







  
}
</style>

<div id="app">
	<?php if ($entityTypeIdMap == $entityInBtnSettings): ?>




<div v-if="paramResult && paramResult.length">




  <div class="bp-block">
    <h4>
      Бизнес процесс:
      {{ paramResult[currentBpIndex].NAME }}
    </h4>

    <div
      v-for="param in paramResult[currentBpIndex].PARAMETERS"
      :key="param.Name"
      class="param-block"
    >
      <label>
        {{ param.Name }}
        <span v-if="param.Required" style="color:red">*</span>
      </label>

     


   <!-- SINGLE -->
<div v-if="param.Multiple == 0">

  <input
    v-if="param.Type === 'number'"
    type="number"
    v-model="formValues[paramResult[currentBpIndex].ID][param.Name][0]"
  />

  <input
    v-else-if="param.Type === 'txt'"
    type="text"
    v-model="formValues[paramResult[currentBpIndex].ID][param.Name][0]"
  />

  <multiselect
    v-else-if="param.Type === 'user'"
    v-model="formValues[paramResult[currentBpIndex].ID][param.Name]"
    placeholder="Выберите пользователя"
    label="name"
    track-by="value"
    :options="allUsers"
    :multiple="false"
    :close-on-select="true"
  >
    <span slot="noResult">Такого варианта нет</span>
  </multiselect>

</div>






     <!-- MULTIPLE -->
<div v-else>

  <!-- MULTIPLE USER -->
 <multiselect v-if="param.Type === 'user'"     v-model="formValues[paramResult[currentBpIndex].ID][param.Name]" name="selection_user" :placeholder-size="16" placeholder="Выберите пользователя"    label="name"
               track-by="value"deselect-label="Убрать" select-label="" selected-label="" open-direction="top" :options="allUsers" :multiple="true" :taggable="false" :close-on-select="true" :limit="1" >
               <span slot="noResult">
               Такого варианта нет
               </span>
            </multiselect>

  <!-- MULTIPLE NUMBER / TEXT -->
  <div
    v-else
    v-for="(val, idx) in formValues[paramResult[currentBpIndex].ID][param.Name]"
    :key="idx"
    class="param-multiple-row"
  >
    <input
      :type="param.Type === 'number' ? 'number' : 'text'"
      v-model="formValues[paramResult[currentBpIndex].ID][param.Name][idx]"
    />

    <span
      :class="[
        'remove-field',
        { 'remove-field--placeholder': idx === 0 }
      ]"
      @click="idx > 0 && removeField(
        paramResult[currentBpIndex].ID,
        param.Name,
        idx
      )"
    >
      ✕
    </span>
  </div>



  <button
   v-if="param.Type !== 'user'"
    class="add-btn"
    @click="addField(
      paramResult[currentBpIndex].ID,
      param.Name
    )"
  >
    Добавить ещё
  </button>
</div>



    </div>




    
  </div>

  <button
  class="btn run-bp-btn"
    :disabled="!isCurrentBpValid"
    @click="runCurrentBp"
  >
    Запустить БП
  </button>
</div>





		<button data-id="<? echo $buttonId;?>" data-idEntity="<?echo $idEntity;?>" v-else class="btn btnAplicaton" @click="runActions">
			<? if ($viewButton['usingTheIcon_FIELDS'] == "true") {
				echo '<span>' . $viewButton['iconOnTheButton_FIELDS'] . '</span>';
			}
			echo $viewButton['textOnTheButton_FIELDS']; ?>
		</button>

	<?php else: ?>
		<div class="entity-warning">
			Кнопка настроена для другой сущности
		</div>
	<?php endif; ?>

	<div v-if="loader" class="modal-mask">
		<div class="modal-wrapper">
			<div class="loader"></div>
		</div>
	</div>	
</div>


<script type="module" src="../6/buttonHandlers/script.js"></script>
