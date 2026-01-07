<?

if (!is_null($_GET['REQUEST'])) {
    $_REQUEST = json_decode($_GET['REQUEST'], 1);
}
include_once(__DIR__ . '/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
$versions = basename(__DIR__);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <script src="//api.bitrix24.com/api/v1/"></script>
    <script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://gcore.jsdelivr.net/npm/vue/dist/vue.min.js"></script>
    <!-- <script src="https://unpkg.com/vue-multiselect@2.1.0"></script> -->
    <script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css">
    <!-- <script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script> -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <!-- <script src="https://kit.fontawesome.com/c9f5eeb571.js" crossorigin="anonymous"></script> -->
    <script type="text/x-template" id="modal-template">
        <transition name="modal">
            <div class="modal-mask">
                <div class="modal-wrapper">
                    <div class="modal-container">
                        <div class="modal-header">
                            <slot name="header">
                            </slot>
                        </div>
                        <div class="modal-footer">
                            <slot name="footer">
                                <button @click="$emit('close')">Нет</button>
                            </slot>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </script>
    <title>VUE</title>
</head>

<body>
<?
$member_id = $_REQUEST['member_id']; ?>
<script>
    window.memberId = '<?echo $member_id?>'
    window.versions = '<?echo $versions?>'
</script>
<div id="app">
    <div class="top-container">
        <div class="logo-con">
            <a target="_blank" href="https://overplan.ru/?utm_source=b24app" title="overplan.ru" class="logo">
                <img src="img/logo_overplan.png">
            </a>
        </div>
        <div class="feedback-con">
            <a href='./index.php?REQUEST=<?= json_encode($_REQUEST) ?>'>Настройка приложения</a>
        </div>
        <div class="feedback-con">
            <a href='./indexReports.php?REQUEST=<?= json_encode($_REQUEST) ?>'>Настроить уведомления</a>
        </div>
        <div class="feedback-con">
            <a href="https://t.me/appsupportbot" target="_blank">Обратная связь</a>
        </div>
    </div>

    <!-- {{rows}} -->
    <div class="tabs_btn" v-if="current_button && current_button.button_actions">
        <img class="addProfiles" src="img/Add.svg" alt="Добавить профиль" @click="createBtn">
        <div v-for="row in rows.slice(0,6)" class="tab_btn" :id=" [row.id]"
             :class="{still_btn_active:row.lists_btn_bool}" @click="open_lists_btn(row)">
            {{row.name}}
        </div>
        <div v-if="rows.length > 6" v-on:mouseenter="show" v-on:mouseleave="hide" class="dropdown"
             :class="{still_block_active:lists_btn_bool}">
            Еще
            <span class="span_btn">
                    &or;
                </span>
            <div id="myDropdown" class="dropdown-content" :class="{show:lists_btn_bool}">
                <div v-for="row in rows.slice(6)" :class="{still_btn_active:row.lists_btn_bool}"
                     @click="open_lists_btn(row)">
                    {{row.name}}
                </div>
            </div>
        </div>
    </div>




    
    <div class="content" v-if="current_button && current_button.button_actions">
        <div class="settings_fields">
            <h3>Панель настроек добавляемой кнопки</h3>
            <div class="div_row">
                <label for="name">Название кнопки:</label>
                <input v-model="current_button.name" type="text" id="name">
            </div>
            <div class="div_row">
                <label for="color_btn">Цвет кнопки:</label>
                <input v-model="current_button.color_btn" type="color" id="color_btn">
            </div>
            <div class="div_row">
                <label for="color_text">Цвет текста:</label>
                <input v-model="current_button.color_text" type="color" id="color_text">
            </div>
            <div class="div_row">
                <label for="radius_btn">Радиус скругления углов кнопки:</label>
                <input max="20" min="0" v-model="current_button.radius_btn"
                       @change="current_button.radius_btn = validator(0, 20,current_button.radius_btn )" type="number"
                       id="radius_btn">
            </div>
            <div class="div_row">
                <label for="buttonBorderSelection">Граница кнопки:</label>
                <div>
                    <input v-model="current_button.buttonBorderSelection" type="checkbox" id="buttonBorderSelection">
                </div>
            </div>
            <div v-if="current_button.buttonBorderSelection" class="div_row">
                <label for="buttonBorderWidth">Высота границы:</label>
                <input v-model="current_button.buttonBorderWidth" max="5" min="0" type="number" id="buttonBorderWidth"
                       @change="current_button.buttonBorderWidth = validator(0, 5,current_button.buttonBorderWidth)">
            </div>
            <div v-if="current_button.buttonBorderSelection" class="div_row">
                <label for="buttonBorderColor">Цвет границы:</label>
                <input v-model="current_button.buttonBorderColor" type="color" id="buttonBorderColor">
            </div>
            <div class="div_row">
                <label for="text_btn">Tекст на кнопке:</label>
                <input maxlength="32" v-model="current_button.text_btn" type="text" id="text_btn">
            </div>
            <div class="div_row">
                <label for="icon_in_front_of_text">Иконка на кнопке перед текстом:</label>
                <div>
                    <input v-model="current_button.use_icon" type="checkbox" id="icon_in_front_of_text">
                </div>
            </div>
            <div v-if="current_button.use_icon" class="div_row">
                <label for="selection_icon">Выбор иконки:</label>
                <input v-model="current_button.icon_btn" type="text" id="selection_icon">
            </div>
            <div class="div_row">
                <label for="entity_selection">Для какой сущности:</label>
                <multiselect v-model="current_button.array_entities_value" name="entity_selection"
                             placeholder="Выберите сущность" label="name" track-by="value" deselect-label=""
                             select-label="Выбрать" selected-label="" open-direction="bottom"
                             :options="current_button.array_entities" :multiple="false" :taggable="false"
                             :close-on-select="true" :limit="1" @remove="removeAnEntity" @select="changingAnEntity">
                        <span slot="noResult">
                            Такого варианта нет
                        </span>
                </multiselect>
            </div>
            <h3>Действие кнопки</h3>
            <button class="accordion" :disabled="disableBizproc" @click="selectedBusinessProcessEventClick(0)">
                Запустить БП
                <div v-if="disableBizproc" class="loader-button"></div>
          </button>
            <div class="panel" :class="{panel_show:accordion_0}" v-if='flagsButtonBizproc'>
                <div class="div_row">
                    <label for="activate_the_property_0">Активировать свойство:</label>
                    <div>
                        <input v-model="current_button.button_actions.id" type="checkbox" id="activate_the_property_0"
                               value="0">
                    </div>
                </div>
                <div class="div_row">
                    <label for="selection_BP">
                        Выбор доступных бизнес-процессов<br>для запуска в рамках выбранных сущностей:
                    </label>
                    <multiselect v-model="current_button.button_actions.business_processes.value" name="selection_BP"
                                 placeholder="Выберите БП"
                                 label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать"
                                 selected-label="" open-direction="bottom"
                                 :options="current_button.button_actions.business_processes.options"
                                 :multiple="true" :taggable="false" :close-on-select="false" :limit="1"
                    >
                            <span slot="noResult">
                                Такого варианта нет
                            </span>
                    </multiselect>
                </div>
            </div>
            <button class="accordion"  :disabled="disableDocumentTemplate" @click="selectedDocumentTemplateEventClick(1)">
                Cоздание документа
                <div v-if="disableDocumentTemplate" class="loader-button"></div>
            </button>
            <div class="panel" :class="{panel_show:accordion_1}" v-if='flagsDocumentTemplate'>
                <div class="div_row">
                    <label for="activate_the_property_1">Активировать свойство:</label>
                    <div>
                        <input v-model="current_button.button_actions.id" type="checkbox" id="activate_the_property_1"
                               value="1">
                    </div>
            </div>
                <div class="div_row">
                    <label for="selection_document">Выберите шаблон документ:</label>
                    <multiselect v-model="current_button.button_actions.document_templates.value"
                                 name="selection_document"
                                 placeholder="Выберите шаблон" label="name" track-by="value" deselect-label="Убрать"
                                 select-label="Выбрать" selected-label="" open-direction="bottom"
                                 :options="current_button.button_actions.document_templates.options" :multiple="true"
                                 :taggable="false"
                                 :close-on-select="false" :limit="1">
                            <span slot="noResult">
                                Такого варианта нет
                            </span>
                    </multiselect>
                </div>
            </div>
            <button class="accordion" :disabled="disableList" @click="selectedListEventClick(2)" >
                Создать элемент Списка
                <div v-if="disableList" class="loader-button"></div>
            </button>
            <div class="panel" :class="{panel_show:accordion_2}" v-if='flagsList'>
                <div class="div_row">
                    <label for="activate_the_property_2">Активировать свойство:</label>
                    <div>
                        <input v-model="current_button.button_actions.id" type="checkbox" id="activate_the_property_2"
                               value="2">
                    </div>
                </div>
                <div class="div_row">
                    <label for="selection_list">Выберите список:</label>
                    <multiselect v-model="current_button.button_actions.lists.value" name="selection_list" placeholder="Выберите список" label="name"
                                 track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label=""
                                 open-direction="bottom" :options="current_button.button_actions.lists.options" :multiple="false" :taggable="false"
                                 :close-on-select="true" :limit="1" @remove="listRemove" @select="listSelection">
                            <span slot="noResult">
                                Такого варианта нет
                            </span>
                    </multiselect>
                </div>
                <table v-if="current_button.button_actions.fields_table.length !== 0">
                    <caption>Таблица соответствий полей в рамках выбранных сущностей</caption>
                    <tr>
                        <th>Поля списка</th>
                        <th>Поля сущности</th>
                    </tr>
                    <tr v-for="row in current_button.button_actions.fields_table">
                        <td>{{row.fieldsLists.name}}<span v-if="row.fieldsLists.isRequired == 'Y'"
                                                          style="color:red;">*</span></td>
                        <td>
                            <multiselect v-model="row.fieldsEntiyValue" placeholder="Выберите поле" label="name"
                                         track-by="value" deselect-label="Убрать" select-label="Выбрать"
                                         selected-label="" open-direction="bottom"
                                         :options="current_button.button_actions.optionsEntity" :multiple="false"
                                         :taggable="false" :close-on-select="true" :limit="1">
                                    <span slot="noResult">
                                        Такого варианта нет
                                    </span>
                            </multiselect>
                        </td>
                    </tr>
                </table>
            </div>
            <button class="accordion" @click="selectedArbitraryLink()">
                Перейти по произвольной ссылке
          </button>
            <div class="panel" :class="{panel_show:accordion_3}" v-if='flagAttributeLink'>
                <div class="div_row">
                    <label for="activate_the_property_3">Активировать свойство:</label>
                    <div>
                        <input v-model="current_button.button_actions.id" type="checkbox" id="activate_the_property_3"
                               value="3">
                    </div>
                </div>
                <div class="div_row">
                    <label for="link_pole">Введите ссылки, по которой необходимо перейти после клика на
                        кнопку:</label>
                    <input v-model="current_button.button_actions.link" type="text" id="link_pole">
                </div>
            </div>
            <button class="accordion"  :disabled="disableCrmLinkFields" @click="selectedCrmFieldsLinkEventClick(4)">
                Перейти по ссылке из поля в CRM
                <div v-if="disableCrmLinkFields" class="loader-button"></div>
          </button>
            <div class="panel" :class="{panel_show:accordion_4}" v-if="flagsCrmLinkFields">
                <div class="div_row">
                    <label for="activate_the_property_4">Активировать свойство:</label>
                    <div>
                        <input v-model="current_button.button_actions.id" type="checkbox" id="activate_the_property_4"
                               value="4">
                    </div>
                </div>
                <div class="div_row">
                    <label for="selection_document">Выберите поля с типом ссылка:</label>
                    <multiselect v-model="current_button.button_actions.crmLinkFilds.value" name="selection_crmLinkFields"
                                 placeholder="Выберите поля" label="name" track-by="value" deselect-label="Убрать"
                                 select-label="Выбрать" selected-label="" open-direction="bottom"
                                 :options="current_button.button_actions.crmLinkFilds.options" :multiple="false" :taggable="false"
                                 :close-on-select="false" :limit="1">
                            <span slot="noResult">
                                Такого варианта нет
                            </span>
                    </multiselect>
                </div>
          </div>
            <div class="div_btn">
                <button id="btn_delete" @click="showModal = true">
                    Удалить настройки и поле
                </button>
                <button @click="saving_settings('<?
                echo (string)$_GET['DOMAIN']; ?>')">
                    Сохранить настройки
                </button>
                <button v-if="!current_button.button_actions.button_in_CRM" @click="addBtn(current_button.id, '<?
                echo (string)$_GET['DOMAIN']; ?>')">
                    Создать кнопку в карточках CRM
                </button>
                <button v-else class="deleteBtnCrm" @click="addBtn(current_button.id, '<?
                echo (string)$_GET['DOMAIN']; ?>')">
                    Удалить кнопку в карточках CRM
                </button>
            </div>
        </div>
        <div class="button_type">
            <h4>Внешний вид кнопки</h4>
            <!-- <h5>Вид штатного дизайна кнопки</h5> -->
            <button style="height:auto; padding: 8px 0;" :style="{
                    color: current_button.color_text,
                    backgroundColor: current_button.color_btn,
                    borderRadius: current_button.radius_btn + 'px',
                    borderStyle:calculateBooksMessage,
                    borderWidth: current_button.buttonBorderWidth + 'px',
                    borderColor: current_button.buttonBorderColor,                   
                }">
                <span v-if="current_button.use_icon">{{current_button.icon_btn}}</span>
                {{current_button.text_btn}}
            </button>
            <button class="standart_styles" @click="SetStandardStyles()">
                Применить штатный стиль
            </button>
            <!-- <h5>Вид вашего дизайна кнопки</h5> -->
            <div class="control_buttons">
                <button @click="saveStylesButton()">Сохранить стили</button>
                <button @click="resetStylesButton()">Сбросить стили</button>
            </div>
        </div>
        <!-- use the modal component, pass in the prop -->
        <modal v-if="showModal">
            <p slot="header">Вы уверены что хотите удалить "{{current_button.name}}"?</p>
            <div slot="footer" class="modal_btn">
                <button @click="deleteBtn('<?
                echo (string)$_GET['DOMAIN']; ?>'), showModal = false">Да
                </button>
                <button @click="showModal = false">Нет</button>
            </div>
        </modal>
    </div>
    <div v-if="loader" class="modal-mask">
        <div class="modal-wrapper">
            <div class="loader"></div>
        </div>
    </div>
</div>
<script type="module" src="js/script.js"></script>
</body>

</html>


<script>
BX24.callMethod('entity.item.get', {
  'ENTITY': 'customButton',
  'FILTER': {},
  'SELECT': ['*']
}, function(result) {
  console.log('3ое хранилище ',result);
});
</script>