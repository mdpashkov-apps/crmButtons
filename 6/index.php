<?
include_once(__DIR__.'/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
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
    <script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/c9f5eeb571.js" crossorigin="anonymous"></script>
</head>
<body>
    <? $member_id = $_REQUEST['member_id']; ?>
    <script>
        window.memberId = '<?echo $member_id?>'
    </script>
    <div id="app">
        <!-- Шапка приложения -->
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
                <a href="https://t.me/appsupportbot" target="_blank">Обратная связь</a>
            </div>
        </div>
        <!-- кнопки -->
        <div v-if="portalButtons.length === 0" >
            <div  class="tabs_btn">
                <img class="addProfiles" src="img/Add.svg" alt="Добавить профиль" @click="createBtn">
                <div class="tab_btn still_btn_active"> {{ current_button.buttonName_FIELDS }} </div>           
            </div>
        </div>
        <div v-else >
            <div class="tabs_btn">
                <img class="addProfiles" src="img/Add.svg" alt="Добавить профиль" @click="createBtn">
                <div v-for="button in portalButtons.slice(0,6)" class="tab_btn" :class="{ still_btn_active: activeButtonId === button.ID }" :id="button.ID" @click="selectButton(button)"> {{ activeButtonId === button.ID ? current_button.buttonName_FIELDS : button.PROPERTY_VALUES.buttonName_FIELDS }}
                </div>
                <div v-if="newButton && totalButtonsCount <= 6" class="tab_btn still_btn_active"> {{ current_button.buttonName_FIELDS }} </div>
                <div v-if="totalButtonsCount > 6" class="dropdown" @mouseenter="showMoreButtons" @mouseleave="hideMoreButtons"> Еще <span class="span_btn">&or;</span>
                    <div class="dropdown-content" :class="{ show: showMore }">
                        <div v-for="button in morebuttons" :key="button.ID" class="tab_btn" :class="{ still_btn_active: activeButtonId === button.ID }" @click="selectButton(button)"> {{ activeButtonId === button.ID ? current_button.buttonName_FIELDS : button.PROPERTY_VALUES.buttonName_FIELDS }}
                        </div>
                        <div v-if="newButton && totalButtonsCount > 6" class="tab_btn still_btn_active"> {{ current_button.buttonName_FIELDS }} </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content">
            <!-- Левый контейнер с настройками кнопки  -->
            <div class="settings_fields">
                <h3>Панель настроек добавляемой кнопки </h3>
                <div class="div_row">
                    <label for="name">Название кнопки:</label>
                    <input v-model="current_button.buttonName_FIELDS" type="text" id="name">
                </div>
                <div class="div_row">
                    <label for="color_btn">Цвет кнопки:</label>
                    <input v-model="current_button.buttonColor_FIELDS" type="color" id="color_btn">
                </div>
                <div class="div_row">
                    <label for="color_text">Цвет текста:</label>
                    <input v-model="current_button.textColor_FIELDS" type="color" id="color_text">
                </div>
                <div class="div_row">
                    <label for="radius_btn">Радиус скругления углов кнопки:</label>
                    <input max="20" min="0" v-model="current_button.buttonRadius_FIELDS" type="number" id="radius_btn">
                </div>
                <div class="div_row">
                    <label for="buttonBorderSelection">Граница кнопки:</label>
                    <div>
                        <input v-model="current_button.buttonBorder_FIELDS" type="checkbox" id="buttonBorderSelection">
                    </div>
                </div>
                <div v-if="current_button.buttonBorder_FIELDS" class="div_row">
                    <label for="buttonBorderWidth">Высота границы:</label>
                    <input v-model="current_button.buttonBorderWidth_FIELDS" max="5" min="0" type="number" id="buttonBorderWidth" >
                </div>
                <div v-if="current_button.buttonBorder_FIELDS" class="div_row">
                    <label for="buttonBorderColor">Цвет границы:</label>
                    <input v-model="current_button.buttonBorderColor_FIELDS" type="color" id="buttonBorderColor">
                </div>
                <div class="div_row">
                    <label for="text_btn">Tекст на кнопке:</label>
                    <input maxlength="32" v-model="current_button.textOnTheButton_FIELDS" type="text" id="text_btn">
                </div>
                <div class="div_row">
                    <label for="icon_in_front_of_text">Иконка на кнопке перед текстом:</label>
                    <div>
                        <input v-model="current_button.usingTheIcon_FIELDS" type="checkbox" id="icon_in_front_of_text">
                    </div>
                </div>
                <div v-if="current_button.usingTheIcon_FIELDS" class="div_row">
                    <label for="selection_icon">Выбор иконки:</label>
                    <input v-model="current_button.iconOnTheButton_FIELDS" type="text" id="selection_icon">
                </div>
                <div class="div_row">
                    <label for="entity_selection">Для какой сущности:</label>
                    <multiselect v-model="current_button.entitySelection_FIELDS" :options="allEntitys" label="name" track-by="value" deselect-label="" select-label="Выбрать" selected-label="" open-direction="bottom" :multiple="false" :close-on-select="true" :limit="1" placeholder="Выберите сущность" :taggable="false" @input="onEntityChange" @open="getEntitys"> <span slot="noResult"> Такого варианта нет </span> </multiselect>
                </div>

                <!-- Левый контейнер с настройками действий  -->
                <h3>Действие кнопки</h3>
                    <button class="accordion" @click="bpSettings">Запустить БП </button>
                    <div class="panel" :class="{panel_show:accordion_0}" v-if='flagsButtonBizproc'>
                        <div class="div_row">
                            <label for="activate_the_property_0">Активировать свойство:</label>
                            <div>
                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" id="activate_the_property_0" :value="0">
                            </div>
                        </div>
                        <div class="div_row">
                            <label for="selection_BP"> Выбор доступных бизнес-процессов<br>для запуска в рамках выбранных сущностей: </label>
                            <multiselect v-model="current_button.businessProcessesValue_FIELDS" name="selection_BP" placeholder="Выберите БП" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" open-direction="bottom" :options="allBizProc" :multiple="true" :taggable="false" :close-on-select="false" :limit="1" @open="getBP"> <span slot="noResult"> Такого варианта нет </span>
                            </multiselect>
                        </div>
                    </div>

                    <button class="accordion" @click="documentSettings"> Cоздание документа </button>
                    <div class="panel" :class="{panel_show:accordion_1}" v-if='flagsButtonDocument'>
                        <div class="div_row">
                            <label for="activate_the_property_1">Активировать свойство:</label>    
                            <div>
                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" id="activate_the_property_1" :value="1">
                            </div>     
                        </div>
                        <div class="div_row">
                            <label for="selection_document">Выберите шаблон документ:</label>
                            <multiselect v-model="current_button.documentTemplatesValue_FIELDS" name="selection_document" placeholder="Выберите шаблон" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" open-direction="bottom" :options="allDocuments" :multiple="true" :taggable="false" :close-on-select="false" :limit="1" @open="getDocs">  <span slot="noResult"> Такого варианта нет </span>
                            </multiselect>
                        </div>
                    </div>

                    <button class="accordion" @click="listSettings" > Создать элемент Списка </button>
                    <div class="panel" :class="{panel_show:accordion_2}" v-if='flagsList'>
                        <div class="div_row">
                            <label for="activate_the_property_2">Активировать свойство:</label>
                             <div>
                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" id="activate_the_property_2" :value="2">
                            </div>
                        </div>
                        <div class="div_row">
                            <label for="selection_list">Выберите список:</label>
                            <multiselect v-model="current_button.listsValue_FIELDS" name="selection_list" placeholder="Выберите список" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" open-direction="bottom" :options="allLists" :multiple="false" :taggable="false" :close-on-select="true" :limit="1"  @open="getLists" @input="onListChange"> <span slot="noResult"> Такого варианта нет </span>
                            </multiselect>
                        </div>
                        <table v-if="current_button.listsValue_FIELDS">
                            <caption>Таблица соответствий полей в рамках выбранных сущностей</caption>
                                <tr>
                                    <th>Поля списка</th>
                                    <th>Поля сущности</th>
                                </tr>
                                <tr v-for="field in current_button.fieldsTable_FIELDS">
                                    <td>{{field.name}}</td>
                                    <td>
                                        <multiselect v-model="field.entField" placeholder="Выберите поле" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" open-direction="bottom" :options="entFields" :multiple="false" :taggable="false" :close-on-select="true" :limit="1" @open="getEntFields"> <span slot="noResult"> Такого варианта нет </span>
                                        </multiselect>
                                    </td>
                                </tr>
                        </table>
                    </div>

                    <button class="accordion" @click="followEnteredLink"> Перейти по произвольной ссылке </button>
                    <div class="panel" :class="{panel_show:accordion_3}" v-if='flagsButtonEnteredLink'>
                        <div class="div_row">
                            <label for="activate_the_property_3">Активировать свойство:</label> 
                             <div>
                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" id="activate_the_property_3" :value="3">
                            </div>
                        </div>
                        <div class="div_row">
                            <label for="link_pole">Введите ссылки, по которой необходимо перейти после клика на кнопку:</label>
                            <input v-model="current_button.link_FIELDS" type="text" id="link_pole">
                        </div>
                    </div>

                    <button class="accordion" @click="followCrmLink"> Перейти по ссылке из поля в CRM </button>
                    <div class="panel" :class="{panel_show:accordion_4}" v-if="flagsButtonCrmLink">
                        <div class="div_row">
                            <label for="activate_the_property_4">Активировать свойство:</label>
                             <div>
                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" id="activate_the_property_4" :value="4"> 
                            </div>
                        </div>
                        <div class="div_row">
                            <label for="selection_document">Выберите поля с типом ссылка:</label>
                            <multiselect v-model="current_button.crmLinkFields_FIELDS" name="selection_crmLinkFields" placeholder="Выберите поля" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" open-direction="bottom" :options="allCrmFieldsLink" :multiple="false" :taggable="false" :close-on-select="false" :limit="1" @open="getCrmLinks"> <span slot="noResult"> Такого варианта нет </span>
                            </multiselect>
                        </div>
                    </div>

                <!-- Кнопки -->
                <div class="div_btn">
                    <button id="btn_delete" @click="delButton"> Удалить настройки и поле </button>
                    <button @click="saveSettings"> Сохранить настройки </button>
                    <button @click="deleteBtnCrm('<?= (string)$_GET['DOMAIN']; ?>')" class="deleteBtnCrm" v-if="current_button.buttonInCRM_FIELDS">Удалить кнопку в карточках CRM</button>
                    <button v-else @click="createBtnCrm('<?= (string)$_GET['DOMAIN']; ?>')">Создать кнопку в карточках CRM</button>
                </div>
            </div>
            <div class="button_type">
                <h4>Внешний вид кнопки</h4>
                <button style="height:auto;padding: 8px 0;"  :style="{color: current_button.textColor_FIELDS, backgroundColor: current_button.buttonColor_FIELDS,borderRadius: current_button.buttonRadius_FIELDS + 'px', border: current_button.buttonBorder_FIELDS ? `${current_button.buttonBorderWidth_FIELDS}px solid ${current_button.buttonBorderColor_FIELDS}`: 'none'}">
                    <span v-if="current_button.usingTheIcon_FIELDS">{{current_button.iconOnTheButton_FIELDS}}</span>
                    {{current_button.textOnTheButton_FIELDS}}
                </button>
                <button class="standart_styles" @click="SetStandardStyles"> Применить штатный стиль </button>
                <div class="control_buttons">
                    <button @click="resetStylesButton">Отменить выбранные стили</button>
                </div>
            </div>
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
  console.log('1ое хранилище ',result);
});
</script>