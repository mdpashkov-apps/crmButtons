<?
if(!is_null($_GET['REQUEST'])) $_REQUEST = json_decode($_GET['REQUEST'], 1);
include_once(__DIR__.'/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
?>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- <link rel="stylesheet" href="css/app.css"> -->

    <script src="https://gcore.jsdelivr.net/npm/vue@2.7.16"></script>
    <!-- <script src="https://unpkg.com/vue-multiselect@2.1.6"></script> -->
    <script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.6/dist/vue-multiselect.min.css">
    <script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- Bootstrap -->
    <link href="https://gcore.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://gcore.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/style.css">
    <title>Уведомления</title>
</head>

<? $member_id = $_REQUEST['member_id'];?>
<script>
    window.memberId = '<?echo $member_id?>'
</script>

<body>    
    <div id="app">
        <div class="top-container">
            <div class="logo-con">
                <a target="_blank" href="https://overplan.ru/?utm_source=b24app" title="overplan.ru" class="logo">
                        <img src="img/logo_overplan.png" >
                </a>
            </div>
            <div class="feedback-con">
                <a href='./index.php?REQUEST=<?=json_encode($_REQUEST)?>'>Настройка приложения</a>
            </div>
            <div class="feedback-con">
                <a href='./indexReports.php?REQUEST=<?=json_encode($_REQUEST)?>'>Настроить уведомления</a>
            </div>
            <div class="feedback-con">
                    <a href="https://t.me/appsupportbot" target="_blank">Обратная связь</a>
            </div>
        </div>
        <h1>Настройка уведомлений</h1>
        <div style="margin-top:15px;">
            <applications-report></applications-report>
        </div>
    </div>

<script type="module" src="./js/script.js"></script>

</body>
</html>