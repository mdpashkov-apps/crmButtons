import {getBpParams, startBp, createDocument, createListElement, OpenCrmLink} from "../js/api.js";

const BX24 = await window.__bxReady;

var app = new Vue({
    el: "#app",
    components: {
        Multiselect: window.VueMultiselect.default
    },
    data() {
        return {
            loader: false,
            paramResult: null,
            formValues: {},
            currentBpIndex: 0,
            allUsers: [],
            boolOptions: [
                { value: 'Y', name: 'Да' },
                { value: 'N', name: 'Нет' },
            ],
        };
    },
    methods: {
        fixDropdowns() {
            var activeMultiselects = document.querySelectorAll('.multiselect.multiselect--active');
            activeMultiselects.forEach(function(ms) {
                var dropdown = ms.querySelector('.multiselect__content-wrapper');
                if (!dropdown) return;
                
                var rect = ms.getBoundingClientRect();
                dropdown.style.position = 'absolute';
                dropdown.style.top = '100%';
                dropdown.style.bottom = 'auto';
                dropdown.style.left = '0';
                dropdown.style.width = rect.width + 'px';
                dropdown.style.maxHeight = '250px';
                dropdown.style.overflowY = 'auto';
                dropdown.style.zIndex = '999999';
                dropdown.style.display = 'block';
            });
        },
        
        getSingleValue(bpId, paramName) {
            if (!this.formValues[bpId]) return null;
            const val = this.formValues[bpId][paramName];
            if (val && typeof val === 'object' && val.value !== undefined) {
                return val;
            }
            if (Array.isArray(val) && val.length > 0) {
                return val[0];
            }
            return null;
        },
        
        updateSingleValue(bpId, paramName, value) {
            if (!this.formValues[bpId]) {
                this.$set(this.formValues, bpId, {});
            }
            if (value && typeof value === 'object' && value.value !== undefined) {
                this.$set(this.formValues[bpId], paramName, [value]);
            } else {
                this.$set(this.formValues[bpId], paramName, [value]);
            }
            this.resizeBx();
        },
        
        getSingleUserValue(bpId, paramName) {
            if (!this.formValues[bpId]) return null;
            const val = this.formValues[bpId][paramName];
            if (Array.isArray(val) && val.length > 0 && val[0] && val[0].value) {
                return val[0];
            }
            return null;
        },
        
        updateSingleUserValue(bpId, paramName, value) {
            if (!this.formValues[bpId]) {
                this.$set(this.formValues, bpId, {});
            }
            this.$set(this.formValues[bpId], paramName, value ? [value] : []);
            this.resizeBx();
        },
        
        getMultipleArray(bpId, paramName) {
            if (!this.formValues[bpId]) return [];
            const val = this.formValues[bpId][paramName];
            if (Array.isArray(val)) return val;
            return [];
        },
        
        getSelectedUserIds(bpId, paramName, currentIndex) {
            const arr = this.getMultipleArray(bpId, paramName);
            return arr
                .filter((item, idx) => idx !== currentIndex && item && item.value)
                .map(item => item.value);
        },
        
        isUserSelected(bpId, paramName, userId, currentIndex) {
            const selectedIds = this.getSelectedUserIds(bpId, paramName, currentIndex);
            return selectedIds.includes(userId);
        },
        
        getUserOptions(bpId, paramName, currentIndex) {
            const selectedIds = this.getSelectedUserIds(bpId, paramName, currentIndex);
            return this.allUsers.filter(user => !selectedIds.includes(user.value));
        },
        
        updateMultipleValue(bpId, paramName, index, value, paramType) {
            if (!this.formValues[bpId]) {
                this.$set(this.formValues, bpId, {});
            }
            let arr = this.formValues[bpId][paramName];
            if (!Array.isArray(arr)) {
                arr = [];
                this.$set(this.formValues[bpId], paramName, arr);
            }
            
            if (paramType === 'user' && value && value.value) {
                const isDuplicate = this.isUserSelected(bpId, paramName, value.value, index);
                if (isDuplicate) {
                    alert(`Пользователь "${value.name}" уже выбран в списке. Нельзя выбрать одного пользователя несколько раз.`);
                    return;
                }
            }
            
            this.$set(arr, index, value);
            this.resizeBx();
        },
        
        addMultipleField(bpId, paramName, type) {
            if (!this.formValues[bpId]) {
                this.$set(this.formValues, bpId, {});
            }
            let arr = this.formValues[bpId][paramName];
            if (!Array.isArray(arr)) {
                arr = [];
                this.$set(this.formValues[bpId], paramName, arr);
            }
            if (type === 'user' || type === 'select' || type === 'bool') {
                arr.push(null);
            } else {
                arr.push('');
            }
            this.resizeBx();
        },
        
        removeMultipleField(bpId, paramName, index) {
            if (index === 0) return;
            let arr = this.formValues[bpId][paramName];
            if (Array.isArray(arr) && arr.length > index) {
                arr.splice(index, 1);
            }
            this.resizeBx();
        },
        
        async runActions() {
            let raw = window.crmActions.buttonActionsId_FIELDS;
            
            if (!raw) {
                console.error('buttonActionsId_FIELDS is empty');
                return;
            }
            
            let actions;
            try {
                actions = JSON.parse(raw);
            } catch(e) {
                console.error('Failed to parse buttonActionsId_FIELDS:', e);
                return;
            }
            
            if (!Array.isArray(actions)) {
                actions = [actions];
            }
            
            actions = actions.filter(a => a !== null && a !== undefined);
            
            const actionsMap = {
                0: this.action0,
                1: this.action1,
                2: this.action2, 
                3: this.action3, 
                4: this.action4, 
            };
            
            for (const actionId of actions) {
                const actionFn = actionsMap[actionId];
                if (actionFn) {
                    try {
                        await actionFn();
                    } catch(e) {
                        console.error(`Action ${actionId} failed:`, e);
                    }
                }
            }
        },

        async action0() {
            this.loader = true;
            try {
                const response = await getBpParams(window.memberId, window.crmActions, window.entityData);
                this.paramResult = response.result;
                if (response.withoutParams?.length) {
                    await this.runBpWithoutParams(response.withoutParams, response.document);
                }
                if (response.allUserFio) {
                    this.allUsers = response.allUserFio;
                }
                
                if (this.paramResult && this.paramResult.length) {
                    this.paramResult.forEach(bp => {
                        this.$set(this.formValues, bp.ID, {});
                        bp.PARAMETERS.forEach(p => {
                            let values;
                            if (p.Multiple) {
                                values = [];
                            } else {
                                const defaultVal = p.Default || '';
                                if (p.Type === 'user' || p.Type === 'select' || p.Type === 'bool') {
                                    values = [null];
                                } else {
                                    values = [defaultVal];
                                }
                            }
                            this.$set(this.formValues[bp.ID], p.Name, values);
                        });
                    });
                }
            } catch(e) {
                console.error('action0 failed:', e);
            } finally {
                this.loader = false;
            }
        },

        async runBpWithoutParams(withoutParams, document) {
            if (!withoutParams || !withoutParams.length) return;
            for (const bp of withoutParams) {
                await new Promise((resolve, reject) => {
                    BX24.callMethod(
                        'bizproc.workflow.start',
                        {
                            TEMPLATE_ID: bp.ID,
                            DOCUMENT_ID: document
                        },
                        result => {
                            if (result.error()) {
                                reject(result.error());
                            } else {
                                resolve(result.data());
                            }
                        }
                    );
                });
            }
        },

        getSelectOptions(param) {
            if (!param.Options) return [];
            return Object.entries(param.Options).map(([key, value]) => ({
                value: key,
                name: value
            }));
        },

        async action1() {
            this.loader = true;
            try {
                await createDocument(window.memberId, window.crmActions, window.entityData);
            } catch(e) {
                console.error('action1 failed:', e);
            } finally {
                this.loader = false;
            }
        },

        async action2() {
            this.loader = true;
            try {
                await createListElement(window.memberId, window.crmActions, window.entityData);
            } catch(e) {
                console.error('action2 failed:', e);
            } finally {
                this.loader = false;
            }
        },

        async action3() {
            let arbitraryLink = window.crmActions.link_FIELDS;
            if (!arbitraryLink) {
                console.error('No link provided');
                return;
            }
            try {
                if (typeof arbitraryLink === 'string' && arbitraryLink.startsWith('"')) {
                    arbitraryLink = JSON.parse(arbitraryLink);
                }
            } catch(e) {}
            
            if (!/^https?:\/\//i.test(arbitraryLink)) {
                arbitraryLink = 'https://' + arbitraryLink;
            }
            window.open(arbitraryLink, '_blank');
        },

        async action4() {
            try {
                let response = await OpenCrmLink(window.memberId, window.crmActions, window.entityData);
                let crmLink = response.result;
                if (!crmLink) {
                    console.error('No CRM link found');
                    return;
                }
                if (!/^https?:\/\//i.test(crmLink)) {
                    crmLink = 'https://' + crmLink;
                }
                window.open(crmLink, '_blank');
            } catch(e) {
                console.error('action4 failed:', e);
            }
        },

        async runCurrentBp() {
            this.loader = true;
            try {
                const bp = this.paramResult[this.currentBpIndex];
                const preparedParams = {};
                
                bp.PARAMETERS.forEach(param => {
                    let values = this.formValues[bp.ID] ? this.formValues[bp.ID][param.Name] : null;
                    if (values === undefined || values === null) return;
                    
                    if (param.Type === 'user' || param.Type === 'select' || param.Type === 'bool') {
                        if (param.Multiple) {
                            values = values.filter(v => v && v.value);
                        } else {
                            values = values[0] && values[0].value ? values[0] : null;
                        }
                    } else {
                        if (param.Multiple) {
                            values = values.filter(v => v !== '' && v !== null);
                        } else {
                            values = values[0] || '';
                        }
                    }
                    
                    preparedParams[param.paramKey] = {
                        type: param.Type,
                        multiple: !!param.Multiple,
                        value: values
                    };
                });
                
                const response = await startBp(
                    window.memberId,
                    { [bp.ID]: preparedParams },
                    window.entityData
                );

                const { templateId, document, parameters } = response;
                
                await new Promise((resolve, reject) => {
                    BX24.callMethod(
                        'bizproc.workflow.start',
                        {
                            TEMPLATE_ID: templateId,
                            DOCUMENT_ID: document,
                            PARAMETERS: parameters
                        },
                        res => {
                            if (res.error()) reject(res.error());
                            else resolve(res.data());
                        }
                    );
                });

                if (this.currentBpIndex < this.paramResult.length - 1) {
                    this.currentBpIndex++;
                } else {
                    this.paramResult = null;
                    this.currentBpIndex = 0;
                    this.formValues = {};
                }
            } catch(e) {
                console.error('runCurrentBp failed:', e);
            } finally {
                this.loader = false;
            }
        },

        resizeBx() {
            if (!window.BX24) return;
            
            const app = document.querySelector('#app');
            if (!app) return;
            
            setTimeout(() => {
                let height = app.scrollHeight;
                height = Math.max(height, 40);
                BX24.resizeWindow('100%', height);
            }, 20);
        },
    },
    
    computed: {
        isCurrentBpValid() {
            if (!this.paramResult) return false;

            const bp = this.paramResult[this.currentBpIndex];
            if (!bp) return false;

            for (const p of bp.PARAMETERS) {
                if (p.Required) {
                    const values = this.formValues[bp.ID] ? this.formValues[bp.ID][p.Name] : null;
                    
                    if (p.Multiple) {
                        if (!Array.isArray(values) || values.length === 0) return false;
                        let hasValid = false;
                        for (const v of values) {
                            if (p.Type === 'select' || p.Type === 'user' || p.Type === 'bool') {
                                if (v && v.value) {
                                    hasValid = true;
                                    break;
                                }
                            } else {
                                if (v && v !== '') {
                                    hasValid = true;
                                    break;
                                }
                            }
                        }
                        if (!hasValid) return false;
                    } else {
                        if (p.Type === 'select' || p.Type === 'user' || p.Type === 'bool') {
                            if (!values || !values[0] || !values[0].value) return false;
                        } else {
                            if (!Array.isArray(values) || !values[0] || values[0] === '') return false;
                        }
                    }
                }
            }
            return true;
        }
    },
    
    mounted() {
        this.resizeBx();
        
        // MutationObserver для отслеживания активного мультиселекта
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList && target.classList.contains('multiselect--active')) {
                        setTimeout(() => this.fixDropdowns(), 5);
                        setTimeout(() => this.resizeBx(), 150);
                    }
                }
            });
        });
        
        // Обходим все существующие мультиселекты
        document.querySelectorAll('.multiselect').forEach((el) => {
            if (!el.hasAttribute('data-observed')) {
                el.setAttribute('data-observed', 'true');
                observer.observe(el, { attributes: true });
            }
        });
        
        // MutationObserver для отслеживания появления новых мультиселектов
        const bodyObserver = new MutationObserver(() => {
            document.querySelectorAll('.multiselect').forEach((el) => {
                if (!el.hasAttribute('data-observed')) {
                    el.setAttribute('data-observed', 'true');
                    observer.observe(el, { attributes: true });
                }
            });
        });
        
        bodyObserver.observe(document.body, { childList: true, subtree: true });
        
        // Обработчик кликов для мультиселектов
        document.addEventListener('click', (event) => {
            const multiselect = event.target.closest('.multiselect');
            if (multiselect) {
                setTimeout(() => this.fixDropdowns(), 5);
                setTimeout(() => this.resizeBx(), 50);
                setTimeout(() => this.resizeBx(), 200);
            }
        });
    },
    
    updated() {
        this.resizeBx();
    },
});