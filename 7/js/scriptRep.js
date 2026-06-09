// js/scriptRep.js

const __apiVer = (typeof window !== 'undefined' && window.__apiVersion) || Date.now();
const {
    getAllUsers, addUsersInChat, deleteChatBot, addChatBot
} = await import(`../js/api.js?v=${__apiVer}`);

Vue.component("modal", {
    template: "#modal-template",
});

var app = new Vue({
    el: "#app",
    components: {
        Multiselect: window.VueMultiselect.default,
    },
    data() {
    return {
        loader: false,
        loading: false,
        mobileMenuOpen: false,
        allUsers: [],
        selectedUsers: [],
        botExists: false,
        botId: null
    };
},
    methods: {
        // Адаптация размера окна для мобильного приложения
        resizeForMobile() {
            if (window.isMobile && window.BX24 && typeof BX24.resizeWindow === 'function') {
                setTimeout(() => {
                    try {
                        const height = document.body.scrollHeight;
                        BX24.resizeWindow(null, height + 50);
                    } catch(e) {
                        console.warn('Resize error:', e);
                    }
                }, 150);
            }
        },
        
        // Показать уведомление с учетом мобильной версии
        showNotification(message, type = 'info') {
            if (window.showNotification) {
                window.showNotification(message, type);
            } else if (window.BX24 && BX24.showNotify) {
                BX24.showNotify({
                    content: message,
                    autoHideDelay: 3000
                });
            } else {
                alert(message);
            }
        },
        
        toggleMobileMenu() {
            this.mobileMenuOpen = !this.mobileMenuOpen;
            const nav = document.querySelector('.bx-app-header__nav');
            if (nav) {
                nav.classList.toggle('bx-app-header__nav--open', this.mobileMenuOpen);
                
                // Добавляем оверлей для мобильного меню
                if (this.mobileMenuOpen && !document.querySelector('.bx-mobile-menu-overlay')) {
                    const overlay = document.createElement('div');
                    overlay.className = 'bx-mobile-menu-overlay';
                    overlay.onclick = () => this.toggleMobileMenu();
                    document.body.appendChild(overlay);
                } else if (!this.mobileMenuOpen) {
                    const overlay = document.querySelector('.bx-mobile-menu-overlay');
                    if (overlay) overlay.remove();
                }
            }
            this.resizeForMobile();
        },
        
        // Проверка существования бота
        async checkBotExists() {
    try {
        const response = await deleteChatBot(window.memberId, true);
        if (response && response.exists === true && response.botId) {
            this.botExists = true;
            this.botId = response.botId;
        } else {
            this.botExists = false;
            this.botId = null;
        }
    } catch (error) {
        this.botExists = false;
        this.botId = null;
    }
},
        
        // получаем всех юзеров
        async getUsers() {
            if (window.showLoader) window.showLoader('Загрузка пользователей...');
            this.loader = true;
            
            try {
                console.log('Загрузка пользователей...');
                const response = await getAllUsers(window.memberId);
                console.log('Ответ:', response);
                
                if (response && response.result && Array.isArray(response.result)) {
                    this.allUsers = response.result;
                    console.log(`Загружено ${this.allUsers.length} пользователей`);
                    this.showNotification(`Загружено ${this.allUsers.length} пользователей`, 'success');
                } else {
                    console.warn('Некорректный ответ:', response);
                    this.allUsers = [];
                    this.showNotification('Не удалось загрузить пользователей', 'error');
                }
            } catch (error) {
                console.error('Ошибка получения пользователей:', error);
                this.showNotification('Ошибка загрузки пользователей: ' + (error.message || 'Неизвестная ошибка'), 'error');
                this.allUsers = [];
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
                this.resizeForMobile();
            }
        },
        
        // удаление пользователя из выбранных
        removeUser(user) {
            const index = this.selectedUsers.findIndex(u => u.value === user.value);
            if (index !== -1) {
                this.selectedUsers.splice(index, 1);
                this.resizeForMobile();
            }
        },
        
        // ф-я добавления выбранных юзеров в чат
        async addInChat() {
            if (this.selectedUsers.length === 0) {
                this.showNotification('Выберите пользователей для добавления', 'error');
                return;
            }
            
            if (window.showLoader) window.showLoader('Добавление пользователей...');
            this.loading = true;
            this.loader = true;
            
            try {
                console.log('Добавление пользователей в чат:', this.selectedUsers);
                const response = await addUsersInChat(window.memberId, this.selectedUsers);
                console.log('Ответ сервера:', response);
                
                if (response && response.success === true) {
                    this.showNotification(`✅ ${this.selectedUsers.length} пользователей добавлено в чат`, 'success');
                    this.selectedUsers = [];
                } else if (response && response.error) {
                    this.showNotification(`❌ Ошибка: ${response.error}`, 'error');
                } else {
                    this.showNotification('❌ Ошибка при добавлении пользователей', 'error');
                }
            } catch (error) {
                console.error('Ошибка добавления:', error);
                this.showNotification('❌ Ошибка при добавлении пользователей: ' + (error.message || 'Неизвестная ошибка'), 'error');
            } finally {
                this.loader = false;
                this.loading = false;
                if (window.hideLoader) window.hideLoader();
                this.resizeForMobile();
            }
        },
        
        // ф-я удаления чатбота с портала
        async delChatBot() {
            const confirmed = confirm('⚠️ Внимание!\n\nУдаление бота отключит все уведомления на портале.\n\nВы уверены?');
            
            if (!confirmed) return;
            
            if (window.showLoader) window.showLoader('Удаление чат-бота...');
            this.loading = true;
            this.loader = true;
            
            try {
                console.log('Удаление чат-бота...');
                const response = await deleteChatBot(window.memberId);
                console.log('Ответ сервера:', response);
                
                if (response && response.success === true) {
                    this.showNotification('🤖 Чат-бот успешно удален', 'success');
                    this.botExists = false;
                    
                    // Обновляем состояние кнопки через 1 секунду
                    setTimeout(() => {
                        this.resizeForMobile();
                    }, 1000);
                } else if (response && response.error) {
                    this.showNotification(`❌ Ошибка: ${response.error}`, 'error');
                } else {
                    this.showNotification('❌ Ошибка при удалении чат-бота', 'error');
                }
            } catch (error) {
                console.error('Ошибка удаления:', error);
                this.showNotification('❌ Ошибка при удалении чат-бота: ' + (error.message || 'Неизвестная ошибка'), 'error');
            } finally {
                this.loader = false;
                this.loading = false;
                if (window.hideLoader) window.hideLoader();
                this.resizeForMobile();
            }
        },
        
        // ф-я добавления чатбота на портал
        async addChatBot() {
            const confirmed = confirm('🤖 Добавить чат-бота?\n\nБот будет использоваться для отправки уведомлений в чат.');
            
            if (!confirmed) return;
            
            if (window.showLoader) window.showLoader('Добавление чат-бота...');
            this.loading = true;
            this.loader = true;
            
            try {
                console.log('Добавление чат-бота...');
                const response = await addChatBot(window.memberId);
                console.log('Ответ сервера:', response);
                
                if (response && response.success === true) {
                    this.showNotification('🤖 Чат-бот успешно добавлен', 'success');
                    this.botExists = true;
                    
                    // Обновляем состояние кнопки через 1 секунду
                    setTimeout(() => {
                        this.resizeForMobile();
                    }, 1000);
                } else if (response && response.error) {
                    this.showNotification(`❌ Ошибка: ${response.error}`, 'error');
                } else {
                    this.showNotification('❌ Ошибка при добавлении чат-бота', 'error');
                }
            } catch (error) {
                console.error('Ошибка добавления:', error);
                this.showNotification('❌ Ошибка при добавлении чат-бота: ' + (error.message || 'Неизвестная ошибка'), 'error');
            } finally {
                this.loader = false;
                this.loading = false;
                if (window.hideLoader) window.hideLoader();
                this.resizeForMobile();
            }
        }
    },
    watch: {
        selectedUsers: {
            handler() {
                this.resizeForMobile();
            },
            deep: true
        },
        loader() {
            this.resizeForMobile();
        }
    },
    async mounted() {
        console.log('Приложение уведомлений запущено');
        
        // Инициализация BX24
        if (window.BX24) {
            BX24.init(() => {
                this.resizeForMobile();
            });
        }
        
        this.resizeForMobile();
        await this.getUsers();
        await this.checkBotExists();
        this.resizeForMobile();
        
        // Наблюдатель за изменениями для мобильной адаптации
        if (window.isMobile) {
            const observer = new MutationObserver(() => this.resizeForMobile());
            observer.observe(document.body, { 
                childList: true, 
                subtree: true, 
                attributes: true 
            });
        }
        
        // Обработка изменения ориентации экрана
        window.addEventListener('orientationchange', () => {
            setTimeout(() => this.resizeForMobile(), 100);
        });
    }
});