<?php
// api/chatHandlers/chat-command-handler.php

$logFile = __DIR__ . '/command-debug.log';

function writeLog($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}";
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $logEntry .= "\n" . print_r($data, true);
        } else {
            $logEntry .= " - {$data}";
        }
    }
    $logEntry .= "\n" . str_repeat('-', 60) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function hasRestError($response) {
    if (!is_array($response)) {
        return true;
    }

    $error = $response['error'] ?? null;
    $errorDescription = $response['error_description'] ?? null;

    if (is_string($error)) {
        $error = trim($error);
    }
    if (is_string($errorDescription)) {
        $errorDescription = trim($errorDescription);
    }

    return !empty($error) || !empty($errorDescription);
}

function resolveIblockTypeId($iblockId) {
    $candidates = ['lists', 'bitrix_processes', 'processes'];

    foreach ($candidates as $typeId) {
        $lists = overCRest::call('lists.get', [
            'IBLOCK_TYPE_ID' => $typeId
        ]);

        if (hasRestError($lists) || empty($lists['result'])) {
            continue;
        }

        foreach ($lists['result'] as $list) {
            if ((int)($list['ID'] ?? 0) === (int)$iblockId) {
                return $typeId;
            }
        }
    }

    return null;
}

function getDedupPath() {
    return __DIR__ . '/command-dedup-cache.json';
}

function isDuplicateCommand($memberId, $dialogId, $command, $ttlSeconds = 4) {
    $path = getDedupPath();
    $now = time();
    $cache = [];

    if (file_exists($path)) {
        $decoded = json_decode(file_get_contents($path), true);
        if (is_array($decoded)) {
            $cache = $decoded;
        }
    }

    // clean old records
    foreach ($cache as $key => $ts) {
        if (!is_int($ts) || ($now - $ts) > ($ttlSeconds * 3)) {
            unset($cache[$key]);
        }
    }

    $fingerprint = md5((string)$memberId . '|' . (string)$dialogId . '|' . (string)$command);
    if (!empty($cache[$fingerprint]) && ($now - (int)$cache[$fingerprint]) <= $ttlSeconds) {
        $cache[$fingerprint] = $now;
        file_put_contents($path, json_encode($cache, JSON_UNESCAPED_UNICODE));
        return true;
    }

    $cache[$fingerprint] = $now;
    file_put_contents($path, json_encode($cache, JSON_UNESCAPED_UNICODE));
    return false;
}

writeLog("=== НОВЫЙ ЗАПРОС ===");
writeLog("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

// Получаем member_id из запроса
$memberId = null;

// Парсим входные данные
$input = $_REQUEST;

if (isset($input['auth']['member_id'])) {
    $memberId = $input['auth']['member_id'];
} elseif (isset($input['memberId'])) {
    $memberId = $input['memberId'];
}

// Если есть data в виде строки JSON
if (isset($input['data']) && is_string($input['data'])) {
    $decodedData = json_decode($input['data'], true);
    if ($decodedData) {
        $input['data'] = $decodedData;
    }
}

if (!$memberId && isset($input['data']['bot']['auth']['member_id'])) {
    $memberId = $input['data']['bot']['auth']['member_id'];
}

if (!$memberId && isset($input['auth']['member_id'])) {
    $memberId = $input['auth']['member_id'];
}

if (!$memberId) {
    writeLog("ERROR: No member_id found");
    http_response_code(200);
    echo 'OK';
    exit;
}

writeLog("member_id: {$memberId}");

// Подключаем overCRest
$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
$path = pathinfo($path, PATHINFO_DIRNAME);
include_once($path . '/overCRest.php');

try {
    overCRest::setCurrentBitrix24($memberId);
    writeLog("overCRest initialized successfully");
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    http_response_code(200);
    echo 'OK';
    exit;
}

// Получаем данные команды
$command = null;
$dialogId = null;
$botId = null;

// Формат ONIMBOTV2COMMANDADD
if (isset($input['data']['command']['command'])) {
    $command = $input['data']['command']['command'];
    $botId = $input['data']['bot']['id'] ?? null;
    $dialogId = $input['data']['chat']['dialogId'] ?? null;
    writeLog("Found command in data.command.command format: {$command}");
}
// Формат ONIMBOTMESSAGEADD
elseif (isset($input['data']['COMMAND'])) {
    $command = $input['data']['COMMAND'];
    $dialogId = $input['data']['DIALOG_ID'] ?? null;
    $botId = $input['data']['BOT_ID'] ?? null;
    writeLog("Found command in data.COMMAND format: {$command}");
}
// Альтернативный формат
elseif (isset($input['command'])) {
    if (is_array($input['command'])) {
        $command = $input['command']['command'] ?? null;
    } else {
        $command = $input['command'];
    }
    $dialogId = $input['dialogId'] ?? $input['chat']['dialogId'] ?? null;
    $botId = $input['botId'] ?? $input['bot']['id'] ?? null;
    writeLog("Found command in command field: {$command}");
}
// Формат из REQUEST напрямую
elseif (isset($_REQUEST['command'])) {
    $command = $_REQUEST['command'];
    $dialogId = $_REQUEST['dialogId'] ?? null;
    $botId = $_REQUEST['botId'] ?? null;
    writeLog("Found command in REQUEST: {$command}");
}

writeLog("Parsed: command={$command}, dialogId={$dialogId}, botId={$botId}");

if (!$command) {
    writeLog("No command found, exiting");
    http_response_code(200);
    echo 'OK';
    exit;
}

// Убираем слеш в начале команды
$cleanCommand = ltrim($command, '/');
writeLog("Clean command: {$cleanCommand}");

// Bitrix может прислать одинаковый webhook 2 раза для одного клика.
// Фильтруем дубликаты в коротком окне, чтобы не запускать БП повторно.
if (isDuplicateCommand($memberId, $dialogId, $cleanCommand)) {
    writeLog("Duplicate command ignored: {$cleanCommand}");
    http_response_code(200);
    echo 'OK';
    exit;
}

// Получаем токен бота из портальных настроек
$settingsCheck = overCRest::call('entity.item.get', [
    'ENTITY' => 'customButton',
    'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true']
]);

$botToken = null;
$portalBotId = null;
$chatId = null;

if (!empty($settingsCheck['result'])) {
    $portalSettings = $settingsCheck['result'][0]['PROPERTY_VALUES'];
    $botToken = $portalSettings['botToken_FIELDS'] ?? null;
    $portalBotId = $portalSettings['botId_FIELDS'] ?? null;
    $chatId = $portalSettings['chatId_FIELDS'] ?? null;
    writeLog("Portal settings found: botId={$portalBotId}, chatId={$chatId}");
} else {
    writeLog("WARNING: Portal settings not found!");
}

if (!$botId && $portalBotId) {
    $botId = $portalBotId;
    writeLog("Using botId from portal settings: {$botId}");
}

// Обрабатываем команду
if (strpos($cleanCommand, 'overplan_button_') === 0) {
    $buttonId = (int)str_replace('overplan_button_', '', $cleanCommand);
    writeLog("Extracted button ID: {$buttonId}");
    
    if ($buttonId > 0) {
        $getButton = overCRest::call('entity.item.get', [
            'ENTITY' => 'customButton',
            'FILTER' => ['ID' => $buttonId]
        ]);
        
        writeLog("Get button result success: " . (empty($getButton['error']) ? "yes" : "no"));
        
        if (!empty($getButton['result'][0]['PROPERTY_VALUES'])) {
            $buttonData = $getButton['result'][0]['PROPERTY_VALUES'];
            $actionType = $buttonData['buttonActionType_FIELDS'] ?? 'url';
            $buttonText = trim($buttonData['textOnTheButton_FIELDS'] ?? $buttonData['buttonName_FIELDS'] ?? 'Кнопка');
            $link = trim($buttonData['link_FIELDS'] ?? '');
            
            writeLog("Action type: {$actionType}, buttonText: {$buttonText}");
            
            if ($actionType === 'workflow') {
                // Запускаем бизнес-процесс из ленты
                $templateId = $buttonData['workflowTemplateId_FIELDS'] ?? null;
                $documentId = $buttonData['workflowDocumentId_FIELDS'] ?? null;
                
                writeLog("Workflow template ID: {$templateId}, document ID: {$documentId}");
                
                if ($templateId) {
                    // Получаем информацию о шаблоне БП
                    $templateInfo = overCRest::call('bizproc.workflow.template.list', [
                        'select' => ['ID', 'NAME', 'DOCUMENT_TYPE', 'MODULE_ID', 'ENTITY', 'AUTO_EXECUTE'],
                        'filter' => ['ID' => (int)$templateId]
                    ]);
                    
                    writeLog("Template info:", $templateInfo);
                    
                    // Получаем правильный DOCUMENT_TYPE
                    $docType = $templateInfo['result'][0]['DOCUMENT_TYPE'] ?? null;
                    $moduleId = $templateInfo['result'][0]['MODULE_ID'] ?? 'lists';
                    $entity = $templateInfo['result'][0]['ENTITY'] ?? 'BizprocDocument';
                    
                    // Извлекаем ID инфоблока из DOCUMENT_TYPE
                    $iblockId = null;
                    if (is_array($docType) && isset($docType[2])) {
                        $iblockId = str_replace('iblock_', '', $docType[2]);
                    }
                    $iblockTypeId = $iblockId ? resolveIblockTypeId($iblockId) : null;
                    
                    writeLog("Module: {$moduleId}, Entity: {$entity}, IblockId: {$iblockId}, IblockTypeId: " . ($iblockTypeId ?? 'not-found'));
                    
                    $documentWasAutoCreated = false;

                    // Если document_id не указан или равен 0, создаём новый элемент
                    if (empty($documentId) || $documentId == '0' || $documentId == 'null') {
                        if ($iblockId) {
                            $typeForListsApi = $iblockTypeId ?: 'lists';
                            $newElement = overCRest::call('lists.element.add', [
                                'IBLOCK_TYPE_ID' => $typeForListsApi,
                                'IBLOCK_ID' => (int)$iblockId,
                                'ELEMENT_CODE' => 'auto_' . time() . '_' . rand(100, 999),
                                'FIELDS' => [
                                    'NAME' => 'Создано из кнопки Overplan ' . date('Y-m-d H:i:s')
                                ]
                            ]);
                            
                            writeLog("Create new element result:", $newElement);

                            if (!hasRestError($newElement) && !empty($newElement['result'])) {
                                $documentId = (int)$newElement['result'];
                                $documentWasAutoCreated = true;
                                writeLog("Created new document with ID: {$documentId}");
                            } else {
                                // Пытаемся взять существующий элемент, чтобы запуск не падал из-за DOCUMENT_ID=0
                                $existingElements = overCRest::call('lists.element.get', [
                                    'IBLOCK_TYPE_ID' => $typeForListsApi,
                                    'IBLOCK_ID' => (int)$iblockId,
                                    'FILTER' => [],
                                    'SELECT' => ['ID']
                                ]);
                                writeLog("Get existing element result:", $existingElements);

                                if (!hasRestError($existingElements) && !empty($existingElements['result'])) {
                                    $firstElement = reset($existingElements['result']);
                                    $documentId = (int)($firstElement['ID'] ?? 0);
                                    writeLog("Use existing document ID: {$documentId}");
                                } else {
                                    writeLog("Failed to resolve valid document ID");
                                    $documentId = 0;
                                }
                            }
                        } else {
                            writeLog("No iblock_id found, using document ID 0");
                            $documentId = 0;
                        }
                    }
                    
                    // Формируем DOCUMENT_ID для запуска БП.
                    // Для lists на разных порталах используются разные форматы entity/document key.
                    $documentsToTry = [];
                    if ($moduleId == 'lists') {
                        $documentsToTry[] = ['lists', $entity, (string)(int)$documentId];
                        $documentsToTry[] = ['lists', 'Bitrix\\Lists\\BizprocDocumentLists', (string)(int)$documentId];
                        $documentsToTry[] = ['lists', 'BizprocDocument', (string)(int)$documentId];
                        if ((int)$documentId > 0 && !empty($iblockId)) {
                            $documentsToTry[] = ['lists', 'BizprocDocument', 'iblock_' . $iblockId . '_' . (int)$documentId];
                        }
                    } else {
                        $documentsToTry[] = [$moduleId, $entity, (int)$documentId];
                    }

                    // Удаляем дубликаты кандидатов
                    $documentsToTry = array_values(array_map(
                        'unserialize',
                        array_unique(array_map('serialize', $documentsToTry))
                    ));
                    writeLog("Document IDs to try:", $documentsToTry);

                    $autoExecuteRaw = $templateInfo['result'][0]['AUTO_EXECUTE'] ?? null;
                    $autoExecute = (int)$autoExecuteRaw;
                    // В Bitrix для шаблонов БП на списках автозапуск по созданию/изменению
                    // может срабатывать сразу после lists.element.add. Тогда ручной start
                    // приведет к дублю. Если документ создан в этом запросе и включен
                    // любой автозапуск, не запускаем БП вручную.
                    $skipManualStart = $documentWasAutoCreated && $autoExecute > 0;
                    writeLog("Auto execute info", [
                        'AUTO_EXECUTE' => $autoExecuteRaw,
                        'documentWasAutoCreated' => $documentWasAutoCreated,
                        'skipManualStart' => $skipManualStart
                    ]);
                    
                    // Формируем URL для ручного запуска
                    $docTypeStr = '';
                    if ($iblockId) {
                        $docTypeStr = 'iblock_' . $iblockId;
                    }
                    $startUrl = "https://" . $_SERVER['HTTP_HOST'] . "/bizproc/start/?template_id={$templateId}&document_type={$docTypeStr}";
                    
                    // Вручную запускаем только когда шаблон не запускается сам от создания элемента.
                    $startResult = null;
                    if (!$skipManualStart) {
                        foreach ($documentsToTry as $documentCandidate) {
                            $startResult = overCRest::call('bizproc.workflow.start', [
                                'TEMPLATE_ID' => (int)$templateId,
                                'DOCUMENT_ID' => $documentCandidate
                            ]);
                            writeLog("Workflow start try with DOCUMENT_ID " . json_encode($documentCandidate) . ":", $startResult);
                            if (!hasRestError($startResult)) {
                                break;
                            }
                        }
                    }
                    
                    if ($skipManualStart) {
                        $responseMessage = "✅ *{$buttonText}*\n\n";
                        $responseMessage .= "🚀 Бизнес-процесс запущен автоматически при создании документа.\n\n";
                        $responseMessage .= "📋 БП: {$templateInfo['result'][0]['NAME']}\n";
                        $responseMessage .= "📄 ID документа: {$documentId}";
                    } elseif (!hasRestError($startResult)) {
                        $responseMessage = "✅ *{$buttonText}*\n\n";
                        $responseMessage .= "🚀 Бизнес-процесс успешно запущен!\n\n";
                        $responseMessage .= "📋 БП: {$templateInfo['result'][0]['NAME']}\n";
                        $responseMessage .= "📄 ID документа: {$documentId}";
                    } else {
                        $errorMsg = $startResult['error_description'] ?? $startResult['error'] ?? 'Неизвестная ошибка';
                        $responseMessage = "❌ *{$buttonText}*\n\n";
                        $responseMessage .= "⚠️ Ошибка запуска БП: {$errorMsg}\n\n";
                        $responseMessage .= "🔄 Попробуйте запустить вручную:\n";
                        $responseMessage .= "🔗 {$startUrl}";
                    }
                    
                    overCRest::call('imbot.v2.Chat.Message.send', [
                        'botId' => (int)$botId,
                        'botToken' => $botToken,
                        'dialogId' => $dialogId,
                        'fields' => ['message' => $responseMessage]
                    ]);
                } else {
                    $responseMessage = "❌ *{$buttonText}*\n\n";
                    $responseMessage .= "⚠️ Шаблон бизнес-процесса не настроен.\n\n";
                    $responseMessage .= "Пожалуйста, обратитесь к администратору портала.";
                    
                    overCRest::call('imbot.v2.Chat.Message.send', [
                        'botId' => (int)$botId,
                        'botToken' => $botToken,
                        'dialogId' => $dialogId,
                        'fields' => ['message' => $responseMessage]
                    ]);
                }
            } else {
                // Обычная ссылка
                if (!empty($link) && $link !== 'null' && $link !== '') {
                    if (!preg_match('/^https?:\/\//i', $link)) {
                        $link = 'https://' . $link;
                    }
                    
                    writeLog("Sending link: {$link}");
                    
                    $responseMessage = "✅ *{$buttonText}*\n\n";
                    $responseMessage .= "🔗 Ссылка: {$link}";
                    
                    $sendResult = overCRest::call('imbot.v2.Chat.Message.send', [
                        'botId' => (int)$botId,
                        'botToken' => $botToken,
                        'dialogId' => $dialogId,
                        'fields' => ['message' => $responseMessage]
                    ]);
                    
                    writeLog("Send result:", $sendResult);
                    
                    if (isset($sendResult['error'])) {
                        writeLog("v2 failed, trying legacy method");
                        overCRest::call('imbot.message.add', [
                            'BOT_ID' => (int)$botId,
                            'DIALOG_ID' => $dialogId,
                            'MESSAGE' => "✅ {$buttonText}\n\n🔗 Ссылка: {$link}"
                        ]);
                    }
                } else {
                    $responseMessage = "❌ *{$buttonText}*\n\n";
                    $responseMessage .= "⚠️ Ссылка для этой кнопки не настроена.\n\n";
                    $responseMessage .= "Пожалуйста, обратитесь к администратору портала.";
                    
                    overCRest::call('imbot.v2.Chat.Message.send', [
                        'botId' => (int)$botId,
                        'botToken' => $botToken,
                        'dialogId' => $dialogId,
                        'fields' => ['message' => $responseMessage]
                    ]);
                }
            }
        } else {
            writeLog("ERROR: Button data not found for ID: {$buttonId}");
            
            overCRest::call('imbot.v2.Chat.Message.send', [
                'botId' => (int)$botId,
                'botToken' => $botToken,
                'dialogId' => $dialogId,
                'fields' => ['message' => "❌ Кнопка не найдена. Возможно, она была удалена."]
            ]);
        }
    } else {
        writeLog("ERROR: Invalid button ID: {$buttonId}");
    }
} else {
    writeLog("Unknown command: {$cleanCommand}");
    
    $welcomeMessage = "🤖 *Бот Overplan*\n\n";
    $welcomeMessage .= "Я помогаю быстро переходить по ссылкам и запускать бизнес-процессы.\n\n";
    $welcomeMessage .= "📌 Нажмите на любую кнопку, созданную в настройках приложения.\n\n";
    $welcomeMessage .= "По всем вопросам обращайтесь к администратору портала.";
    
    overCRest::call('imbot.v2.Chat.Message.send', [
        'botId' => (int)$botId,
        'botToken' => $botToken,
        'dialogId' => $dialogId,
        'fields' => ['message' => $welcomeMessage]
    ]);
}

writeLog("=== ЗАПРОС ОБРАБОТАН ===");

http_response_code(200);
echo 'OK';