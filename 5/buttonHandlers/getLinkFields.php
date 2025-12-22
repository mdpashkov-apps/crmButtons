<?php

function ensureHttp($url)
{
    // Удаляем пробелы в начале и конце
    $url = trim($url);

    // Проверяем, начинается ли строка с http:// или https:// (регистронезависимо)
    if (!preg_match('#^https?://#i', $url)) {
        // Если нет, добавляем https://
        $url = 'https://' . $url;
    }

    return $url;
}

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
include_once ($path . '/overCRest.php');

$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);

$linkValue = '';
$urlMethod = match ($requestData['entityType']) {
    'Deal' => 'crm.deal.get',
    'Lead' => 'crm.lead.get',
    'Company' => 'crm.company.get',
    'Contact' => 'crm.contact.get',
    default => 'crm.item.get'
};
$link = '';
if ($urlMethod === 'crm.item.get') {
    $response = overCRest::call($urlMethod, [
        'entityTypeId' => $requestData['entityType'],
        'id' => $requestData['idEntity'],
    ])['result']['item'];
    $linkValue = $response[$requestData['idFieldsLink']];
} else {
    $reponseBaseEntity = overCRest::call($urlMethod, [
        'ID' => $requestData['idEntity']
    ])['result'];
    $linkValue = $reponseBaseEntity[$requestData['idFieldsLink']];
}
$linkValue = ensureHttp($linkValue);
echo json_encode($linkValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
