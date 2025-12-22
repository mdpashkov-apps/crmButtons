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
    <!-- <script src="https://unpkg.com/vue-multiselect@2.1.0"></script> -->
    <script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css">
    <!-- <script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script> -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <!-- <script src="https://kit.fontawesome.com/c9f5eeb571.js" crossorigin="anonymous"></script> -->
   
</head>

<body>
<?
$member_id = $_REQUEST['member_id']; ?>
<script>
    window.memberId = '<?echo $member_id?>'
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
            <a href="https://t.me/appsupportbot" target="_blank">Обратная связь</a>
        </div>
    </div>



    <!-- <div class="tabs_btn" >
        <img class="addProfiles" src="img/Add.svg" alt="Добавить профиль" @click="createBtn">
        <div v-for="button in portalButtons.slice(0,6)" class="tab_btn" :id="button.ID" >  {{ button.PROPERTY_VALUES.buttonName_FIELDS }} </div>
        <div v-if="portalButtons.length > 6" class="dropdown" @mouseenter="getMorButtons"> Еще </div>
</div> -->



<div class="tabs_btn">
    <img class="addProfiles"
         src="img/Add.svg"
         alt="Добавить профиль"
         @click="createBtn">

    <div v-for="button in portalButtons.slice(0,6)"
         class="tab_btn"
         :id="button.ID">
        {{ button.PROPERTY_VALUES.buttonName_FIELDS }}
    </div>

    <div v-if="portalButtons.length > 6"
         class="dropdown"
         @mouseenter="showMoreButtons"
         @mouseleave="hideMoreButtons">

        Еще
        <span class="span_btn">&or;</span>

        <div class="dropdown-content" :class="{ show: showMore }">
            <div v-for="button in morebuttons"
                 :key="button.ID"
                 class="tab_btn">
                {{ button.PROPERTY_VALUES.buttonName_FIELDS }}
            </div>
        </div>

    </div>
</div>


        
<!-- <div class="tabs_btn" v-if="current_button && current_button.button_actions">
            <img class="addProfiles" src="img/Add.svg" alt="Добавить профиль" @click="createBtn">
            <div v-for="row in rows.slice(0,6)" class="tab_btn" :id=" [row.id]" :class="{still_btn_active:row.lists_btn_bool}" @click="open_lists_btn(row)">
                {{row.name}}
            </div>

            <div v-if="rows.length > 6" v-on:mouseenter="show" v-on:mouseleave="hide" class="dropdown" :class="{still_block_active:lists_btn_bool}">
                Еще
                <span class="span_btn">
                    &or;
                </span>
                <div id="myDropdown" class="dropdown-content" :class="{show:lists_btn_bool}">
                    <div v-for="row in rows.slice(6)" :class="{still_btn_active:row.lists_btn_bool}" @click="open_lists_btn(row)">
                        {{row.name}}
                    </div>
                </div>
            </div>
        </div> -->


    

   
    
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