<?
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
$buttonId = explode('|', $_SERVER['SCRIPT_NAME'])[1];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Кнопка CRM | Overplan</title>
    
    <!-- Bitrix24 JS API -->
    <script src="//api.bitrix24.com/api/v1/"></script>
    
    <!-- Font Awesome 6 Free - локально (без внешнего CDN) -->
    <link rel="stylesheet" href="../7/libs/fontawesome/css/all.min.css">
    
    <!-- Vue и зависимости - локально -->
    <script src="../7/libs/vue.min.js"></script>
    <link rel="stylesheet" href="../7/libs/vue-multiselect.min.css">
    <script src="../7/libs/vue-multiselect.min.js"></script>
    <script src="../7/libs/axios.min.js"></script>
    
    <!-- Основные стили -->
    <link rel="stylesheet" href="../7/buttonHandlers/css/buttonStyle.css?v=<?= time() ?>">
    
    <?
    // ПОЛУЧАЕМ ДАННЫЕ КНОПКИ
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
    
    // УПРОЩЁННАЯ ОБРАБОТКА ИКОНКИ - просто выводим то, что ввели
    $iconHtml = '';
    $showIcon = ($viewButton['usingTheIcon_FIELDS'] == "true" && !empty($viewButton['iconOnTheButton_FIELDS']));
    
    if ($showIcon) {
        $icon = trim($viewButton['iconOnTheButton_FIELDS']);
        // Просто выводим иконку как есть, оборачивая в span
        $iconHtml = '<span class="bx-crm-icon">' . htmlspecialchars($icon) . '</span>';
    }
    
    $crmActions = [
        'entitySelection_FIELDS' => $result_entity['entitySelection_FIELDS'],
        'buttonActionsId_FIELDS' => $result_entity['buttonActionsId_FIELDS'],
        'businessProcessesValue_FIELDS' => $result_entity['businessProcessesValue_FIELDS'],
        'documentTemplatesValue_FIELDS' => $result_entity['documentTemplatesValue_FIELDS'],
        'listsValue_FIELDS' => $result_entity['listsValue_FIELDS'],
        'fieldsTable_FIELDS' => $result_entity['fieldsTable_FIELDS'],
        'link_FIELDS' => $result_entity['link_FIELDS'],
        'crmLinkFields_FIELDS' => $result_entity['crmLinkFields_FIELDS'],
        'bpChainValue_FIELDS' => $result_entity['bpChainValue_FIELDS'] ?? '',
        'linkWithParams_FIELDS' => $result_entity['linkWithParams_FIELDS'] ?? '',
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
    
    <!-- Динамические стили для кнопки -->
    <style>
        .bx-crm-main-btn {
            background-color: <? echo $viewButton['buttonColor_FIELDS'] ?: '#2fc6f6'; ?> !important;
            color: <? echo $viewButton['textColor_FIELDS'] ?: '#ffffff'; ?> !important;
            border-radius: <? echo $viewButton['buttonRadius_FIELDS'] ?: '6'; ?>px !important;
            <? if (json_decode($viewButton['buttonBorder_FIELDS']) && !empty($viewButton['buttonBorderWidth_FIELDS'])) {
                echo 'border: ' . $viewButton['buttonBorderWidth_FIELDS'] . 'px solid ' . $viewButton['buttonBorderColor_FIELDS'] . ' !important;';
            } else {
                echo 'border: none !important;';
            } ?>
        }
        
        .bx-crm-main-btn:hover {
            opacity: 0.9;
        }
        
        .bx-crm-icon {
            margin-right: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>

<script>
window.__apiVersion = '<?= @filemtime(__DIR__ . '/js/api.js') ?: time() ?>';
window.memberId = '<?echo $member_id?>';
window.crmActions = <? echo json_encode($crmActions, JSON_UNESCAPED_UNICODE); ?>;
window.entityData = <?= json_encode(json_decode($_REQUEST['PLACEMENT_OPTIONS'], true), JSON_UNESCAPED_UNICODE) ?>;
window.buttonIconHtml = '<? echo addslashes($iconHtml); ?>';
window.buttonText = '<? echo htmlspecialchars($viewButton['textOnTheButton_FIELDS'] ?: 'Кнопка'); ?>';
window.showIcon = <? echo $showIcon ? 'true' : 'false'; ?>;
</script>

<div id="app">
    <!-- Панель с параметрами БП -->
    <div v-if="paramResult && paramResult.length" class="bx-crm-button-container">
        <div class="bx-bp-panel">
            <div class="bx-bp-progress" v-if="paramResult.length > 1">
                Шаг {{ currentBpIndex + 1 }} из {{ paramResult.length }}
            </div>
            
            <div v-for="param in paramResult[currentBpIndex].PARAMETERS" :key="param.Name" class="bx-param-group">
                <label class="bx-param-label">
                    {{ param.Name }}
                    <span class="bx-required" v-if="param.Required">*</span>
                </label>
                
                <!-- Одиночные поля -->
                <div v-if="param.Multiple == 0">
                    <input v-if="param.Type === 'number'" type="number" class="ui-input" v-model="formValues[paramResult[currentBpIndex].ID][param.Name][0]" @change="resizeBx"/>
                    <input v-else-if="param.Type === 'datetime'" type="datetime-local" class="ui-input" v-model="formValues[paramResult[currentBpIndex].ID][param.Name][0]" @change="resizeBx"/>
                    <input v-else-if="param.Type === 'txt'" type="text" class="ui-input" v-model="formValues[paramResult[currentBpIndex].ID][param.Name][0]" @input="resizeBx"/>
                    <multiselect v-else-if="param.Type === 'user'" v-model="formValues[paramResult[currentBpIndex].ID][param.Name]" placeholder="Выберите пользователя" label="name" track-by="value" :options="allUsers" :multiple="false" :close-on-select="true" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                        <span slot="noResult">Такого варианта нет</span>
                    </multiselect>
                    <multiselect v-else-if="param.Type === 'bool'" v-model="formValues[paramResult[currentBpIndex].ID][param.Name]" placeholder="Выберите значение" label="name" track-by="value" :options="boolOptions" :multiple="false" :close-on-select="true" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                        <span slot="noResult">Такого варианта нет</span>
                    </multiselect>
                    <multiselect v-else-if="param.Type === 'select'" v-model="formValues[paramResult[currentBpIndex].ID][param.Name]" :options="getSelectOptions(param)" label="name" track-by="value" :multiple="false" :close-on-select="true" placeholder="Выберите значение" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                        <span slot="noResult">Такого варианта нет</span>
                    </multiselect>
                </div>
                
                <!-- Множественные поля -->
                <div v-else>
                    <div v-if="param.Type === 'bool'">
                        <div v-for="(val, idx) in formValues[paramResult[currentBpIndex].ID][param.Name]" :key="idx" class="bx-multiple-row">
                            <multiselect v-model="formValues[paramResult[currentBpIndex].ID][param.Name][idx]" :options="boolOptions" label="name" track-by="value" :multiple="false" :close-on-select="true" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                                <span slot="noResult">Такого варианта нет</span>
                            </multiselect>
                            <span :class="['bx-remove-field', { 'bx-remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeField(paramResult[currentBpIndex].ID, param.Name, idx)">✕</span>
                        </div>
                        <button type="button" class="bx-add-btn" @click="addField(paramResult[currentBpIndex].ID, param.Name)">+ Добавить ещё</button>
                    </div>
                    
                    <div v-else-if="param.Type === 'select'">
                        <div v-for="(val, idx) in formValues[paramResult[currentBpIndex].ID][param.Name]" :key="idx" class="bx-multiple-row">
                            <multiselect v-model="formValues[paramResult[currentBpIndex].ID][param.Name][idx]" :options="getSelectOptions(param)" label="name" track-by="value" :multiple="false" :close-on-select="true" placeholder="Выберите значение" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                                <span slot="noResult">Такого варианта нет</span>
                            </multiselect>
                            <span :class="['bx-remove-field', { 'bx-remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeField(paramResult[currentBpIndex].ID, param.Name, idx)">✕</span>
                        </div>
                        <button type="button" class="bx-add-btn" @click="addField(paramResult[currentBpIndex].ID, param.Name)">+ Добавить ещё</button>
                    </div>
                    
                    <div v-else-if="param.Type === 'user'">
                        <div v-for="(val, idx) in formValues[paramResult[currentBpIndex].ID][param.Name]" :key="idx" class="bx-multiple-row">
                            <multiselect v-model="formValues[paramResult[currentBpIndex].ID][param.Name][idx]" :options="allUsers" label="name" track-by="value" :multiple="false" :close-on-select="true" placeholder="Выберите пользователя" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                                <span slot="noResult">Такого варианта нет</span>
                            </multiselect>
                            <span :class="['bx-remove-field', { 'bx-remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeField(paramResult[currentBpIndex].ID, param.Name, idx)">✕</span>
                        </div>
                        <button type="button" class="bx-add-btn" @click="addField(paramResult[currentBpIndex].ID, param.Name)">+ Добавить ещё</button>
                    </div>
                    
                    <div v-else>
                        <div v-for="(val, idx) in formValues[paramResult[currentBpIndex].ID][param.Name]" :key="idx" class="bx-multiple-row">
                            <input :type="param.Type === 'number' ? 'number' : (param.Type === 'datetime' ? 'datetime-local' : 'text')" class="ui-input" v-model="formValues[paramResult[currentBpIndex].ID][param.Name][idx]" @input="resizeBx"/>
                            <span :class="['bx-remove-field', { 'bx-remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeField(paramResult[currentBpIndex].ID, param.Name, idx)">✕</span>
                        </div>
                        <button type="button" class="bx-add-btn" @click="addField(paramResult[currentBpIndex].ID, param.Name)">+ Добавить ещё</button>
                    </div>
                </div>
            </div>
            
            <button class="ui-btn ui-btn-success bx-run-bp-btn" :disabled="!isCurrentBpValid" @click="runCurrentBp">
                <i class="fas fa-play"></i>
                Запустить БП
            </button>
        </div>
    </div>

    <!-- Панель цепочки БП (PRO) -->
    <div v-else-if="chain && currentAsks.length" class="bx-crm-button-container">
        <div class="bx-bp-panel">
            <div class="bx-bp-progress" v-if="chainTotal > 1">
                Шаг {{ currentStepIndex + 1 }} из {{ chainTotal }} — {{ currentStep.NAME }}
            </div>

            <div v-for="param in currentAsks" :key="param.Name" class="bx-param-group">
                <label class="bx-param-label">
                    {{ param.Name }}
                    <span class="bx-required" v-if="param.Required">*</span>
                </label>

                <div v-if="param.Multiple == 0">
                    <input v-if="param.Type === 'number'" type="number" class="ui-input" v-model="formValues[currentStep.ID][param.Name][0]" @change="resizeBx"/>
                    <input v-else-if="param.Type === 'datetime'" type="datetime-local" class="ui-input" v-model="formValues[currentStep.ID][param.Name][0]" @change="resizeBx"/>
                    <input v-else-if="param.Type === 'txt'" type="text" class="ui-input" v-model="formValues[currentStep.ID][param.Name][0]" @input="resizeBx"/>
                    <multiselect v-else-if="param.Type === 'user'" v-model="formValues[currentStep.ID][param.Name]" placeholder="Выберите пользователя" label="name" track-by="value" :options="allUsers" :multiple="false" :close-on-select="true" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                        <span slot="noResult">Такого варианта нет</span>
                    </multiselect>
                    <multiselect v-else-if="param.Type === 'bool'" v-model="formValues[currentStep.ID][param.Name]" placeholder="Выберите значение" label="name" track-by="value" :options="boolOptions" :multiple="false" :close-on-select="true" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                        <span slot="noResult">Такого варианта нет</span>
                    </multiselect>
                    <multiselect v-else-if="param.Type === 'select'" v-model="formValues[currentStep.ID][param.Name]" :options="getSelectOptions(param)" label="name" track-by="value" :multiple="false" :close-on-select="true" placeholder="Выберите значение" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                        <span slot="noResult">Такого варианта нет</span>
                    </multiselect>
                </div>

                <div v-else>
                    <div v-if="param.Type === 'bool'">
                        <div v-for="(val, idx) in formValues[currentStep.ID][param.Name]" :key="idx" class="bx-multiple-row">
                            <multiselect v-model="formValues[currentStep.ID][param.Name][idx]" :options="boolOptions" label="name" track-by="value" :multiple="false" :close-on-select="true" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                                <span slot="noResult">Такого варианта нет</span>
                            </multiselect>
                            <span :class="['bx-remove-field', { 'bx-remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeField(currentStep.ID, param.Name, idx)">✕</span>
                        </div>
                        <button type="button" class="bx-add-btn" @click="addField(currentStep.ID, param.Name)">+ Добавить ещё</button>
                    </div>

                    <div v-else-if="param.Type === 'select'">
                        <div v-for="(val, idx) in formValues[currentStep.ID][param.Name]" :key="idx" class="bx-multiple-row">
                            <multiselect v-model="formValues[currentStep.ID][param.Name][idx]" :options="getSelectOptions(param)" label="name" track-by="value" :multiple="false" :close-on-select="true" placeholder="Выберите значение" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                                <span slot="noResult">Такого варианта нет</span>
                            </multiselect>
                            <span :class="['bx-remove-field', { 'bx-remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeField(currentStep.ID, param.Name, idx)">✕</span>
                        </div>
                        <button type="button" class="bx-add-btn" @click="addField(currentStep.ID, param.Name)">+ Добавить ещё</button>
                    </div>

                    <div v-else-if="param.Type === 'user'">
                        <div v-for="(val, idx) in formValues[currentStep.ID][param.Name]" :key="idx" class="bx-multiple-row">
                            <multiselect v-model="formValues[currentStep.ID][param.Name][idx]" :options="allUsers" label="name" track-by="value" :multiple="false" :close-on-select="true" placeholder="Выберите пользователя" @open="resizeBx" @close="resizeBx" @input="resizeBx">
                                <span slot="noResult">Такого варианта нет</span>
                            </multiselect>
                            <span :class="['bx-remove-field', { 'bx-remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeField(currentStep.ID, param.Name, idx)">✕</span>
                        </div>
                        <button type="button" class="bx-add-btn" @click="addField(currentStep.ID, param.Name)">+ Добавить ещё</button>
                    </div>

                    <div v-else>
                        <div v-for="(val, idx) in formValues[currentStep.ID][param.Name]" :key="idx" class="bx-multiple-row">
                            <input :type="param.Type === 'number' ? 'number' : (param.Type === 'datetime' ? 'datetime-local' : 'text')" class="ui-input" v-model="formValues[currentStep.ID][param.Name][idx]" @input="resizeBx"/>
                            <span :class="['bx-remove-field', { 'bx-remove-field--placeholder': idx === 0 }]" @click="idx > 0 && removeField(currentStep.ID, param.Name, idx)">✕</span>
                        </div>
                        <button type="button" class="bx-add-btn" @click="addField(currentStep.ID, param.Name)">+ Добавить ещё</button>
                    </div>
                </div>
            </div>

            <button class="ui-btn ui-btn-success bx-run-bp-btn" :disabled="!isChainStepValid" @click="runChainStep">
                <i class="fas fa-play"></i>
                Запустить БП
            </button>
        </div>
    </div>

    <!-- Основная кнопка -->
    <div v-else class="bx-crm-button-container">
        <button data-id="<? echo $buttonId;?>" class="ui-btn bx-crm-main-btn" @click="runActions">
            <span v-if="showIcon" v-html="buttonIconHtml"></span>
            <span>{{ buttonText }}</span>
        </button>
    </div>
    
    <!-- Лоадер -->
    <div v-if="loader" class="bx-modal-mask">
        <div class="bx-loader-spinner"></div>
    </div>
</div>

<script>
    window.__bxReady = new Promise(resolve => {
        BX24.ready(() => {
            resolve(window.BX24);
        });
    });
</script>
<script type="module" src="../7/buttonHandlers/js/script.js?v=<?= time() ?>"></script>
</body>
</html>