<?php

$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$entityBody = file_get_contents('php://input');
$requestData = json_decode($entityBody, true);
include_once ($path . '/overCRest.php');

$memberId = $requestData['memberId'];
overCRest::setCurrentBitrix24($memberId);
$responseStorage = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
])['result'];
$selected = [];
foreach ($responseStorage as $item) {
    if ($item['ACTIVE'] === 'Y' && $item['PROPERTY_VALUES']['crmLinkFields_FIELDS']) {
        $selected['value'] = $item['PROPERTY_VALUES']['crmLinkFields_FIELDS'];
    }
}
$method = match ($requestData['entityType']) {
    'Deal' => 'crm.deal.fields',
    'Lead' => 'crm.lead.fields',
    'Contact' => 'crm.contact.fields',
    'Company' => 'crm.company.fields',
    default => 'crm.item.fields',
};

function parsingResponseAndSearchFieldsTypeLink(array $arraySearch): array
{
    $options = [];
    foreach ($arraySearch as $key => $fields) {
        if ($fields['type'] === 'url') {
            $options[] = [
                'value' => $key,
                'name' => $fields['formLabel'],
            ];
        }
    }
    return $options;
}

if ((int) $requestData['entityType']) {
    $response = overCRest::call($method, [
        'entityTypeId' => (int) $requestData['entityType'],
    ])['result']['fields'];
    $result = parsingResponseAndSearchFieldsTypeLink($response);
} else {
    $responseDefaultCrmEntity = overCRest::call($method, [])['result'];
    $result = parsingResponseAndSearchFieldsTypeLink($responseDefaultCrmEntity);
}
if (count($selected) === 1) {
    $flag = false;
    foreach ($result as $key => $value) {
        if ($value['value'] === $selected['value']) {
            $selected['name'] = $value['name'];
            $flag = true;
        }
    }
    if (!$flag) {
        $selected = [];
    }
}
echo json_encode([
    'selected' => $selected,
    'options' => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
