<?php
// api/chatHandlers/ensure-bot.php
// Идемпотентно гарантирует наличие чат-бота "Overplan Report" и группового чата
// "ALLChat Overplan" для фичи "Кнопки в чате". Раньше это делала страница
// "Настройка уведомлений" (addChatBot.php) — теперь логика вынесена сюда и
// вызывается лениво при первой чат-кнопке / добавлении пользователей.
//
// Возвращает ['botId'=>int|null, 'botToken'=>string|null, 'chatId'=>int|null].
// Требует, чтобы overCRest::setCurrentBitrix24() был уже вызван.

if (!function_exists('ensureBotAndChat')) {
    function ensureBotAndChat($memberId)
    {
        $WEBHOOK = 'https://app.overplan.ru/applications/crmButtons/7/api/chatHandlers/chat-command-handler.php';

        // 1. Чат
        $findChat = overCRest::call('im.search.chat.list', ['FIND' => 'ALLChat Overplan']);
        if (empty($findChat['result'])) {
            $chatAdd = overCRest::call('im.chat.add', [
                'TYPE'  => 'CHAT',
                'TITLE' => 'ALLChat Overplan',
                'USERS' => [1],
            ]);
            $chatId = $chatAdd['result'] ?? null;
        } else {
            $chatId = $findChat['result'][0]['id'];
        }

        // 2. Портальные настройки
        $settingsCheck = overCRest::call('entity.item.get', [
            'ENTITY' => 'customButton',
            'FILTER' => ['=PROPERTY_VALUES.isPortalSettings' => 'true'],
        ]);
        $portalSettingsId = $settingsCheck['result'][0]['ID'] ?? null;
        $portalSettings   = $settingsCheck['result'][0]['PROPERTY_VALUES'] ?? [];
        $botId    = $portalSettings['botId_FIELDS'] ?? null;
        $botToken = $portalSettings['botToken_FIELDS'] ?? null;

        // 3. Ищем реально существующего бота по коду
        $existingBotId = null;
        $findBotV2 = overCRest::call('imbot.v2.Bot.list', []);
        if (!isset($findBotV2['error']) && !empty($findBotV2['result']['bots'])) {
            foreach ($findBotV2['result']['bots'] as $bot) {
                if (($bot['code'] ?? '') === 'OVERPLAN_REPORT_CRMBUTTONS') {
                    $existingBotId = $bot['id'];
                    break;
                }
            }
        }
        if (!$existingBotId) {
            $findBotOld = overCRest::call('imbot.bot.list', []);
            if (!isset($findBotOld['error']) && !empty($findBotOld['result'])) {
                foreach ($findBotOld['result'] as $bot) {
                    if (($bot['CODE'] ?? '') === 'OVERPLAN_REPORT_CRMBUTTONS') {
                        $existingBotId = $bot['ID'];
                        break;
                    }
                }
            }
        }

        if ($existingBotId) {
            $botId = $existingBotId;
            // добавить бота в чат, если его там нет
            if ($chatId) {
                $chatUsers = overCRest::call('im.chat.user.get', ['CHAT_ID' => $chatId]);
                $inChat = false;
                if (!empty($chatUsers['result'])) {
                    foreach ($chatUsers['result'] as $u) {
                        if (($u['id'] ?? null) == $existingBotId) { $inChat = true; break; }
                    }
                }
                if (!$inChat) {
                    overCRest::call('im.chat.user.add', ['CHAT_ID' => $chatId, 'USERS' => [(int)$existingBotId]]);
                }
            }
        } else {
            // 3b. Создаём нового бота
            $botToken = hash('sha256', $memberId . 'OVERPLAN_SECRET_SALT_' . time() . rand(1000, 9999));
            $reg = overCRest::call('imbot.v2.Bot.register', [
                'botToken' => $botToken,
                'fields'   => [
                    'code'       => 'OVERPLAN_REPORT_CRMBUTTONS',
                    'type'       => 'bot',
                    'eventMode'  => 'webhook',
                    'webhookUrl' => $WEBHOOK,
                    'properties' => [
                        'name'         => 'Overplan Report',
                        'workPosition' => 'Команды',
                        'color'        => 'AQUA',
                        'email'        => 'hello@overplan.ru',
                        'website'      => 'overplan.ru',
                    ],
                ],
            ]);
            if (!isset($reg['error'])) {
                $botId = $reg['result']['bot']['id'] ?? $reg['result']['botId'] ?? $reg['result'] ?? null;
            }
            if (!$botId) {
                $regOld = overCRest::call('imbot.register', [
                    'CODE'                  => 'OVERPLAN_REPORT_CRMBUTTONS',
                    'TYPE'                  => 'BOT',
                    'EVENT_MESSAGE_ADD'     => $WEBHOOK,
                    'EVENT_WELCOME_MESSAGE' => $WEBHOOK,
                    'PROPERTIES'            => [
                        'NAME'         => 'Overplan Report',
                        'WORK_POSITION'=> 'Команды',
                        'COLOR'        => 'AQUA',
                        'EMAIL'        => 'hello@overplan.ru',
                        'PERSONAL_WWW' => 'overplan.ru',
                    ],
                ]);
                if (!isset($regOld['error'])) {
                    $botId = $regOld['result'] ?? null;
                }
            }
            if ($botId && $chatId) {
                overCRest::call('im.chat.user.add', ['CHAT_ID' => $chatId, 'USERS' => [(int)$botId]]);
            }
        }

        // 4. Сохраняем в портальные настройки
        if ($botId) {
            $data = [
                'botId_FIELDS'     => $botId,
                'chatId_FIELDS'    => $chatId,
                'botRegistered'    => '1',
                'isPortalSettings' => 'true',
                'buttonName_FIELDS'=> 'PORTAL_SETTINGS',
            ];
            if ($botToken) { $data['botToken_FIELDS'] = $botToken; }

            if ($portalSettingsId) {
                overCRest::call('entity.item.update', [
                    'ENTITY' => 'customButton',
                    'ID'     => (int)$portalSettingsId,
                    'PROPERTY_VALUES' => $data,
                ]);
            } else {
                overCRest::call('entity.item.add', [
                    'ENTITY' => 'customButton',
                    'NAME'   => 'PORTAL_SETTINGS',
                    'PROPERTY_VALUES' => $data,
                ]);
            }
        }

        return ['botId' => $botId, 'botToken' => $botToken, 'chatId' => $chatId];
    }
}
