<?
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
$buttonId = explode('|', $_SERVER['SCRIPT_NAME'])[1];
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="//api.bitrix24.com/api/v1/"></script>
  <script src="https://gcore.jsdelivr.net/npm/vue/dist/vue.min.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css">
  <script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script>
  <script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <link rel="stylesheet" href="../6/buttonHandlers/css/buttonStyle.css?v=<?= time() ?>">
</head>
<body>
  <style>
  body {
    margin: 0 !important;
    padding: 0 !important;
    background: transparent;
  }
</style>
<?
$member_id = $_REQUEST['member_id'];
$result_entity = overCRest::call('entity.item.get', [
  'ENTITY' => 'customButton',
  'FILTER' => ['ID' => $buttonId]
])['result'][0]['PROPERTY_VALUES'];

$viewButton = [
  'buttonColor_FIELDS' => $result_entity['buttonColor_FIELDS'],
  'textColor_FIELDS' => $result_entity['textColor_FIELDS'],
  'buttonRadius_FIELDS' => $result_entity['buttonRadius_FIELDS'],
  'buttonBorder_FIELDS' => $result_entity['buttonBorder_FIELDS'],
  'buttonBorderWidth_FIELDS' => $result_entity['buttonBorderWidth_FIELDS'],
  'buttonBorderColor_FIELDS' => $result_entity['buttonBorderColor_FIELDS'],
  'textOnTheButton_FIELDS' => $result_entity['textOnTheButton_FIELDS'],
  'usingTheIcon_FIELDS' => $result_entity['usingTheIcon_FIELDS'],
  'iconOnTheButton_FIELDS' => $result_entity['iconOnTheButton_FIELDS'],
];

$crmActions = [
  'entitySelection_FIELDS' => $result_entity['entitySelection_FIELDS'],
  'buttonActionsId_FIELDS' => $result_entity['buttonActionsId_FIELDS'],
  'businessProcessesValue_FIELDS' => $result_entity['businessProcessesValue_FIELDS'],
  'documentTemplatesValue_FIELDS' => $result_entity['documentTemplatesValue_FIELDS'],
  'listsValue_FIELDS' => $result_entity['listsValue_FIELDS'],
  'fieldsTable_FIELDS' => $result_entity['fieldsTable_FIELDS'],
  'link_FIELDS' => $result_entity['link_FIELDS'],
  'crmLinkFields_FIELDS' => $result_entity['crmLinkFields_FIELDS'],
];

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
window.memberId = '<?echo $member_id?>';    
window.crmActions = <? echo json_encode($crmActions, JSON_UNESCAPED_UNICODE); ?>;
window.entityData = <?= json_encode( json_decode($_REQUEST['PLACEMENT_OPTIONS'], true),JSON_UNESCAPED_UNICODE) ?>;
</script>

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
  <div v-if="paramResult && paramResult.length">
    <div class="bp-block">
      <div v-for="(param, paramIndex) in paramResult[currentBpIndex].PARAMETERS" :key="param.Name + paramIndex" class="param-block">
        <label>
          {{ param.Name }}
          <span v-if="param.Required" style="color:red">*</span>
        </label>
        
        <!-- ОДИНОЧНЫЕ ПОЛЯ (Multiple == 0) -->
        <div v-if="param.Multiple == 0">
          <!-- Поле number -->
          <input v-if="param.Type === 'number'" type="number" :value="getSingleValue(paramResult[currentBpIndex].ID, param.Name)" @input="updateSingleValue(paramResult[currentBpIndex].ID, param.Name, $event.target.value)"/>
          
          <!-- Поле datetime -->
          <input v-else-if="param.Type === 'datetime'" type="datetime-local" :value="getSingleValue(paramResult[currentBpIndex].ID, param.Name)" @input="updateSingleValue(paramResult[currentBpIndex].ID, param.Name, $event.target.value)"/>
          
          <!-- Поле text -->
          <input v-else-if="param.Type === 'txt'" type="text" :value="getSingleValue(paramResult[currentBpIndex].ID, param.Name)" @input="updateSingleValue(paramResult[currentBpIndex].ID, param.Name, $event.target.value)"/>
          
          <!-- Одиночный пользователь -->
          <multiselect 
            v-if="param.Type === 'user'"
            :value="getSingleUserValue(paramResult[currentBpIndex].ID, param.Name)"
            @input="updateSingleUserValue(paramResult[currentBpIndex].ID, param.Name, $event)"
            placeholder="Выберите пользователя" 
            label="name" 
            track-by="value" 
            :options="allUsers" 
            :multiple="false" 
            :close-on-select="true">
            <span slot="noResult">Такого варианта нет</span>
          </multiselect>
          
          <!-- Одиночный bool -->
          <multiselect 
            v-if="param.Type === 'bool'"
            :value="getSingleValue(paramResult[currentBpIndex].ID, param.Name)"
            @input="updateSingleValue(paramResult[currentBpIndex].ID, param.Name, $event)"
            placeholder="Выберите значение" 
            label="name" 
            track-by="value" 
            :options="boolOptions" 
            :multiple="false" 
            :close-on-select="true">
            <span slot="noResult">Такого варианта нет</span>
          </multiselect>
          
          <!-- Одиночный select -->
          <multiselect 
            v-if="param.Type === 'select'"
            :value="getSingleValue(paramResult[currentBpIndex].ID, param.Name)"
            @input="updateSingleValue(paramResult[currentBpIndex].ID, param.Name, $event)"
            :options="getSelectOptions(param)" 
            label="name" 
            track-by="value" 
            :multiple="false" 
            :close-on-select="true" 
            placeholder="Выберите значение">
            <span slot="noResult">Такого варианта нет</span>
          </multiselect>
        </div>
        
        <!-- МНОЖЕСТВЕННЫЕ ПОЛЯ (Multiple == 1) -->
        <div v-else>
          <!-- Множественный пользователь -->
          <div v-if="param.Type === 'user'">
    <div v-for="(item, idx) in getMultipleArray(paramResult[currentBpIndex].ID, param.Name)" :key="idx" class="param-multiple-row">
        <multiselect 
            :value="item"
            @input="updateMultipleValue(paramResult[currentBpIndex].ID, param.Name, idx, $event, param.Type)"
            placeholder="Выберите пользователя" 
            label="name" 
            track-by="value" 
            :options="getUserOptions(paramResult[currentBpIndex].ID, param.Name, idx)" 
            :multiple="false" 
            :close-on-select="true">
            <span slot="noResult">Такого варианта нет</span>
        </multiselect>
        <span :class="['remove-field', { 'remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeMultipleField(paramResult[currentBpIndex].ID, param.Name, idx)">✕</span>
    </div>
    <button class="add-btn" @click="addMultipleField(paramResult[currentBpIndex].ID, param.Name, param.Type)">Добавить ещё</button>
</div>
          
          <!-- Множественный bool -->
          <div v-else-if="param.Type === 'bool'">
            <div v-for="(item, idx) in getMultipleArray(paramResult[currentBpIndex].ID, param.Name)" :key="idx" class="param-multiple-row">
              <multiselect 
                :value="item"
                @input="updateMultipleValue(paramResult[currentBpIndex].ID, param.Name, idx, $event)"
                :options="boolOptions" 
                label="name" 
                track-by="value" 
                :multiple="false" 
                :close-on-select="true" 
                placeholder="Выберите значение">
                <span slot="noResult">Такого варианта нет</span>
              </multiselect>
              <span :class="['remove-field', { 'remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeMultipleField(paramResult[currentBpIndex].ID, param.Name, idx)">✕</span>
            </div>
            <button class="add-btn" @click="addMultipleField(paramResult[currentBpIndex].ID, param.Name, param.Type)">Добавить ещё</button>
          </div>
          
          <!-- Множественный select -->
          <div v-else-if="param.Type === 'select'">
            <div v-for="(item, idx) in getMultipleArray(paramResult[currentBpIndex].ID, param.Name)" :key="idx" class="param-multiple-row">
              <multiselect 
                :value="item"
                @input="updateMultipleValue(paramResult[currentBpIndex].ID, param.Name, idx, $event)"
                :options="getSelectOptions(param)" 
                label="name" 
                track-by="value" 
                :multiple="false" 
                :close-on-select="true" 
                placeholder="Выберите значение">
                <span slot="noResult">Такого варианта нет</span>
              </multiselect>
              <span :class="['remove-field', { 'remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeMultipleField(paramResult[currentBpIndex].ID, param.Name, idx)">✕</span>
            </div>
            <button class="add-btn" @click="addMultipleField(paramResult[currentBpIndex].ID, param.Name, param.Type)">Добавить ещё</button>
          </div>
          
          <!-- Множественные поля других типов (number, datetime, txt) -->
          <div v-else>
            <div v-for="(item, idx) in getMultipleArray(paramResult[currentBpIndex].ID, param.Name)" :key="idx" class="param-multiple-row">
              <input :type="param.Type === 'number' ? 'number' : (param.Type === 'datetime' ? 'datetime-local' : 'text')" 
                :value="item"
                @input="updateMultipleValue(paramResult[currentBpIndex].ID, param.Name, idx, $event.target.value)"/>
              <span :class="['remove-field', { 'remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeMultipleField(paramResult[currentBpIndex].ID, param.Name, idx)">✕</span>
            </div>
            <button class="add-btn" @click="addMultipleField(paramResult[currentBpIndex].ID, param.Name, param.Type)">Добавить ещё</button>
          </div>
        </div>
      </div>
    </div>
    <button class="btn run-bp-btn" :disabled="!isCurrentBpValid" @click="runCurrentBp">Запустить БП</button>
  </div>
  
  <button data-id="<? echo $buttonId;?>" v-else class="btn btnAplicaton" @click="runActions">
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

<script>
  window.__bxReady = new Promise(resolve => {
    BX24.ready(() => {
      resolve(window.BX24);
    });
  });
</script>
<script type="module" src="../6/buttonHandlers/js/script.js?v=<?= time() ?>"></script>

</body>
</html>