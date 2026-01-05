<?php

// ПУТЬ К КАРТИНКЕ
$imagePath = '/home/bitrix/www/applications/crmButtons/6/overBot.png';

if (!file_exists($imagePath)) {
    die('Файл не найден');
}

// ЧИТАЕМ ФАЙЛ И КОДИРУЕМ В BASE64
$base64 = base64_encode(file_get_contents($imagePath));

// ВЫВОДИМ В КОНСОЛЬ / БРАУЗЕР
// echo $base64;
// file_put_contents(__DIR__.'/result91.log', var_export($base64, true), FILE_APPEND);
