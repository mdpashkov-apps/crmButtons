import { OpenCrmLink,startBp,createDocument,createListElement, getBpParams} from "../buttonHandlers/api.js";

Vue.component("modal", {
  template: "#modal-template",
});
var app = new Vue({
  el: "#app",
  components: {
          Multiselect: window.VueMultiselect.default

  },
  data() {
    return {

      buttonActionsId_FIELDS: [],
      loader: false,
        paramResult: null, // ← сюда кладём результат getParam
    formValues: {}, 
    currentBpIndex: 0, // 👈 текущий БП
    allUsers: [], // 👈 ВОТ ОНО
        selectedUser: {}, // выбранный пользователь в муьтиселекте


    };
  },

  methods: {

removeField(bpId, paramName, index) {
  if (index === 0) return; // первый нельзя удалять
  this.formValues[bpId][paramName].splice(index, 1);
},




async action0() {
  this.loader = true;

  const response = await getBpParams(
    window.memberId,
    window.crmActions,
    window.entityData
  );

  this.paramResult = response.result;

 if (response.allUserFio) {

    this.allUsers = response.allUserFio;

  }
  // ИНИЦИАЛИЗАЦИЯ ФОРМЫ
 this.paramResult.forEach(bp => {
  this.$set(this.formValues, bp.ID, {});

  bp.PARAMETERS.forEach(p => {
   let values;

if (p.Type === 'user') {
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


  async action1() {
        this.loader = true;


          let response = await createDocument(window.memberId,window.crmActions, window.entityData);
    this.loader = false;


  },




async runCurrentBp() {
  const bp = this.paramResult[this.currentBpIndex];

  const preparedParams = {};

  bp.PARAMETERS.forEach(param => {
    const values = this.formValues[bp.ID][param.Name];

    if (param.Multiple) {
      preparedParams[param.paramKey] = values;
    } else {
      preparedParams[param.paramKey] = values[0];
    }
  });

  const bpParams = {
    [bp.ID]: preparedParams
  };

  let response = await startBp(
    window.memberId,
    bpParams,
    window.entityData
  );

  if (this.currentBpIndex < this.paramResult.length - 1) {
    this.currentBpIndex++;
  } else {
    this.paramResult = null;
  }
},



  addField(bpId, paramName) {
    this.formValues[bpId][paramName].push('');
  },


  async action2() {
          let response = await createListElement(window.memberId,window.crmActions, window.entityData);

  },


// перейти по ссылке произвольной
async action3() {
  let arbitraryLink = window.crmActions.link_FIELDS;

   if (!/^https?:\/\//i.test(arbitraryLink)) {
    arbitraryLink = 'https://' + arbitraryLink;
  }
  window.open(arbitraryLink, '_blank');
},

  async action4() {

      let response = await OpenCrmLink(window.memberId,window.crmActions, window.entityData);
  let crmLink = response.result;

 if (!/^https?:\/\//i.test(crmLink)) {
    crmLink = 'https://' + crmLink;
  }
  window.open(crmLink, '_blank');

  },

resizeBx() {
  if (!window.BX24) return;

  const app = document.querySelector('#app');
  if (!app) return;

  const size = BX24.getScrollSize();
  const height = app.clientHeight + 30;

  BX24.resizeWindow(size.scrollWidth, height);
},

  async runActions() {
  let raw = window.crmActions.buttonActionsId_FIELDS;

  // 🔥 ВОТ ЭТА СТРОКА РЕШАЕТ ВСЁ
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
}

},
computed: {
  isCurrentBpValid() {
    if (!this.paramResult) return false;

    const bp = this.paramResult[this.currentBpIndex];

    for (const p of bp.PARAMETERS) {
      if (p.Required) {
        const values = this.formValues[bp.ID][p.Name];
        if (!values || values.some(v => !v || v === '')) {
          return false;
        }
      }
    }
    return true;
  }
},
mounted() {
  this.resizeBx();
},

updated() {
  this.resizeBx();
},


});
