const __apiVer = (typeof window !== 'undefined' && window.__apiVersion) || Date.now();
const {
    getBpParams, getBpChainParams, startBp, createDocument, createListElement, OpenCrmLink
} = await import(`../js/api.js?v=${__apiVer}`);

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
        // Цепочка БП (PRO)
        chain: null,
        chainDocument: null,
        currentStepIndex: 0,
        currentAsks: [],
        allUsers: [],
        boolOptions: [
            { value: 'Y', name: 'Да' },
            { value: 'N', name: 'Нет' },
        ],
        buttonIconHtml: window.buttonIconHtml || '',
        buttonText: window.buttonText || 'Кнопка',
        showIcon: window.showIcon || false,
    };
},
  methods: {
    async runActions() {
      let raw = window.crmActions.buttonActionsId_FIELDS;
      const actions = JSON.parse(raw);
      const actionsMap = {
        0: this.action0,
        1: this.action1,
        2: this.action2,
        3: this.action3,
        4: this.action4,
        5: this.action5,
        6: this.action6,
      };
      
      for (const actionId of actions) {
        const actionFn = actionsMap[actionId];
        if (actionFn) {
          await actionFn();
        }
      }
    },

    async action0() {
      this.loader = true;
      const response = await getBpParams(window.memberId, window.crmActions, window.entityData);
      this.paramResult = response.result;
      if (response.withoutParams?.length) {
        await this.runBpWithoutParams(response.withoutParams, response.document);
      }
      if (response.allUserFio) {
        this.allUsers = response.allUserFio;
      }
      if (response.BoolOptions) {
        this.boolOptions = response.BoolOptions;
      }
      
      this.paramResult.forEach(bp => {
        this.$set(this.formValues, bp.ID, {});
        bp.PARAMETERS.forEach(p => {
          let values;
          if (p.Type === 'user') {
            values = p.Multiple ? [] : null;
          } else if (p.Type === 'select') {
            values = p.Multiple ? [] : null;
          } else {
            const defaultVal = p.Default || '';
            values = p.Multiple ? [defaultVal] : [defaultVal];
          }
          this.$set(this.formValues[bp.ID], p.Name, values);
        });
      });
      this.loader = false;
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
      let response = await createDocument(window.memberId, window.crmActions, window.entityData);
      this.loader = false;
    },

    async action2() {
      this.loader = true;
      let response = await createListElement(window.memberId, window.crmActions, window.entityData);
      this.loader = false;
    },

    async action3() {
      let arbitraryLink = window.crmActions.link_FIELDS;
      if (!/^https?:\/\//i.test(arbitraryLink)) {
        arbitraryLink = 'https://' + arbitraryLink;
      }
      window.open(arbitraryLink, '_blank');
    },

    async action4() {
      let template = window.crmActions.linkWithParams_FIELDS;
      if (!template) return;

      if (/\{[^{}]+\}/.test(template)) {
        this.loader = true;
        try {
          template = await this.resolveLinkPlaceholders(template);
        } finally {
          this.loader = false;
        }
      }

      if (!/^https?:\/\//i.test(template)) {
        template = 'https://' + template;
      }
      window.open(template, '_blank');
    },

    // Открыть ссылку из поля CRM (legacy «Перейти по ссылке из поля CRM», бесплатное действие, ID 6)
    async action6() {
      const response = await OpenCrmLink(window.memberId, window.crmActions, window.entityData);
      let crmLink = response && response.result ? response.result : '';
      if (!crmLink) return;
      if (!/^https?:\/\//i.test(crmLink)) {
        crmLink = 'https://' + crmLink;
      }
      window.open(crmLink, '_blank');
    },

    async resolveLinkPlaceholders(template) {
      const entityData = window.entityData && window.entityData.ENTITY_DATA;
      let fields = {};

      if (entityData && entityData.entityTypeId && entityData.entityId) {
        const entityTypeId = String(entityData.entityTypeId);
        const entityId = entityData.entityId;
        const restEntityMap = { '1': 'lead', '2': 'deal', '3': 'contact', '4': 'company' };
        const restEntity = restEntityMap[entityTypeId];

        try {
          if (restEntity) {
            const data = await new Promise((resolve, reject) => {
              BX24.callMethod('crm.' + restEntity + '.get', { id: entityId }, res => {
                if (res.error()) reject(res.error());
                else resolve(res.data());
              });
            });
            fields = data || {};
          } else {
            const data = await new Promise((resolve, reject) => {
              BX24.callMethod('crm.item.get', {
                entityTypeId: parseInt(entityTypeId, 10),
                id: entityId
              }, res => {
                if (res.error()) reject(res.error());
                else resolve(res.data());
              });
            });
            fields = (data && data.item) ? data.item : {};
          }
        } catch (e) {
          console.error('Не удалось получить данные сущности для подстановки полей в ссылку:', e);
        }
      } else {
        console.warn('Контекст сущности недоступен — плейсхолдеры будут заменены на пустые значения.');
      }

      const resolveValue = (v) => {
        if (v === undefined || v === null) return '';
        if (Array.isArray(v)) {
          if (v.length === 0) return '';
          if (v[0] && typeof v[0] === 'object' && 'VALUE' in v[0]) return String(v[0].VALUE);
          return v.join(',');
        }
        if (typeof v === 'object') return JSON.stringify(v);
        return String(v);
      };

      return template.replace(/\{([^{}]+)\}/g, (_, code) => encodeURIComponent(resolveValue(fields[code])));
    },

    async action5() {
      this.loader = true;
      try {
        const response = await getBpChainParams(window.memberId, window.crmActions, window.entityData);
        this.chain = Array.isArray(response.chain) ? response.chain : [];
        this.chainDocument = response.document;
        this.currentStepIndex = 0;
        this.currentAsks = [];
        if (response.allUserFio) {
          this.allUsers = response.allUserFio;
        }

        this.chain.forEach(bp => {
          this.$set(this.formValues, bp.ID, {});
          bp.PARAMETERS.forEach(p => {
            if (this.isParamPreset(bp, p)) return;
            this.$set(this.formValues[bp.ID], p.Name, this.makeEmptyFormValue(p));
          });
        });
      } finally {
        this.loader = false;
      }
      await this.processChainStep();
    },

    isParamPreset(bp, param) {
      return bp.presets && Object.prototype.hasOwnProperty.call(bp.presets, param.paramKey);
    },

    makeEmptyFormValue(param) {
      if (param.Type === 'user' || param.Type === 'select' || param.Type === 'bool') {
        return param.Multiple ? [] : null;
      }
      const defaultVal = param.Default || '';
      return param.Multiple ? [defaultVal] : [defaultVal];
    },

    computeAsksFor(bp) {
      return bp.PARAMETERS.filter(p => !this.isParamPreset(bp, p));
    },

    async processChainStep() {
      while (this.chain && this.currentStepIndex < this.chain.length) {
        const bp = this.chain[this.currentStepIndex];
        const asks = this.computeAsksFor(bp);
        if (asks.length === 0) {
          await this.runChainBp(bp, {});
          this.currentStepIndex++;
          continue;
        }
        this.currentAsks = asks;
        return;
      }
      this.chain = null;
      this.currentAsks = [];
    },

    async runChainStep() {
      if (!this.chain || !this.chain[this.currentStepIndex]) return;
      const bp = this.chain[this.currentStepIndex];
      const userParams = {};

      this.currentAsks.forEach(param => {
        const values = this.formValues[bp.ID][param.Name];
        if (values === undefined || values === null) return;
        userParams[param.paramKey] = {
          type: param.Type,
          multiple: !!param.Multiple,
          value: param.Type === 'user' ? values : (param.Multiple ? values : { value: values })
        };
      });

      await this.runChainBp(bp, userParams);
      this.currentAsks = [];
      this.currentStepIndex++;
      await this.processChainStep();
    },

    async runChainBp(bp, userParams) {
      this.loader = true;
      try {
        const merged = Object.assign({}, bp.presets || {}, userParams);
        const response = await startBp(
          window.memberId,
          { [bp.ID]: merged },
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
      } finally {
        this.loader = false;
      }
    },

    async runCurrentBp() {
      this.loader = true;
      const bp = this.paramResult[this.currentBpIndex];
      const preparedParams = {};
      
      bp.PARAMETERS.forEach(param => {
        const values = this.formValues[bp.ID][param.Name];
        if (values === undefined || values === null) return;
        preparedParams[param.paramKey] = {
          type: param.Type,
          multiple: !!param.Multiple,
          value: param.Type === 'user' ? values : (param.Multiple ? values : { value: values })
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
      }
      this.loader = false;
    },

    addField(bpId, paramName) {
      this.formValues[bpId][paramName].push('');
    },
  
    removeField(bpId, paramName, index) {
      if (index === 0) return;
      this.formValues[bpId][paramName].splice(index, 1);
    },

    resizeBx() {
      if (!window.BX24) return;
      
      const app = document.querySelector('#app');
      if (!app) return;
      
      // Используем setTimeout чтобы дать DOM обновиться
      setTimeout(() => {
        const height = app.scrollHeight + 30;
        const size = BX24.getScrollSize();
        BX24.resizeWindow(size.scrollWidth, height);
      }, 20);
    },
  },
  
  computed: {
    isCurrentBpValid() {
      if (!this.paramResult) return false;

      const bp = this.paramResult[this.currentBpIndex];

      for (const p of bp.PARAMETERS) {
        if (p.Required) {
          const values = this.formValues[bp.ID][p.Name];

          if (p.Multiple) {
            if (!Array.isArray(values) || values.length === 0) return false;
            for (const v of values) {
              if (p.Type === 'select' || p.Type === 'user' || p.Type === 'bool') {
                if (!v || !v.value) return false;
              } else {
                if (!v || v === '') return false;
              }
            }
          } else {
            if (p.Type === 'select' || p.Type === 'user' || p.Type === 'bool') {
              if (!values || !values.value) return false;
            } else {
              if (!Array.isArray(values) || !values[0]) return false;
            }
          }
        }
      }
      return true;
    },

    currentStep() {
      return this.chain ? this.chain[this.currentStepIndex] : null;
    },

    chainTotal() {
      return this.chain ? this.chain.length : 0;
    },

    isChainStepValid() {
      if (!this.currentStep || !this.currentAsks.length) return false;
      const bp = this.currentStep;

      for (const p of this.currentAsks) {
        if (!p.Required) continue;
        const values = this.formValues[bp.ID][p.Name];

        if (p.Multiple) {
          if (!Array.isArray(values) || values.length === 0) return false;
          for (const v of values) {
            if (p.Type === 'select' || p.Type === 'user' || p.Type === 'bool') {
              if (!v || !v.value) return false;
            } else {
              if (!v || v === '') return false;
            }
          }
        } else {
          if (p.Type === 'select' || p.Type === 'user' || p.Type === 'bool') {
            if (!values || !values.value) return false;
          } else {
            if (!Array.isArray(values) || !values[0]) return false;
          }
        }
      }
      return true;
    }
  },
  
  mounted() {
    this.resizeBx();
    
    // Отслеживаем клики по мультиселектам
    document.addEventListener('click', (event) => {
      const multiselect = event.target.closest('.multiselect');
      if (multiselect) {
        // При клике на мультиселект - ждём открытия списка
        setTimeout(() => this.resizeBx(), 50);
        setTimeout(() => this.resizeBx(), 200);
      }
    });
    
    // Отслеживаем изменение размера контента
    const observer = new MutationObserver(() => {
      this.resizeBx();
    });
    
    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'style']
    });
  },
  
  updated() {
    this.resizeBx();
  },
});