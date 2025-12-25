<?php



$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');

// Инициализируем портал
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Кнопка тест</title>

    <script src="//api.bitrix24.com/api/v1/"></script>

    <style>
        body {
            margin: 0;
            padding: 6px;
            background: transparent;
        }

        .btn-test {
            background: #000;
            color: #fff;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn-test:hover {
            opacity: 0.85;
        }
    </style>
</head>
<body>

<button class="btn-test" onclick="onClickBtn()">
    Кнопка тест
</button>

<script>
function onClickBtn() {
    BX24.alert('Битриксовая кнопка работает ✅');
}
</script>

</body>
</html>
