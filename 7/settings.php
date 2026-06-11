<?
define('C_REST_CLIENT_ID','app.643fdd16d62df7.54956574');//Application ID
define('C_REST_CLIENT_SECRET','RWGqYx16fIG6oy5u61bQOmHP8Pq2FGGn2HY1yty29sw8KKIxd1');//Application key
define('TABLE_NAME', 'crmButtons'); //Имя таблицы

define('CHAT_REPORT', 'ALLChat Overplan'); //Имя чата для рассылки
define('BOT_REPORT_CODE', 'OVERPLAN_REPORT_CRMBUTTONS'); //Имя бота для рассылки
define('BOT_REPORT_NAME', 'Overplan Report'); //Имя бота для рассылки

// === Биллинговые уведомления (приёмник api/notifications/notify.php) ===
// ВНИМАНИЕ: впишите общий секрет из биллинга (тот же, что на стороне billing).
define('NOTIFICATIONS_DISPATCH_SECRET', '12d916a6fd9361a73c2ef647347a5e3374b1971f43362725de1ec52fdbace898'); // общий секрет с биллингом
define('NOTIFICATIONS_CALLBACK_URL', 'https://billing.qabinet.ru/v1/notifications/callback');

// === Биллинг qabinet (pull: тариф/фичи/лимиты) ===
define('BILLING_BASE_URL', 'https://billing.qabinet.ru/v1');
define('BILLING_WIDGET_URL', 'https://billing.qabinet.ru/widget/b2b-checkout');
define('BILLING_APP_CODE', 'user_buttons'); // код продукта в qabinet — менять нельзя без миграции
define('BILLING_TRIAL_PLAN_CODE', 'trial');
define('APP_DATABASE', [
    'host' => "localhost",
    'login' => 'bitrix0',
    'password' => 'Ji]T@sq[IvSs=0b6ZHRz',
    'database' => 'sitemanager',
    'table' => 	'crmButtons'
]);
// or
// define('C_REST_WEB_HOOK_URL','https://rest-course.bitrix24.ru/rest/1/j0nzq02mzvzmx9lx/');//url on creat Webhook

//define('C_REST_CURRENT_ENCODING','windows-1251');
//define('C_REST_IGNORE_SSL',true);//turn off validate ssl by curl
//define('C_REST_LOG_TYPE_DUMP',true); //logs save var_export for viewing convenience
//define('C_REST_BLOCK_LOG',true);//turn off default logs
//define('C_REST_LOGS_DIR', __DIR__ .'/logs/'); //directory path to save the log