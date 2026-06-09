<?
if (!is_null($_GET['REQUEST'])) {
    $_REQUEST = json_decode($_GET['REQUEST'], 1);
}
include_once(__DIR__.'/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Настройка кнопок CRM | Overplan</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Локальные файлы вместо CDN -->
    <link rel="stylesheet" href="libs/vue-multiselect.min.css">
    <script src="libs/vue-multiselect.min.js"></script>
    
    <!-- Библиотеки -->
    <script src="//api.bitrix24.com/api/v1/"></script>
    <script src="libs/axios.min.js"></script>
    <script src="libs/vue.min.js"></script>

    <!-- Font Awesome 6 Free - локально (без внешнего CDN) -->
    <link rel="stylesheet" href="libs/fontawesome/css/all.min.css?v=<?= @filemtime(__DIR__ . '/libs/fontawesome/css/all.min.css') ?: time() ?>">

    <!-- Основные стили -->
    <link rel="stylesheet" href="css/bitrix-design.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/mobile-adaptation.css?v=<?= time() ?>">
</head>
<body class="bitrix-ui">
    <? 
    $member_id = $_REQUEST['member_id'];
    $request_json = htmlspecialchars(json_encode($_REQUEST), ENT_QUOTES, 'UTF-8');
    ?>
    <script>
        window.__apiVersion = '<?= @filemtime(__DIR__ . '/js/api.js') ?: time() ?>';
        window.memberId = '<?echo $member_id?>';
        window.BX24 = BX24;
        window.isMobile = false;
        
        // Определение мобильного устройства и адаптация
        if (typeof BX24 !== 'undefined' && BX24.init) {
            BX24.init(function() {
                if (BX24.isMobile && BX24.isMobile()) {
                    window.isMobile = true;
                    document.body.classList.add('bx-mobile');
                    
                    setTimeout(function() {
                        if (typeof BX24.resizeWindow === 'function') {
                            var height = document.body.scrollHeight;
                            BX24.resizeWindow(null, height + 50);
                        }
                    }, 100);
                }
            });
        } else {
            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                window.isMobile = true;
                document.body.classList.add('bx-mobile');
            }
        }
        
        // Функция для уведомлений
        window.showNotification = function(message, type = 'info') {
            if (window.BX24 && BX24.showNotify) {
                BX24.showNotify({
                    content: message,
                    autoHideDelay: 3000
                });
            } else {
                const notification = document.createElement('div');
                notification.textContent = message;
                notification.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    right: 20px;
                    background: ${type === 'error' ? '#f1361b' : '#2fc6f6'};
                    color: white;
                    padding: 12px;
                    border-radius: 8px;
                    text-align: center;
                    z-index: 10000;
                    animation: slideUp 0.3s ease;
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            }
        };
        
        window.showLoader = function(text = 'Загрузка...') {
            if (window.isMobile && window.BX24 && BX24.showLoading) {
                BX24.showLoading({ text: text });
            }
        };
        
        window.hideLoader = function() {
            if (window.isMobile && window.BX24 && BX24.hideLoading) {
                BX24.hideLoading();
            }
        };
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideUp {
                from {
                    transform: translateY(100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
    
    <div id="app">
        <!-- Шапка приложения -->
        <div class="bx-app-header">
            <div class="bx-app-header__container">
                <div class="bx-app-header__logo">
                    <a href="https://overplan.ru/?utm_source=b24app" target="_blank" class="bx-app-header__logo-link">
                        <img src="img/logo_overplan.png" alt="Overplan" class="bx-app-header__logo-img">
                    </a>
                </div>
                <div class="bx-app-header__nav">
                    <a href="./index.php?REQUEST=<?= $request_json ?>" class="bx-app-header__link bx-app-header__link--active">
                        <span class="bx-icon bx-icon--settings"></span>
                        <span class="bx-app-header__link-text">Настройка приложения</span>
                    </a>
                    <a href="./indexReports.php?REQUEST=<?= $request_json ?>" class="bx-app-header__link">
                        <span class="bx-icon bx-icon--notification"></span>
                        <span class="bx-app-header__link-text">Настройка уведомлений</span>
                    </a>
                    <a href="https://t.me/appsupportbot" target="_blank" class="bx-app-header__link">
                        <span class="bx-icon bx-icon--chat"></span>
                        <span class="bx-app-header__link-text">Обратная связь</span>
                    </a>
                </div>
                <button class="bx-app-header__mobile-toggle" @click="toggleMobileMenu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
        
        <div class="bx-app-content">
            <!-- Баннер -->
            <div class="bx-banner">
                <i class="bx-banner__icon fa-solid fa-bullseye"></i>
                <div class="bx-banner__text">
                    Запишитесь на бесплатную подборку полезных приложений для вашего портала
                </div>
                <a href="https://t.me/appsupportbot" target="_blank" class="ui-btn ui-btn-sm ui-btn-primary bx-banner__btn">
                    Записаться
                </a>
            </div>
            
            <!-- Вкладки профилей -->
            <div class="bx-tabs">
                <div class="bx-tabs__container">
                    <button class="bx-tabs__add bx-tabs__add--highlight"
                            :disabled="buttonsAtLimit"
                            :class="{ 'bx-tabs__add--disabled': buttonsAtLimit }"
                            @click="createBtn"
                            :title="buttonsAtLimit ? 'Лимит free-тарифа исчерпан' : 'Создать кнопку'">
                        <span class="bx-tabs__add-icon">+</span>
                        <span class="bx-tabs__add-text">Создать</span>
                    </button>
                    <div v-if="buttonLimit !== null" class="bx-buttons-counter" :class="{ 'bx-buttons-counter--limit': buttonsAtLimit }">
                        <i class="fa-solid fa-layer-group"></i>
                        {{ buttonsUsed }} / {{ buttonLimit }}
                        <span v-if="buttonsAtLimit" class="bx-buttons-counter__hint">— лимит free</span>
                    </div>
                    <div class="bx-tabs__list" ref="tabsList">
                        <div v-for="button in portalButtons.slice(0,6)" 
                             class="bx-tabs__item" 
                             :class="{ 'bx-tabs__item--active': activeButtonId === button.ID }"
                             @click="selectButton(button)">
                            {{ activeButtonId === button.ID ? current_button.buttonName_FIELDS : button.PROPERTY_VALUES.buttonName_FIELDS }}
                        </div>
                        <div v-if="newButton && totalButtonsCount <= 6" class="bx-tabs__item bx-tabs__item--new">
                            {{ current_button.buttonName_FIELDS }}
                        </div>
                        <div v-if="totalButtonsCount > 6" class="bx-tabs__dropdown" v-click-outside="hideMoreButtons">
                            <span class="bx-tabs__dropdown-toggle" @click="toggleMoreButtons">
                                Ещё <span class="bx-tabs__dropdown-arrow">▼</span>
                            </span>
                            <div class="bx-tabs__dropdown-menu" v-show="showMore">
                                <div v-for="button in morebuttons" 
                                     class="bx-tabs__dropdown-item"
                                     :class="{ 'bx-tabs__dropdown-item--active': activeButtonId === button.ID }"
                                     @click="selectButton(button)">
                                    {{ button.PROPERTY_VALUES.buttonName_FIELDS }}
                                </div>
                                <div v-if="newButton" class="bx-tabs__dropdown-item bx-tabs__dropdown-item--new">
                                    {{ current_button.buttonName_FIELDS }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Основная сетка -->
            <div class="bx-grid">
                <!-- Левая панель -->
                <div class="bx-grid__col bx-grid__col--main">
                    <div class="bx-card">
                        <div class="bx-card__header">
                            <h2 class="bx-card__title">Панель настроек кнопки</h2>
                        </div>
                        <div class="bx-card__body">
                            <!-- Основные настройки -->
                            <div class="bx-form-section">
                                <div class="bx-form-row">
                                    <label class="bx-label">Название кнопки</label>
                                    <div class="bx-control">
                                        <input v-model="current_button.buttonName_FIELDS" type="text" class="ui-input" placeholder="Введите название">
                                    </div>
                                </div>
                                
                                <div class="bx-form-row bx-form-row--2cols">
                                    <div class="bx-form-col">
                                        <label class="bx-label">Цвет кнопки</label>
                                        <input v-model="current_button.buttonColor_FIELDS" type="color" class="bx-color-input">
                                    </div>
                                    <div class="bx-form-col">
                                        <label class="bx-label">Цвет текста</label>
                                        <input v-model="current_button.textColor_FIELDS" type="color" class="bx-color-input">
                                    </div>
                                </div>
                                
                                <div class="bx-form-row">
                                    <label class="bx-label">Радиус скругления</label>
                                    <div class="bx-range-wrap">
                                        <input v-model="current_button.buttonRadius_FIELDS" type="range" min="0" max="20" class="bx-range">
                                        <span class="bx-range-value">{{ current_button.buttonRadius_FIELDS }}px</span>
                                    </div>
                                </div>
                                
                                <div class="bx-form-row">
                                    <label class="bx-checkbox">
                                        <input v-model="current_button.buttonBorder_FIELDS" type="checkbox" class="bx-checkbox__input">
                                        <span class="bx-checkbox__label">Использовать границу кнопки</span>
                                    </label>
                                </div>
                                
                                <div v-if="current_button.buttonBorder_FIELDS" class="bx-form-row bx-form-row--nested">
                                    <div class="bx-form-row bx-form-row--2cols">
                                        <div class="bx-form-col">
                                            <label class="bx-label">Толщина границы (px)</label>
                                            <input v-model="current_button.buttonBorderWidth_FIELDS" type="number" min="0" max="5" class="ui-input ui-input--sm">
                                        </div>
                                        <div class="bx-form-col">
                                            <label class="bx-label">Цвет границы</label>
                                            <input v-model="current_button.buttonBorderColor_FIELDS" type="color" class="bx-color-input">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bx-form-row">
                                    <label class="bx-label">Текст на кнопке</label>
                                    <div class="bx-control">
                                        <input v-model="current_button.textOnTheButton_FIELDS" type="text" maxlength="32" class="ui-input" placeholder="Текст кнопки">
                                    </div>
                                </div>
                                
                                <div class="bx-form-row">
                                    <label class="bx-checkbox">
                                        <input v-model="current_button.usingTheIcon_FIELDS" type="checkbox" class="bx-checkbox__input">
                                        <span class="bx-checkbox__label">Использовать иконку</span>
                                    </label>
                                </div>
                                
                                <div v-if="current_button.usingTheIcon_FIELDS" class="bx-form-row bx-form-row--nested">
                                    <label class="bx-label">Иконка (Font Awesome)</label>
                                    <input v-model="current_button.iconOnTheButton_FIELDS" type="text" class="ui-input" placeholder="">
                                </div>
                                
                                <div class="bx-form-row">
                                    <label class="bx-label">Сущность CRM</label>
                                    <div class="bx-control">
                                        <multiselect v-model="current_button.entitySelection_FIELDS" 
                                                     :options="allEntitys" 
                                                     label="name" 
                                                     track-by="value"
                                                     placeholder="Выберите сущность"
                                                     class="bx-multiselect"
                                                     @open="getEntitys"
                                                     @input="onEntityChange">
                                            <span slot="noResult">Ничего не найдено</span>
                                        </multiselect>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Действия кнопки -->
                            <div class="bx-accordion">
                                <div class="bx-accordion__item">
                                    <button class="bx-accordion__header" @click="toggleAccordion(0)">
                                        <i class="bx-accordion__icon fa-solid fa-gear"></i>
                                        <span>Запустить бизнес-процесс</span>
                                        <span class="bx-accordion__arrow" :class="{ 'bx-accordion__arrow--open': accordion_0 }">▼</span>
                                    </button>
                                    <div class="bx-accordion__body" :class="{ 'bx-accordion__body--open': accordion_0 }">
                                        <div class="bx-accordion__content">
                                            <label class="bx-checkbox">
                                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" :value="0" class="bx-checkbox__input">
                                                <span class="bx-checkbox__label">Активировать свойство</span>
                                            </label>
                                            <div class="bx-mt-12">
                                                <multiselect v-model="current_button.businessProcessesValue_FIELDS"
                                                             :options="allBizProc"
                                                             label="name"
                                                             track-by="value"
                                                             placeholder="Выберите БП"
                                                             :multiple="true"
                                                             class="bx-multiselect"
                                                             @open="getBP">
                                                    <span slot="noResult">Нет доступных БП</span>
                                                </multiselect>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bx-accordion__item">
                                    <button class="bx-accordion__header" @click="toggleAccordion(1)">
                                        <i class="bx-accordion__icon fa-solid fa-file-lines"></i>
                                        <span>Создание документа</span>
                                        <span class="bx-accordion__arrow" :class="{ 'bx-accordion__arrow--open': accordion_1 }">▼</span>
                                    </button>
                                    <div class="bx-accordion__body" :class="{ 'bx-accordion__body--open': accordion_1 }">
                                        <div class="bx-accordion__content">
                                            <label class="bx-checkbox">
                                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" :value="1" class="bx-checkbox__input">
                                                <span class="bx-checkbox__label">Активировать свойство</span>
                                            </label>
                                            <div class="bx-mt-12">
                                                <multiselect v-model="current_button.documentTemplatesValue_FIELDS" 
                                                             :options="allDocuments" 
                                                             label="name" 
                                                             track-by="value"
                                                             placeholder="Выберите шаблон"
                                                             :multiple="true"
                                                             class="bx-multiselect"
                                                             @open="getDocs">
                                                    <span slot="noResult">Нет шаблонов</span>
                                                </multiselect>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bx-accordion__item">
                                    <button class="bx-accordion__header" @click="toggleAccordion(2)">
                                        <i class="bx-accordion__icon fa-solid fa-clipboard-list"></i>
                                        <span>Создать элемент списка</span>
                                        <span class="bx-accordion__arrow" :class="{ 'bx-accordion__arrow--open': accordion_2 }">▼</span>
                                    </button>
                                    <div class="bx-accordion__body" :class="{ 'bx-accordion__body--open': accordion_2 }">
                                        <div class="bx-accordion__content">
                                            <label class="bx-checkbox">
                                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" :value="2" class="bx-checkbox__input">
                                                <span class="bx-checkbox__label">Активировать свойство</span>
                                            </label>
                                            <div class="bx-mt-12">
                                                <multiselect v-model="current_button.listsValue_FIELDS" 
                                                             :options="allLists" 
                                                             label="name" 
                                                             track-by="value"
                                                             placeholder="Выберите список"
                                                             class="bx-multiselect"
                                                             @open="getLists"
                                                             @input="onListChange">
                                                    <span slot="noResult">Нет списков</span>
                                                </multiselect>
                                            </div>
                                            <div v-if="current_button.listsValue_FIELDS" class="bx-table-wrap bx-mt-16">
                                                <table class="bx-table">
                                                    <caption class="bx-table__caption">Таблица соответствий полей</caption>
                                                    <thead>
                                                        <tr>
                                                            <th>Поля списка</th>
                                                            <th>Поля сущности</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="field in current_button.fieldsTable_FIELDS" :key="field.value">
                                                            <td>{{ field.name }}</td>
                                                            <td>
                                                                <multiselect v-model="field.entField" 
                                                                             placeholder="Выберите поле" 
                                                                             label="name" 
                                                                             track-by="value"
                                                                             :options="entFields"
                                                                             class="bx-multiselect bx-multiselect--sm"
                                                                             @open="getEntFields">
                                                                    <span slot="noResult">Нет полей</span>
                                                                </multiselect>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bx-accordion__item">
                                    <button class="bx-accordion__header" @click="toggleAccordion(3)">
                                        <i class="bx-accordion__icon fa-solid fa-link"></i>
                                        <span>Перейти по произвольной ссылке</span>
                                        <span class="bx-accordion__arrow" :class="{ 'bx-accordion__arrow--open': accordion_3 }">▼</span>
                                    </button>
                                    <div class="bx-accordion__body" :class="{ 'bx-accordion__body--open': accordion_3 }">
                                        <div class="bx-accordion__content">
                                            <div class="bx-info-text bx-mb-12">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Данное действие не работает совместно с действием "Перейти по ссылке из поля в CRM"
                                            </div>
                                            <label class="bx-checkbox">
                                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" :value="3" class="bx-checkbox__input">
                                                <span class="bx-checkbox__label">Активировать свойство</span>
                                            </label>
                                            <div class="bx-mt-12">
                                                <input v-model="current_button.link_FIELDS" type="text" class="ui-input" placeholder="https://example.com">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bx-accordion__item">
                                    <button class="bx-accordion__header" @click="toggleAccordion(7)">
                                        <i class="bx-accordion__icon fa-solid fa-diagram-project"></i>
                                        <span>Запустить цепочку бизнес-процессов</span>
                                        <span class="bx-pro-badge">Только в PRO</span>
                                        <span class="bx-accordion__arrow" :class="{ 'bx-accordion__arrow--open': accordion_7 }">▼</span>
                                    </button>
                                    <div class="bx-accordion__body" :class="{ 'bx-accordion__body--open': accordion_7 }">
                                        <div class="bx-accordion__content">
                                            <div class="bx-info-text bx-mb-12">
                                                <i class="fa-solid fa-circle-info"></i> Цепочка запускается строго в порядке списка. Для каждого шага можно заранее задать значения параметров — тогда они не будут спрашиваться у пользователя.
                                            </div>
                                            <label class="bx-checkbox">
                                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" :value="5" class="bx-checkbox__input">
                                                <span class="bx-checkbox__label">Активировать свойство</span>
                                            </label>
                                            <div class="bx-mt-12">
                                                <multiselect v-model="current_button.bpChainValue_FIELDS"
                                                             :options="allBizProc"
                                                             label="name"
                                                             track-by="value"
                                                             placeholder="Выберите БП — добавятся в цепочку"
                                                             :multiple="true"
                                                             class="bx-multiselect"
                                                             @open="getBP">
                                                    <span slot="noResult">Нет доступных БП</span>
                                                </multiselect>
                                            </div>
                                            <div v-if="Array.isArray(current_button.bpChainValue_FIELDS) && current_button.bpChainValue_FIELDS.length > 0" class="bx-mt-16">
                                                <label class="bx-label">Порядок запуска</label>
                                                <div class="bx-bp-chain">
                                                    <div v-for="(bp, idx) in current_button.bpChainValue_FIELDS" :key="bp.value" class="bx-bp-chain__item">
                                                        <div class="bx-bp-chain__row">
                                                            <span class="bx-bp-chain__idx">{{ idx + 1 }}</span>
                                                            <span class="bx-bp-chain__name">{{ bp.name }}</span>
                                                            <span class="bx-bp-chain__ctrls">
                                                                <button type="button" class="bx-bp-chain__btn" :disabled="idx === 0" @click="moveBpUp(idx)" title="Вверх">↑</button>
                                                                <button type="button" class="bx-bp-chain__btn" :disabled="idx === current_button.bpChainValue_FIELDS.length - 1" @click="moveBpDown(idx)" title="Вниз">↓</button>
                                                                <button type="button" class="bx-bp-chain__btn" @click="toggleBpExpand(bp)" :title="expandedBpInChain[bp.value] ? 'Свернуть параметры' : 'Параметры'">
                                                                    <i class="fa-solid fa-sliders"></i> {{ expandedBpInChain[bp.value] ? 'Скрыть' : 'Параметры' }}
                                                                </button>
                                                                <button type="button" class="bx-bp-chain__btn bx-bp-chain__btn--danger" @click="removeBpFromChain(idx)" title="Удалить из цепочки">✕</button>
                                                            </span>
                                                        </div>
                                                        <div v-if="expandedBpInChain[bp.value]" class="bx-bp-chain__body">
                                                            <div v-if="chainBpDefsLoading[bp.value]" class="bx-info-text">Загрузка параметров...</div>
                                                            <div v-else-if="!chainBpDefs[bp.value] || chainBpDefs[bp.value].length === 0" class="bx-info-text">У этого БП нет параметров.</div>
                                                            <div v-else>
                                                                <div v-for="param in chainBpDefs[bp.value]" :key="param.paramKey" class="bx-form-row bx-bp-chain__param">
                                                                    <label class="bx-label">
                                                                        {{ param.Name }}
                                                                        <span class="bx-required" v-if="param.Required">*</span>
                                                                    </label>
                                                                    <template v-if="canPresetParam(param)">
                                                                        <label class="bx-checkbox">
                                                                            <input type="checkbox" :checked="hasPreset(bp, param.paramKey)" @change="togglePreset(bp, param)" class="bx-checkbox__input">
                                                                            <span class="bx-checkbox__label">Задать значение в настройках (иначе спросим у пользователя)</span>
                                                                        </label>
                                                                        <div v-if="hasPreset(bp, param.paramKey)" class="bx-mt-12">
                                                                            <input v-if="param.Type === 'txt'" type="text" class="ui-input" v-model="bp.presets[param.paramKey].value">
                                                                            <input v-else-if="param.Type === 'number'" type="number" class="ui-input" v-model="bp.presets[param.paramKey].value">
                                                                            <input v-else-if="param.Type === 'datetime'" type="datetime-local" class="ui-input" v-model="bp.presets[param.paramKey].value">
                                                                            <multiselect v-else-if="param.Type === 'bool'" v-model="bp.presets[param.paramKey].value" :options="boolOptions" label="name" track-by="value" placeholder="Выберите значение" class="bx-multiselect">
                                                                                <span slot="noResult">Нет</span>
                                                                            </multiselect>
                                                                            <multiselect v-else-if="param.Type === 'select'" v-model="bp.presets[param.paramKey].value" :options="getSelectOptionsForParam(param)" label="name" track-by="value" placeholder="Выберите значение" class="bx-multiselect">
                                                                                <span slot="noResult">Нет</span>
                                                                            </multiselect>
                                                                        </div>
                                                                    </template>
                                                                    <div v-else class="bx-info-text">
                                                                        <span v-if="Number(param.Multiple) === 1">Множественные параметры пока не поддерживают пресеты — спросим у пользователя при запуске.</span>
                                                                        <span v-else>Параметры этого типа пока не поддерживают пресеты — спросим у пользователя при запуске.</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bx-accordion__item">
                                    <button class="bx-accordion__header" @click="toggleAccordion(4)">
                                        <i class="bx-accordion__icon fa-solid fa-up-right-from-square"></i>
                                        <span>Переход по ссылке с параметрами</span>
                                        <span class="bx-pro-badge">Только в PRO</span>
                                        <span class="bx-accordion__arrow" :class="{ 'bx-accordion__arrow--open': accordion_4 }">▼</span>
                                    </button>
                                    <div class="bx-accordion__body" :class="{ 'bx-accordion__body--open': accordion_4 }">
                                        <div class="bx-accordion__content">
                                            <div class="bx-info-text bx-mb-12">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Данное действие не работает совместно с действием "Перейти по произвольной ссылке"
                                            </div>
                                            <label class="bx-checkbox">
                                                <input v-model="current_button.buttonActionsId_FIELDS" type="checkbox" :value="4" class="bx-checkbox__input">
                                                <span class="bx-checkbox__label">Активировать свойство</span>
                                            </label>
                                            <div class="bx-mt-12">
                                                <label class="bx-label">Шаблон ссылки</label>
                                                <input v-model="current_button.linkWithParams_FIELDS" type="text" class="ui-input" placeholder="https://example.com?id={ID}&amp;name={TITLE}" ref="linkParamsInput">
                                                <div class="bx-hint">В ссылку можно вставлять значения полей карточки CRM в виде <code>{FIELD_CODE}</code>. Используйте селектор ниже — он добавит плейсхолдер в позицию курсора.</div>
                                            </div>
                                            <div class="bx-mt-12" v-if="current_button.entitySelection_FIELDS && current_button.entitySelection_FIELDS.value !== 'chat_bot'">
                                                <label class="bx-label">Вставить поле CRM в ссылку</label>
                                                <multiselect :value="null"
                                                             :options="entFields"
                                                             label="name"
                                                             track-by="value"
                                                             placeholder="Выберите поле — оно будет добавлено в ссылку"
                                                             class="bx-multiselect"
                                                             @open="ensureEntFieldsLoaded"
                                                             @select="insertLinkPlaceholder">
                                                    <span slot="noResult">Нет полей</span>
                                                </multiselect>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bx-accordion__item">
                                    <button class="bx-accordion__header" @click="toggleAccordion(5)">
                                        <i class="bx-accordion__icon fa-solid fa-comments"></i>
                                        <span>Кнопка в чатах</span>
                                        <span class="bx-pro-badge">Только в PRO</span>
                                        <span class="bx-accordion__arrow" :class="{ 'bx-accordion__arrow--open': accordion_5 }">▼</span>
                                    </button>
                                    <div class="bx-accordion__body" :class="{ 'bx-accordion__body--open': accordion_5 }">
                                        <div class="bx-accordion__content">
                                            <div class="bx-info-text bx-mb-12">
                                                <i class="fa-solid fa-lightbulb"></i> Кнопка в чате отображается в общем чате "ALLChat Overplan" и доступна всем участникам чата.
                                            </div>
                                            
                                            <div class="bx-actions bx-mt-16">
                                                <button v-if="current_button.buttonInChat_FIELDS" 
                                                        class="ui-btn ui-btn-warning" 
                                                        @click="deleteBtnChat">
                                                    Удалить кнопку из чата
                                                </button>
                                                <button v-else 
                                                        class="ui-btn ui-btn-success" 
                                                        @click="createBtnChat">
                                                    Создать кнопку в чате
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Бизнес-процесс из ленты новостей -->
                                <div class="bx-accordion__item">
                                    <button class="bx-accordion__header" @click="toggleAccordionWorkflow">
                                        <i class="bx-accordion__icon fa-solid fa-arrows-rotate"></i>
                                        <span>Запустить БП из Ленты</span>
                                        <span class="bx-pro-badge">Только в PRO</span>
                                        <span class="bx-accordion__arrow" :class="{ 'bx-accordion__arrow--open': accordion_6 }">▼</span>
                                    </button>
                                    <div class="bx-accordion__body" :class="{ 'bx-accordion__body--open': accordion_6 }">
                                        <div class="bx-accordion__content">
                                            <div class="bx-info-text bx-mb-12">
                                                <i class="fa-solid fa-lightbulb"></i> При нажатии на кнопку в чате будет запущен бизнес-процесс из ленты новостей.
                                                Если у БП есть обязательные параметры - откроется форма, если нет - БП запустится сразу.
                                            </div>
                                            
                                            <div class="bx-form-row">
                                                <label class="bx-label">Тип действия кнопки</label>
                                                <div class="bx-control">
                                                    <label class="bx-radio">
                                                        <input type="radio" value="url" v-model="buttonActionType" @change="onActionTypeChange">
                                                        <span class="bx-radio__label">Открыть ссылку</span>
                                                    </label>
                                                    <label class="bx-radio bx-ml-16">
                                                        <input type="radio" value="workflow" v-model="buttonActionType" @change="onActionTypeChange">
                                                        <span class="bx-radio__label">Запустить БП из ленты</span>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div v-if="buttonActionType === 'workflow'">
                                                <div class="bx-form-row">
                                                    <label class="bx-label">Выберите бизнес-процесс</label>
                                                    <multiselect v-model="selectedWorkflowTemplate" 
                                                                 :options="allFeedWorkflows" 
                                                                 label="name" 
                                                                 track-by="id"
                                                                 placeholder="Выберите БП из ленты"
                                                                 class="bx-multiselect"
                                                                 @open="getFeedWorkflows">
                                                        <span slot="noResult">Нет доступных БП</span>
                                                    </multiselect>
                                                </div>
                                                
                                                <div class="bx-form-row" v-if="selectedWorkflowTemplate">
                                                    <label class="bx-label">ID документа (опционально)</label>
                                                    <input v-model="selectedWorkflowDocument" type="text" class="ui-input" 
                                                           placeholder="Оставьте пустым для создания нового документа">
                                                    <div class="bx-hint">ID элемента ленты, к которому привязан БП. Если не указан - будет создан новый документ</div>
                                                </div>
                                            </div>
                                            
                                            <div v-if="buttonActionType === 'url'" class="bx-form-row">
                                                <label class="bx-label">Ссылка для перехода</label>
                                                <input v-model="current_button.link_FIELDS" type="text" class="ui-input" 
                                                       placeholder="https://example.com">
                                                <div class="bx-hint">Убедитесь, что включена галочка "Перейти по произвольной ссылке" выше</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Кнопки действий -->
                            <div class="bx-actions">
                                <button class="ui-btn ui-btn-danger" @click="delButton">
                                    Удалить настройки
                                </button>
                                <button class="ui-btn ui-btn-primary" @click="saveSettings">
                                    Сохранить настройки
                                </button>
                                <button v-if="current_button.buttonInCRM_FIELDS" 
                                        class="ui-btn ui-btn-warning" 
                                        @click="deleteBtnCrm">
                                    Удалить кнопку в CRM
                                </button>
                                <button v-else 
                                        class="ui-btn ui-btn-success" 
                                        @click="createBtnCrm">
                                    Создать кнопку в CRM
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Правая панель - предпросмотр -->
                <div class="bx-grid__col bx-grid__col--side">
                    <div class="bx-card bx-card--preview">
                        <div class="bx-card__header">
                            <h3 class="bx-card__title">Внешний вид кнопки</h3>
                        </div>
                        <div class="bx-card__body bx-card__body--preview">
                            <div class="bx-preview">
                                <button class="bx-preview__button" :style="previewButtonStyle">
                                    <span v-if="current_button.usingTheIcon_FIELDS">{{ current_button.iconOnTheButton_FIELDS }}</span>
                                    {{ current_button.textOnTheButton_FIELDS }}
                                </button>
                            </div>
                            <div class="bx-preview-actions">
                                <button class="ui-btn ui-btn-light-border ui-btn-block" @click="SetStandardStyles">
                                    Штатный стиль Bitrix24
                                </button>
                                <button class="ui-btn ui-btn-light ui-btn-block" @click="resetStylesButton">
                                    Сбросить стили
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Лоадер -->
        <div v-if="loader" class="bx-loader">
            <div class="bx-loader__overlay"></div>
            <div class="bx-loader__spinner"></div>
        </div>
    </div>
    
    <script type="module" src="js/script-bitrix.js?v=<?= time() ?>"></script>
</body>
</html>