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
        // Изменяем переменные для кнопки
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
      let response = await OpenCrmLink(window.memberId, window.crmActions, window.entityData);
      let crmLink = response.result;
      if (!/^https?:\/\//i.test(crmLink)) {
        crmLink = 'https://' + crmLink;
      }
      window.open(crmLink, '_blank');
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