<?
if(!is_null($_GET['REQUEST'])) $_REQUEST = json_decode($_GET['REQUEST'], 1);
include_once(__DIR__.'/overCRest.php');
overCRest::setCurrentBitrix24($_REQUEST['member_id']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Уведомления | Overplan</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Локальные файлы вместо CDN -->
    <link rel="stylesheet" href="libs/vue-multiselect.min.css">
    <script src="libs/vue-multiselect.min.js"></script>
    
    <script src="libs/vue.min.js"></script>
    <script src="libs/axios.min.js"></script>
    <script src="//api.bitrix24.com/api/v1/"></script>
    
    <!-- Основные стили -->
    <link rel="stylesheet" href="css/bitrix-design.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/mobile-adaptation.css?v=<?= time() ?>">
</head>
<body class="bitrix-ui">
    <? 
    $member_id = $_REQUEST['member_id'];
    $request_json = htmlspecialchars(json_encode($_REQUEST), ENT_QUOTES, 'UTF-8');
    ?>
    <script>
        window.memberId = '<?echo $member_id?>';
        window.BX24 = BX24;
        window.isMobile = false;
        
        if (typeof BX24 !== 'undefined' && BX24.init) {
            BX24.init(function() {
                if (BX24.isMobile && BX24.isMobile()) {
                    window.isMobile = true;
                    document.body.classList.add('bx-mobile');
                    
                    setTimeout(function() {
                        if (typeof BX24.resizeWindow === 'function') {
                            var height = document.body.scrollHeight;
                            BX24.resizeWindow(null, height + 50);
                        }
                    }, 100);
                }
            });
        } else {
            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                window.isMobile = true;
                document.body.classList.add('bx-mobile');
            }
        }
        
        window.showNotification = function(message, type = 'info') {
            if (window.BX24 && BX24.showNotify) {
                BX24.showNotify({
                    content: message,
                    autoHideDelay: 3000
                });
            } else {
                const notification = document.createElement('div');
                notification.textContent = message;
                notification.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    right: 20px;
                    background: ${type === 'error' ? '#f1361b' : '#2fc6f6'};
                    color: white;
                    padding: 12px;
                    border-radius: 8px;
                    text-align: center;
                    z-index: 10000;
                    animation: slideUp 0.3s ease;
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            }
        };
        
        window.showLoader = function(text = 'Загрузка...') {
            if (window.isMobile && window.BX24 && BX24.showLoading) {
                BX24.showLoading({ text: text });
            }
        };
        
        window.hideLoader = function() {
            if (window.isMobile && window.BX24 && BX24.hideLoading) {
                BX24.hideLoading();
            }
        };
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideUp {
                from {
                    transform: translateY(100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
    
    <div id="app">
        <div class="bx-app-header">
            <div class="bx-app-header__container">
                <div class="bx-app-header__logo">
                    <a href="https://overplan.ru/?utm_source=b24app" target="_blank" class="bx-app-header__logo-link">
                        <img src="img/logo_overplan.png" alt="Overplan" class="bx-app-header__logo-img">
                    </a>
                </div>
                <div class="bx-app-header__nav">
                    <a href="./index.php?REQUEST=<?= $request_json ?>" class="bx-app-header__link">
                        <span class="bx-app-header__link-text">Настройка приложения</span>
                    </a>
                    <a href="./indexReports.php?REQUEST=<?= $request_json ?>" class="bx-app-header__link bx-app-header__link--active">
                        <span class="bx-app-header__link-text">Настройка уведомлений</span>
                    </a>
                    <a href="https://t.me/appsupportbot" target="_blank" class="bx-app-header__link">
                        <span class="bx-app-header__link-text">Обратная связь</span>
                    </a>
                </div>
                <button class="bx-app-header__mobile-toggle" @click="toggleMobileMenu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
        
        <div class="bx-app-content">
            <div class="bx-card bx-card--narrow">
                <div class="bx-card__header">
                    <h2 class="bx-card__title">Настройка уведомлений</h2>
                </div>
                <div class="bx-card__body">
                    <!-- Статус бота -->
                    <div class="bx-status" :class="botExists ? 'bx-status--success' : 'bx-status--error'">
    <span v-if="botExists">✅ Статус: Чат-бот активен (ID: {{ botId }})</span>
    <span v-else>❌ Статус: Чат-бот не найден. Нажмите "Добавить чат-бота"</span>
</div>
                    
                    <div class="bx-form-row">
                        <label class="bx-label">Добавьте пользователей в чат для уведомлений:</label>
                        <multiselect 
                            v-model="selectedUsers" 
                            name="selection_users" 
                            placeholder="Выберите пользователей" 
                            label="name" 
                            track-by="value" 
                            deselect-label="Убрать" 
                            select-label="Выбрать" 
                            selected-label="" 
                            open-direction="bottom" 
                            :options="allUsers" 
                            :multiple="true" 
                            :taggable="false" 
                            :close-on-select="false" 
                            :limit="4"
                            class="bx-multiselect">
                            <span slot="noResult">Такого варианта нет</span>
                        </multiselect>
                    </div>
                    
                    <div class="selected-users-list" v-if="selectedUsers.length > 0">
                        <div class="selected-users-title">Выбранные пользователи:</div>
                        <div class="selected-users-tags">
                            <div v-for="user in selectedUsers" :key="user.value" class="user-tag">
                                {{ user.name }}
                                <button class="remove-user" @click="removeUser(user)">×</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bx-actions bx-actions--compact">
                        <button class="ui-btn ui-btn-primary" @click="addInChat" :disabled="loading || selectedUsers.length === 0">
                            {{ loading ? 'Добавление...' : '➕ Добавить пользователей' }}
                        </button>
                        <button class="ui-btn ui-btn-danger" @click="delChatBot" :disabled="loading">
                            {{ loading ? 'Удаление...' : '🗑️ Удалить чат-бота' }}
                        </button>
                        <button class="ui-btn ui-btn-success" @click="addChatBot" :disabled="loading">
                            {{ loading ? 'Добавление...' : '🤖 Добавить чат-бота' }}
                        </button>
                    </div>
                    
                    <div class="bx-info-text bx-info-text--panel">
                        <strong>ℹ️ Информация:</strong><br>
                        • Чат-бот необходим для отправки уведомлений в чат "ALLChat Overplan"<br>
                        • Если бот не найден - нажмите "Добавить чат-бота"<br>
                        • После добавления бота, перейдите в настройки кнопок и создайте кнопку в чате
                    </div>
                </div>
            </div>
        </div>

        <div v-if="loader" class="bx-loader">
            <div class="bx-loader__overlay"></div>
            <div class="bx-loader__spinner"></div>
        </div>
    </div>
    
    <script type="module" src="js/scriptRep.js?v=<?= time() ?>"></script>
</body>
</html>