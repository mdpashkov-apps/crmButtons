<?php
include_once(__DIR__.'/overCRest.php');

$install_result = overCRest::installApp();

$eventBind = overCRest::call(
    'event.bind',
    [
        'event' => 'OnAppUninstall',
        'handler' => 'https://app.overplan.ru/applications/crmButtons/1/uninstall.php'
    ]
);

overCRest::setLog(['add' => $result], 'installation');

if($install_result['rest_only'] === false):?>
<head>
	<script src="//api.bitrix24.com/api/v1/"></script>
	<?if($install_result['install'] == true):?>
	<script>
	            BX24.init(function() {
                    BX24.callMethod(
                        "entity.get", {
                            'ENTITY': 'customButton'
                        },
                        (result) => {                          
                            if(!result.answer.error){
                                BX24.installFinish();
                            } else{
                                BX24.callBatch({ 
                                    //создание
                                    entity: ['entity.add', {
                                        'ENTITY': 'customButton',
                                        'NAME': 'customButton',
                                        'ACCESS': {
                                            AU: 'W'
                                        },
                                    }],
                                    // создание полей в хранилище
                                    buttonName: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'buttonName_FIELDS',
                                        'NAME': 'Название кнопки',
                                        'TYPE': 'S'
                                    }],
                                    customField: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'customField_FIELDS',
                                        'NAME': 'Пользовательский тип поля',
                                        'TYPE': 'S'
                                    }],
                                    buttonColor: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'buttonColor_FIELDS',
                                        'NAME': 'Цвет кнопки',
                                        'TYPE': 'S'
                                    }],
                                    textColor: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'textColor_FIELDS',
                                        'NAME': 'БП сделок',
                                        'TYPE': 'S'
                                    }],
                                    buttonRadius: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'buttonRadius_FIELDS',
                                        'NAME': 'Радиус кнопки',
                                        'TYPE': 'S'
                                    }],
                                    buttonBorder: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'buttonBorder_FIELDS',
                                        'NAME': 'Использование границы кнопки',
                                        'TYPE': 'S'
                                    }],
                                    buttonBorderHeight: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'buttonBorderWidth_FIELDS',
                                        'NAME': 'Высота границы кнопки',
                                        'TYPE': 'S'
                                    }],
                                    buttonBorderColor: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'buttonBorderColor_FIELDS',
                                        'NAME': 'Цвет границы кнопки',
                                        'TYPE': 'S'
                                    }],
                                    textOnTheButton: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'textOnTheButton_FIELDS',
                                        'NAME': 'Текст кнопки',
                                        'TYPE': 'S'
                                    }],
                                    usingTheIcon: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'usingTheIcon_FIELDS',
                                        'NAME': 'Использование иконки',
                                        'TYPE': 'S'
                                    }],
                                    iconOnTheButton: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'iconOnTheButton_FIELDS',
                                        'NAME': 'Иконка на кнопке',
                                        'TYPE': 'S'
                                    }],
                                    entitySelection: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'entitySelection_FIELDS',
                                        'NAME': 'Выбор сущности',
                                        'TYPE': 'S'
                                    }],
                                    buttonActionsId: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'buttonActionsId_FIELDS',
                                        'NAME': 'Выбранные действия',
                                        'TYPE': 'S'
                                    }],
                                    businessProcessesValue: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'businessProcessesValue_FIELDS',
                                        'NAME': 'Выбранный БП',
                                        'TYPE': 'S'
                                    }],
                                    documentTemplatesValue: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'documentTemplatesValue_FIELDS',
                                        'NAME': 'Выбранный шабблон документа',
                                        'TYPE': 'S'
                                    }],
                                    listsValue: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'listsValue_FIELDS',
                                        'NAME': 'Выбранный список',
                                        'TYPE': 'S'
                                    }],
                                    fieldsTable: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'fieldsTable_FIELDS',
                                        'NAME': 'Поля таблицы',
                                        'TYPE': 'S'
                                    }],
                                    link: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'link_FIELDS',
                                        'NAME': 'Ссылка',
                                        'TYPE': 'S'
                                    }],
                                    buttonInCRM: ['entity.item.property.add', {
                                        'ENTITY': 'customButton',
                                        'PROPERTY': 'buttonInCRM_FIELDS',
                                        'NAME': 'Кнопка в карточке',
                                        'TYPE': 'S'
                                    }],
                                }, (result) => {
                                    BX24.installFinish();                        
                                });		
                            }  
                        }
                    )			
                });
                BX24.callBind('OnAppUninstall', 'http://app.overplan.ru/applications/crmButtons/1/uninstall.php');
	</script>
	<?endif;?>
</head>
<body>
	<?if($install_result['install'] == true):?>
		installation has been finished
	<?else:?>
        <pre><?print_r($install_result);?></pre>
		installation error
	<?endif;?>
</body>
<?endif;