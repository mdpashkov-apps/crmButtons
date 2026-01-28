<?
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
$buttonId = explode('|', $_SERVER['SCRIPT_NAME'])[1];
?>
<head>
  <script src="//api.bitrix24.com/api/v1/"></script>
  <script src="https://gcore.jsdelivr.net/npm/vue/dist/vue.min.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css">
  <script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script>
  <script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script src="https://kit.fontawesome.com/c9f5eeb571.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="../6/buttonHandlers/css/buttonStyle.css">
</head>
<?
$member_id = $_REQUEST['member_id'];
$result_entity = overCRest::call('entity.item.get', [
	'ENTITY' => 'customButton',
	'FILTER' => ['ID' => $buttonId]
])['result'][0]['PROPERTY_VALUES'];

// массив стилей кнопки из хранилища
$viewButton = [
  'buttonColor_FIELDS' => $result_entity['buttonColor_FIELDS'], //цвет кнопки
  'textColor_FIELDS' => $result_entity['textColor_FIELDS'], // цвет текста на кнопке
  'buttonRadius_FIELDS' => $result_entity['buttonRadius_FIELDS'], // радиус скругления кнопки
  'buttonBorder_FIELDS' => $result_entity['buttonBorder_FIELDS'], // параметр использовать ли рамку вокруг кнопки
  'buttonBorderWidth_FIELDS' => $result_entity['buttonBorderWidth_FIELDS'], //толщина рамки кнопки
  'buttonBorderColor_FIELDS' => $result_entity['buttonBorderColor_FIELDS'], // цвет рамки
  'textOnTheButton_FIELDS' => $result_entity['textOnTheButton_FIELDS'], // текст на кнопке
  'usingTheIcon_FIELDS' => $result_entity['usingTheIcon_FIELDS'], // параметр использовать ли иконку на кнопке
  'iconOnTheButton_FIELDS' => $result_entity['iconOnTheButton_FIELDS'], // иконка на кнопке
];

// массив настроек действий кнопки
$crmActions = [
  'entitySelection_FIELDS' => $result_entity['entitySelection_FIELDS'], // для какой сущности
  'buttonActionsId_FIELDS' => $result_entity['buttonActionsId_FIELDS'], // действия кнопки, которые необходимо выполнить
  'businessProcessesValue_FIELDS' => $result_entity['businessProcessesValue_FIELDS'], // выбранные бп
  'documentTemplatesValue_FIELDS' => $result_entity['documentTemplatesValue_FIELDS'], // выбранные документы
  'listsValue_FIELDS' => $result_entity['listsValue_FIELDS'], // выбранные списки
  'fieldsTable_FIELDS' => $result_entity['fieldsTable_FIELDS'], // поля списка сопоставленные с полями crm
  'link_FIELDS' => $result_entity['link_FIELDS'], // введенная произвольная ссылка
  'crmLinkFields_FIELDS' => $result_entity['crmLinkFields_FIELDS'], // выбранное поле типа ссылка
];

// получаем отдельно выбранную сущность в кнопке, если лид,сделка, контакт или компания меняем их на числовой id
$entityInBtnSettings = json_decode($result_entity['entitySelection_FIELDS'], true)['value'];

$entityTypeIdOpened = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true)['ENTITY_DATA']['entityTypeId'];
$entityMap = [
    '1' => 'Lead',
    '2' => 'Deal',
    '3' => 'Contact',
    '4' => 'Company',
];
$entityTypeIdMap = $entityMap[$entityTypeIdOpened] ?? $entityTypeIdOpened;
?>

<script>
// используем эти данные для передачи в php файлы
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
</style>

<div id="app">
<?php if ($entityTypeIdMap == $entityInBtnSettings): ?>
  <div v-if="paramResult && paramResult.length">
    <div class="bp-block">
      <h4> Бизнес процесс: {{ paramResult[currentBpIndex].NAME }}</h4>
      <div v-for="param in paramResult[currentBpIndex].PARAMETERS" :key="param.Name" class="param-block">
        <label>
          {{ param.Name }}
          <span v-if="param.Required" style="color:red">*</span>
        </label>
        <!-- SINGLE -->
        <div v-if="param.Multiple == 0">
          <input v-if="param.Type === 'number'" type="number" v-model="formValues[paramResult[currentBpIndex].ID][param.Name][0]"/>
          <input v-else-if="param.Type === 'txt'" type="text" v-model="formValues[paramResult[currentBpIndex].ID][param.Name][0]"/>
          <multiselect v-else-if="param.Type === 'user'" v-model="formValues[paramResult[currentBpIndex].ID][param.Name]" placeholder="Выберите пользователя" label="name" track-by="value" :options="allUsers" :multiple="false" :close-on-select="true">
            <span slot="noResult">Такого варианта нет</span>
          </multiselect>
           <!-- MULTISELECT ДЛЯ BOOL -->
          <multiselect v-else-if="param.Type === 'bool'" v-model="formValues[paramResult[currentBpIndex].ID][param.Name]" placeholder="Выберите значение" label="name" track-by="value" :options="boolOptions" :multiple="false" :close-on-select="true">
            <span slot="noResult">Такого варианта нет</span>
          </multiselect>
        </div>
        <div v-else>
          <!-- Множественный BOOL -->
          <div v-if="param.Type === 'bool'">
            <div v-for="(val, idx) in formValues[paramResult[currentBpIndex].ID][param.Name]" :key="idx" class="param-multiple-row">
              <multiselect v-model="formValues[paramResult[currentBpIndex].ID][param.Name][idx]" :options="boolOptions" label="name" track-by="value" :multiple="false" :close-on-select="true" placeholder="Выберите значение">
                <span slot="noResult">Такого варианта нет</span>
              </multiselect>
              <span 
                :class="['remove-field', { 'remove-field--placeholder': idx === 0 }]" 
                @click="idx > 0 && removeField(paramResult[currentBpIndex].ID, param.Name, idx)"
              >✕</span>
            </div>
            <button class="add-btn" @click="addField(paramResult[currentBpIndex].ID, param.Name)">Добавить ещё</button>
          </div>

          <!-- Остальные типы (текст, число и user) остаются без изменений -->
          <div v-else-if="param.Type !== 'user'">
            <div v-for="(val, idx) in formValues[paramResult[currentBpIndex].ID][param.Name]" :key="idx" class="param-multiple-row">
              <input :type="param.Type === 'number' ? 'number' : 'text'" v-model="formValues[paramResult[currentBpIndex].ID][param.Name][idx]"/>
              <span 
                :class="['remove-field', { 'remove-field--placeholder': idx === 0 }]" 
                @click="idx > 0 && removeField(paramResult[currentBpIndex].ID, param.Name, idx)"
              >✕</span>
            </div>
            <button class="add-btn" @click="addField(paramResult[currentBpIndex].ID, param.Name)">Добавить ещё</button>
          </div>
        </div>
      </div>
    </div>
    <button class="btn run-bp-btn" :disabled="!isCurrentBpValid" @click="runCurrentBp">Запустить БП</button>
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

<script>
  // Ждём пока Bitrix инициализируется
  window.__bxReady = new Promise(resolve => {
    BX24.ready(() => {
      resolve(window.BX24);
    });
  });
</script>
<script type="module" src="../6/buttonHandlers/js/script.js"></script>

