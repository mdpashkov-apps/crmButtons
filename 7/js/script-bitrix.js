import { 
    getAllButtons, getTemplate, getMoreButtons, saveBtnSettings, 
    deleteButton, createButtonInCrm, deleteButtonInCrm, getButtonData,
    getAllEntitys, getBPforEntity, getDocumentsforEntity, getAllLists,
    getListFields, getEntityFieldsForList, getCrmFieldsLink,
    createButtonInChat, deleteButtonInChat, getFeedWorkflowsList
} from "../js/api.js";

Vue.directive('click-outside', {
    bind(el, binding, vnode) {
        el.clickOutsideEvent = function(event) {
            if (!(el === event.target || el.contains(event.target))) {
                vnode.context[binding.expression]();
            }
        };
        document.body.addEventListener('click', el.clickOutsideEvent);
    },
    unbind(el) {
        document.body.removeEventListener('click', el.clickOutsideEvent);
    }
});

var app = new Vue({
    el: "#app",
    components: {
        Multiselect: window.VueMultiselect.default
    },
    data() {
        return {
            loader: false,
            mobileMenuOpen: false,
            originalButtonStyles: null,
            newButton: false,
            portalButtons: [],
            morebuttons: [],
            showMore: false,
            loadingMore: false,
            current_button: {
                buttonColor_FIELDS: '#2fc6f6',
                textColor_FIELDS: '#ffffff',
                buttonBorderColor_FIELDS: '#ffffff',
                buttonRadius_FIELDS: 5,
                textOnTheButton_FIELDS: 'Кнопка',
                buttonName_FIELDS: 'Новая кнопка',
                buttonInChat_FIELDS: false,
                usingTheIcon_FIELDS: false,
                iconOnTheButton_FIELDS: '',
                buttonBorder_FIELDS: false,
                buttonBorderWidth_FIELDS: 1,
                buttonActionsId_FIELDS: [],
                businessProcessesValue_FIELDS: [],
                documentTemplatesValue_FIELDS: [],
                listsValue_FIELDS: null,
                fieldsTable_FIELDS: [],
                link_FIELDS: '',
                crmLinkFields_FIELDS: null,
                entitySelection_FIELDS: null,
                buttonInCRM_FIELDS: false,
                chatCommandId_FIELDS: '',
                // Новые поля для БП из ленты
                buttonActionType_FIELDS: 'url',
                workflowTemplateId_FIELDS: null,
                workflowDocumentId_FIELDS: null,
                workflowFromFeed_FIELDS: false
            },
            activeButtonId: null,
            allEntitys: [],
            allBizProc: [],
            allDocuments: [],
            allLists: [],
            allCrmFieldsLink: [],
            entFields: [],
            // Настройки для БП из ленты
            buttonActionType: 'url',
            allFeedWorkflows: [],
            selectedWorkflowTemplate: null,
            selectedWorkflowDocument: '',
            // Аккордеоны
            accordion_0: false,
            accordion_1: false,
            accordion_2: false,
            accordion_3: false,
            accordion_4: false,
            accordion_5: false,
            accordion_6: false,
            // Флаги
            flagsButtonBizproc: false,
            flagsButtonDocument: false,
            flagsList: false,
            flagsButtonEnteredLink: false,
            flagsButtonCrmLink: false
        };
    },
    computed: {
        totalButtonsCount() {
            return this.portalButtons.length + (this.newButton ? 1 : 0);
        },
        previewButtonStyle() {
            const border = this.current_button.buttonBorder_FIELDS 
                ? `${this.current_button.buttonBorderWidth_FIELDS || 0}px solid ${this.current_button.buttonBorderColor_FIELDS}`
                : 'none';
            return {
                color: this.current_button.textColor_FIELDS,
                backgroundColor: this.current_button.buttonColor_FIELDS,
                borderRadius: `${this.current_button.buttonRadius_FIELDS || 0}px`,
                border: border
            };
        }
    },
    methods: {
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
        
        toggleMoreButtons() {
            this.showMore = !this.showMore;
            if (this.showMore && this.morebuttons.length === 0 && !this.loadingMore) {
                this.loadMoreButtons();
            }
            this.resizeForMobile();
        },
        
        hideMoreButtons() {
            this.showMore = false;
        },
        
        toggleAccordion(index) {
            const key = `accordion_${index}`;
            this[key] = !this[key];
            const flagsMap = {
                0: 'flagsButtonBizproc',
                1: 'flagsButtonDocument',
                2: 'flagsList',
                3: 'flagsButtonEnteredLink',
                4: 'flagsButtonCrmLink'
            };
            if (flagsMap[index]) {
                this[flagsMap[index]] = true;
            }
            this.resizeForMobile();
        },
        
        toggleAccordionWorkflow() {
            this.accordion_6 = !this.accordion_6;
            if (this.accordion_6 && this.allFeedWorkflows.length === 0) {
                this.getFeedWorkflows();
            }
            this.resizeForMobile();
        },
        
        async loadMoreButtons() {
            this.loadingMore = true;
            try {
                let response = await getMoreButtons(window.memberId);
                this.morebuttons = response.result || [];
            } finally {
                this.loadingMore = false;
            }
        },
        
        async getButtons(selectLast = false) {
            if (window.showLoader) window.showLoader('Загрузка кнопок...');
            this.loader = true;
            
            try {
                const response = await getAllButtons(window.memberId);
                const buttons = Array.isArray(response?.result?.result) ? response.result.result : [];
                this.portalButtons = buttons;
                
                if (this.portalButtons.length === 0) {
                    await this.getTemp();
                    this.activeButtonId = null;
                    this.newButton = true;
                    return;
                }
                
                const buttonToSelect = selectLast ? this.portalButtons[this.portalButtons.length - 1] : this.portalButtons[0];
                this.selectButton(buttonToSelect);
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
                this.resizeForMobile();
            }
        },
        
        async getTemp() {
            let response = await getTemplate(window.memberId);
            this.current_button = { ...this.current_button, ...response.result };
            // Инициализируем новые поля
            this.buttonActionType = this.current_button.buttonActionType_FIELDS || 'url';
            this.selectedWorkflowTemplate = null;
            this.selectedWorkflowDocument = '';
        },
        
        async selectButton(button) {
            if (window.showLoader) window.showLoader('Загрузка настроек...');
            this.loader = true;
            
            try {
                this.newButton = false;
                this.activeButtonId = button.ID;
                let response = await getButtonData(window.memberId, button);
                this.current_button = JSON.parse(JSON.stringify(response.result));
                this.normalizeBooleans();
                
                if (this.current_button?.entitySelection_FIELDS) {
                    await this.getEntFields();
                }
                this.normalizeFieldsTable();
                
                this.originalButtonStyles = {
                    buttonColor_FIELDS: this.current_button.buttonColor_FIELDS,
                    buttonRadius_FIELDS: this.current_button.buttonRadius_FIELDS,
                    buttonBorder_FIELDS: this.current_button.buttonBorder_FIELDS,
                    buttonBorderWidth_FIELDS: this.current_button.buttonBorderWidth_FIELDS,
                    buttonBorderColor_FIELDS: this.current_button.buttonBorderColor_FIELDS
                };
                
                if (this.current_button.listsValue_FIELDS) {
                    await this.onListChange(true);
                }
                
                if (!Array.isArray(this.current_button.buttonActionsId_FIELDS)) {
                    this.$set(this.current_button, 'buttonActionsId_FIELDS', []);
                }
                
                // Загружаем настройки БП из ленты
                this.buttonActionType = this.current_button.buttonActionType_FIELDS || 'url';
                if (this.buttonActionType === 'workflow' && this.current_button.workflowTemplateId_FIELDS) {
                    await this.getFeedWorkflows();
                    this.selectedWorkflowTemplate = this.allFeedWorkflows.find(
                        w => w.id == this.current_button.workflowTemplateId_FIELDS
                    );
                    this.selectedWorkflowDocument = this.current_button.workflowDocumentId_FIELDS || '';
                } else {
                    this.selectedWorkflowTemplate = null;
                    this.selectedWorkflowDocument = '';
                }
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
                this.resizeForMobile();
            }
        },
        
        normalizeBooleans() {
            const boolFields = ['buttonBorder_FIELDS', 'usingTheIcon_FIELDS', 'buttonInCRM_FIELDS', 'buttonInChat_FIELDS', 'workflowFromFeed_FIELDS'];
            boolFields.forEach(field => {
                if (field in this.current_button) {
                    this.current_button[field] = this.current_button[field] === true || 
                                                  this.current_button[field] === 1 || 
                                                  this.current_button[field] === '1' || 
                                                  this.current_button[field] === 'true';
                }
            });
        },
        
        normalizeFieldsTable() {
            const ft = this.current_button.fieldsTable_FIELDS;
            if (!Array.isArray(ft) || ft.length !== 2) return;
            
            const [crmFields, listFields] = ft;
            this.current_button.fieldsTable_FIELDS = listFields.map((listCode, i) => {
                let entField = null;
                if (crmFields[i] && crmFields[i] !== 'null') {
                    entField = this.entFields.find(f => f.value === crmFields[i]) || null;
                }
                return { value: listCode, name: listCode, entField };
            });
        },
        
        async saveSettings() {
            if (window.showLoader) window.showLoader('Сохранение настроек...');
            this.loader = true;
            
            try {
                // Добавляем новые поля в current_button перед сохранением
                this.current_button.buttonActionType_FIELDS = this.buttonActionType;
                this.current_button.workflowTemplateId_FIELDS = this.selectedWorkflowTemplate?.id || null;
                this.current_button.workflowDocumentId_FIELDS = this.selectedWorkflowDocument || null;
                this.current_button.workflowFromFeed_FIELDS = this.buttonActionType === 'workflow';
                
                let response = await saveBtnSettings(window.memberId, this.current_button, this.activeButtonId);
                
                if (response.result) {
                    this.originalButtonStyles = {
                        buttonColor_FIELDS: this.current_button.buttonColor_FIELDS,
                        buttonRadius_FIELDS: this.current_button.buttonRadius_FIELDS,
                        buttonBorder_FIELDS: this.current_button.buttonBorder_FIELDS,
                        buttonBorderWidth_FIELDS: this.current_button.buttonBorderWidth_FIELDS,
                        buttonBorderColor_FIELDS: this.current_button.buttonBorderColor_FIELDS
                    };
                    this.showNotification('Настройки успешно сохранены', 'success');
                    this.resizeForMobile();
                }
                
                if (this.newButton && response.result) {
                    this.newButton = false;
                    await this.getButtons(true);
                }
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                this.showNotification('Ошибка при сохранении настроек', 'error');
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
            }
        },
        
        async delButton() {
            if (!this.activeButtonId) return;
            
            const confirmed = confirm('Вы уверены, что хотите удалить эту кнопку?');
            if (!confirmed) return;
            
            if (window.showLoader) window.showLoader('Удаление кнопки...');
            this.loader = true;
            
            try {
                await deleteButton(window.memberId, this.activeButtonId);
                
                this.portalButtons = this.portalButtons.filter(b => b.ID !== this.activeButtonId);
                this.activeButtonId = null;
                
                if (this.portalButtons.length > 0) {
                    await this.selectButton(this.portalButtons[0]);
                } else {
                    await this.getTemp();
                    this.newButton = true;
                }
                
                this.showNotification('Кнопка успешно удалена', 'success');
                this.resizeForMobile();
            } catch (error) {
                console.error('Ошибка удаления:', error);
                this.showNotification('Ошибка при удалении кнопки', 'error');
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
            }
        },
        
        async createBtnCrm() {
            if (window.showLoader) window.showLoader('Создание кнопки в CRM...');
            this.loader = true;
            
            try {
                await createButtonInCrm(window.memberId, this.activeButtonId, window.memberId, this.current_button);
                const response = await getButtonData(window.memberId, { ID: this.activeButtonId });
                this.current_button = JSON.parse(JSON.stringify(response.result));
                this.normalizeBooleans();
                this.showNotification('Кнопка создана в CRM', 'success');
                this.resizeForMobile();
            } catch (error) {
                console.error('Ошибка создания:', error);
                this.showNotification('Ошибка при создании кнопки в CRM', 'error');
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
            }
        },
        
        async deleteBtnCrm() {
            if (window.showLoader) window.showLoader('Удаление кнопки из CRM...');
            this.loader = true;
            
            try {
                await deleteButtonInCrm(window.memberId, this.activeButtonId, window.memberId);
                const response = await getButtonData(window.memberId, { ID: this.activeButtonId });
                this.current_button = JSON.parse(JSON.stringify(response.result));
                this.normalizeBooleans();
                this.showNotification('Кнопка удалена из CRM', 'success');
                this.resizeForMobile();
            } catch (error) {
                console.error('Ошибка удаления:', error);
                this.showNotification('Ошибка при удалении кнопки из CRM', 'error');
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
            }
        },
        
        async createBtnChat() {
            if (window.showLoader) window.showLoader('Создание кнопки в чате...');
            this.loader = true;
            
            try {
                await createButtonInChat(window.memberId, this.activeButtonId, this.current_button);
                const response = await getButtonData(window.memberId, { ID: this.activeButtonId });
                this.current_button = JSON.parse(JSON.stringify(response.result));
                this.normalizeBooleans();
                this.showNotification('Кнопка создана в чате', 'success');
                this.resizeForMobile();
            } catch (error) {
                console.error('Ошибка создания:', error);
                this.showNotification('Ошибка при создании кнопки в чате', 'error');
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
            }
        },
        
        async deleteBtnChat() {
            if (window.showLoader) window.showLoader('Удаление кнопки из чата...');
            this.loader = true;
            
            try {
                await deleteButtonInChat(window.memberId, this.activeButtonId);
                const response = await getButtonData(window.memberId, { ID: this.activeButtonId });
                this.current_button = JSON.parse(JSON.stringify(response.result));
                this.normalizeBooleans();
                this.showNotification('Кнопка удалена из чата', 'success');
                this.resizeForMobile();
            } catch (error) {
                console.error('Ошибка удаления:', error);
                this.showNotification('Ошибка при удалении кнопки из чата', 'error');
            } finally {
                this.loader = false;
                if (window.hideLoader) window.hideLoader();
            }
        },
        
        async createBtn() {
            this.newButton = true;
            let response = await getTemplate(window.memberId);
            this.current_button = { ...this.current_button, ...response.result };
            this.activeButtonId = null;
            this.buttonActionType = 'url';
            this.selectedWorkflowTemplate = null;
            this.selectedWorkflowDocument = '';
            this.originalButtonStyles = {
                buttonColor_FIELDS: this.current_button.buttonColor_FIELDS,
                buttonRadius_FIELDS: this.current_button.buttonRadius_FIELDS,
                buttonBorder_FIELDS: this.current_button.buttonBorder_FIELDS,
                buttonBorderWidth_FIELDS: this.current_button.buttonBorderWidth_FIELDS,
                buttonBorderColor_FIELDS: this.current_button.buttonBorderColor_FIELDS
            };
            this.resizeForMobile();
            this.showNotification('Создана новая кнопка', 'success');
        },
        
        SetStandardStyles() {
            this.current_button.textColor_FIELDS = "#ffffff";
            this.current_button.buttonColor_FIELDS = "#2fc6f6";
            this.current_button.buttonRadius_FIELDS = "0";
            this.current_button.buttonBorderWidth_FIELDS = 0;
            this.current_button.buttonBorder_FIELDS = false;
            this.resizeForMobile();
        },
        
        async resetStylesButton() {
            if (!this.originalButtonStyles) return;
            Object.keys(this.originalButtonStyles).forEach(key => {
                this.$set(this.current_button, key, this.originalButtonStyles[key]);
            });
            this.resizeForMobile();
        },
        
        async getEntitys() {
            this.loader = true;
            try {
                let response = await getAllEntitys(window.memberId);
                this.allEntitys = response.result;
            } finally {
                this.loader = false;
            }
        },
        
        async getBP() {
            this.loader = true;
            try {
                let response = await getBPforEntity(window.memberId, this.current_button);
                this.allBizProc = response.result;
            } finally {
                this.loader = false;
            }
        },
        
        async getDocs() {
            this.loader = true;
            try {
                let response = await getDocumentsforEntity(window.memberId, this.current_button);
                this.allDocuments = response.result;
            } finally {
                this.loader = false;
            }
        },
        
        async getLists() {
            this.loader = true;
            try {
                let response = await getAllLists(window.memberId, this.current_button);
                this.allLists = response.result;
            } finally {
                this.loader = false;
            }
        },
        
        async onListChange(silent = false) {
            if (!silent) this.loader = true;
            try {
                let response = await getListFields(window.memberId, this.current_button);
                let freshFields = response.result;
                
                if (Array.isArray(this.current_button.fieldsTable_FIELDS)) {
                    freshFields.forEach(f => { f.entField = null; });
                }
                this.current_button.fieldsTable_FIELDS = freshFields;
                this.resizeForMobile();
            } finally {
                if (!silent) this.loader = false;
            }
        },
        
        async getEntFields() {
            this.loader = true;
            try {
                let response = await getEntityFieldsForList(window.memberId, this.current_button);
                this.entFields = response.result;
            } finally {
                this.loader = false;
            }
        },
        
        async getCrmLinks() {
            this.loader = true;
            try {
                let response = await getCrmFieldsLink(window.memberId, this.current_button);
                this.allCrmFieldsLink = response.result;
            } finally {
                this.loader = false;
            }
        },
        
        async getFeedWorkflows() {
            this.loader = true;
            try {
                let response = await getFeedWorkflowsList(window.memberId);
                this.allFeedWorkflows = response.result || [];
            } catch (error) {
                console.error('Ошибка загрузки БП из ленты:', error);
                this.allFeedWorkflows = [];
            } finally {
                this.loader = false;
            }
        },
        
        onActionTypeChange() {
            if (this.buttonActionType === 'workflow') {
                this.getFeedWorkflows();
            } else {
                this.selectedWorkflowTemplate = null;
                this.selectedWorkflowDocument = '';
            }
            this.resizeForMobile();
        },
        
        onEntityChange() {
            this.current_button.businessProcessesValue_FIELDS = [];
            this.allBizProc = [];
            this.current_button.documentTemplatesValue_FIELDS = [];
            this.allDocuments = [];
            this.current_button.listsValue_FIELDS = null;
            this.current_button.fieldsTable_FIELDS = [];
            this.allLists = [];
            this.entFields = [];
            this.current_button.crmLinkFields_FIELDS = null;
            this.allCrmFieldsLink = [];
            this.accordion_0 = false;
            this.accordion_1 = false;
            this.accordion_2 = false;
            this.accordion_4 = false;
            this.resizeForMobile();
        }
    },
    
    async mounted() {
        if (window.BX24) {
            BX24.init(() => {
                this.resizeForMobile();
            });
        }
        
        this.resizeForMobile();
        await this.getButtons();
        this.resizeForMobile();
        
        if (window.isMobile) {
            const observer = new MutationObserver(() => this.resizeForMobile());
            observer.observe(document.body, { 
                childList: true, 
                subtree: true, 
                attributes: true,
                attributeFilter: ['class', 'style']
            });
            
            let touchStartX = 0;
            document.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
            });
            
            document.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].clientX;
                if (touchEndX - touchStartX > 100 && this.mobileMenuOpen) {
                    this.toggleMobileMenu();
                }
            });
        }
        
        window.addEventListener('orientationchange', () => {
            setTimeout(() => this.resizeForMobile(), 100);
        });
    }
});