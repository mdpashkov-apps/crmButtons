import { getAllButtons, getTemplate, getMoreButtons, saveBtnSettings, deleteButton, createButtonInCrm, deleteButtonInCrm, getButtonData, getAllEntitys, getBPforEntity, getDocumentsforEntity, getAllLists, getListFields, getEntityFieldsForList, getCrmFieldsLink,} from "../js/api.js";


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
          originalButtonStyles: null, // 👈 снимок стилей
crmLinkWarning: false,
    enteredLinkWarning: false,
      newButton: false,
      loader: false,
      portalButtons: [],
      morebuttons: [],
      showMore: false,
      loadingMore: false,
      // current_button: {},

  current_button: {
      buttonColor_FIELDS: '#000000',
      textColor_FIELDS: '#ffffff',
      buttonBorderColor_FIELDS: '#000000',
      
    },

      activeButtonId: null, // 👈 текущая активная кнопка
      allEntitys: [],
                  allCrmFieldsLink: [],

      allBizProc: [],
            allDocuments: [],
            allLists: [],
currentListFields: [],
    //  listFields:[],
entFields:[],
    flagsButtonBizproc: false,
        flagsButtonDocument: false,
        flagsList: false,
flagsButtonEnteredLink: false,
flagsButtonCrmLink: false,

    accordion_0: false,
accordion_1: false,
accordion_2: false,
accordion_3: false,
accordion_4: false,

    };
  },

  methods: {



async getButtons(selectLast = false) {
  
  let response = await getAllButtons(window.memberId);

  if (response.result.total === 0) {
    this.portalButtons = []; // 🔥 КРИТИЧНО
    await this.getTemp();
    this.activeButtonId = null;
    this.newButton = true;
    return;
  }

  this.portalButtons = response.result.result;

  let buttonToSelect = selectLast
    ? this.portalButtons[this.portalButtons.length - 1]
    : this.portalButtons[0];

  this.selectButton(buttonToSelect);
},


 async getTemp() {
      let response = await getTemplate(window.memberId,);
      // console.log(response.result)

    this.current_button = response.result;
      // console.log(this.current_button)

    },



 async getMorButtons() {
      let response = await getMoreButtons(window.memberId);
      this.morebuttons = response.result

    },



 async showMoreButtons() {
        this.showMore = true;

        // грузим только один раз
        if (this.morebuttons.length === 0 && !this.loadingMore) {
            this.loadingMore = true;
            try {
                let response = await getMoreButtons(window.memberId);
                this.morebuttons = response.result || [];
            } finally {
                this.loadingMore = false;
            }
        }
    },

    hideMoreButtons() {
        this.showMore = false;
    },


async saveSettings() {
  if (!this.checkEntitySelected()) {
    return;
  }
  this.loader = true;

  let response = await saveBtnSettings(
    window.memberId,
    this.current_button,
    this.activeButtonId
  );
if (response.result) {
  this.originalButtonStyles = {
    buttonColor_FIELDS: this.current_button.buttonColor_FIELDS,
    color_text: this.current_button.color_text,
    buttonRadius_FIELDS: this.current_button.buttonRadius_FIELDS,
    buttonBorderColor_FIELDS: this.current_button.buttonBorderColor_FIELDS,
    buttonBorderWidth_FIELDS: this.current_button.buttonBorderWidth_FIELDS,
    buttonBorder_FIELDS: this.current_button.buttonBorder_FIELDS,
  }
}

  this.loader = false;

  // 🟢 первая кнопка или новая
  if (this.newButton && response.result) {
    this.newButton = false;

    // 🔥 ОБЯЗАТЕЛЬНО перезагружаем список
    await this.getButtons(true);
  }
},

async delButton() {
  if (!this.activeButtonId) return;

  this.loader = true;
  await deleteButton(window.memberId, this.activeButtonId);
  this.loader = false;

  // ❌ удаляем из Vue
  this.portalButtons = this.portalButtons.filter(
    b => b.ID !== this.activeButtonId
  );

  this.activeButtonId = null;

  // 👉 если остались кнопки — активируем первую
  if (this.portalButtons.length > 0) {
    this.selectButton(this.portalButtons[0]);
  } else {
    await this.getTemp(); // шаблон
    this.newButton = true;
  }
},

async createBtnCrm(domen) {
 
    this.loader = true;

   await createButtonInCrm(window.memberId, this.activeButtonId, domen);
      // await this.getButtons(true)

const response = await getButtonData(
    window.memberId,
    { ID: this.activeButtonId }
  );

  this.current_button = JSON.parse(JSON.stringify(response.result));
  this.normalizeBooleans();


  
    this.loader = false;
  
},






async deleteBtnCrm(domen) {
 
    this.loader = true;

    await deleteButtonInCrm(window.memberId, this.activeButtonId, domen);

      // await this.getButtons(true)
const response = await getButtonData(
    window.memberId,
    { ID: this.activeButtonId }
  );

  this.current_button = JSON.parse(JSON.stringify(response.result));
  this.normalizeBooleans();
  
    this.loader = false;
  
},
normalizeBooleans() {
    const boolFields = [
      'buttonBorder_FIELDS',
      'usingTheIcon_FIELDS',
      'buttonInCRM_FIELDS',
    ]

    boolFields.forEach(field => {
      if (field in this.current_button) {
        this.current_button[field] =
          this.current_button[field] === true ||
          this.current_button[field] === 1 ||
          this.current_button[field] === '1' ||
          this.current_button[field] === 'true'
      }
    })
  },
async selectButton(button) {
  
    this.newButton = false; // 🔥

  this.activeButtonId = button.ID;

        let response = await getButtonData(window.memberId, button);

  // 🔥 кладём данные из PHP
  this.current_button = JSON.parse(JSON.stringify(response.result));
  this.normalizeBooleans()

  await this.getEntFields()   // ⬅ загрузили options

this.normalizeFieldsTable()

  this.originalButtonStyles = {
      buttonColor_FIELDS: this.current_button.buttonColor_FIELDS,
      color_text: this.current_button.color_text,
      buttonRadius_FIELDS: this.current_button.buttonRadius_FIELDS,
      buttonBorder_FIELDS: this.current_button.buttonBorder_FIELDS,
      buttonBorderWidth_FIELDS: this.current_button.buttonBorderWidth_FIELDS,
      buttonBorderColor_FIELDS: this.current_button.buttonBorderColor_FIELDS,
    }


 if (this.current_button.listsValue_FIELDS) {
    await this.onListChange(true)
  }
  if (!Array.isArray(this.current_button.buttonActionsId_FIELDS)) {
  this.$set(this.current_button, 'buttonActionsId_FIELDS', [])
}
},


async getEntitys() {
    this.loader = true;

      let response = await getAllEntitys(window.memberId,);
     

    this.allEntitys = response.result;
      this.loader = false;


    },
async getBP() {
    this.loader = true;

      let response = await getBPforEntity(window.memberId, this.current_button);
     

    this.allBizProc = response.result;
      this.loader = false;


    },


async getDocs() {
    this.loader = true;

      let response = await getDocumentsforEntity(window.memberId, this.current_button);
     

    this.allDocuments = response.result;
      this.loader = false;


    },

async getLists() {
    this.loader = true;

      let response = await getAllLists(window.memberId, this.current_button);
     

    this.allLists = response.result;
      this.loader = false;


    },

async onListChange(silent = false) {
  if (!silent) this.loader = true

  let response = await getListFields(window.memberId, this.current_button)
  let freshFields = response.result

  // если есть сохранённые соответствия
  if (Array.isArray(this.current_button.fieldsTable_FIELDS)) {

    freshFields.forEach(f => {
      let saved = this.current_button.fieldsTable_FIELDS.find(
        s => s.value === f.value
      )

      if (saved) {
        f.entField = saved.entField
      }
    })
  }

  this.current_button.fieldsTable_FIELDS = freshFields

  if (!silent) this.loader = false
},

async getEntFields() {
    this.loader = true;

      let response = await getEntityFieldsForList(window.memberId, this.current_button);
     

    this.entFields = response.result;
      this.loader = false;


    },
async getCrmLinks() {
    this.loader = true;

      let response = await getCrmFieldsLink(window.memberId, this.current_button);
     

    this.allCrmFieldsLink = response.result;
      this.loader = false;


    },






  toggleEnteredLink(e) {
    this.enteredLinkWarning = false

    if (e.target.checked) {
      if (this.current_button.buttonActionsId_FIELDS.includes(4)) {
        this.enteredLinkWarning = true
        return
      }
      this.current_button.buttonActionsId_FIELDS.push(3)
    } else {
      this.current_button.buttonActionsId_FIELDS =
        this.current_button.buttonActionsId_FIELDS.filter(v => v !== 3)
    }
  },

  toggleCrmLink(e) {
    this.crmLinkWarning = false

    if (e.target.checked) {
      if (this.current_button.buttonActionsId_FIELDS.includes(3)) {
        this.crmLinkWarning = true
        return
      }
      this.current_button.buttonActionsId_FIELDS.push(4)
    } else {
      this.current_button.buttonActionsId_FIELDS =
        this.current_button.buttonActionsId_FIELDS.filter(v => v !== 4)
    }
  },
  bpSettings() {
      if (!this.checkEntitySelected()) return;

    this.accordion_0 = !this.accordion_0
    this.flagsButtonBizproc = true
  },

  
documentSettings() {
        if (!this.checkEntitySelected()) return;

    this.accordion_1 = !this.accordion_1
    this.flagsButtonDocument = true
  },
listSettings() {
        if (!this.checkEntitySelected()) return;

    this.accordion_2 = !this.accordion_2
    this.flagsList = true
  },
followEnteredLink() {
        if (!this.checkEntitySelected()) return;

    this.accordion_3 = !this.accordion_3
    this.flagsButtonEnteredLink = true
  },
followCrmLink() {
        if (!this.checkEntitySelected()) return;

    this.accordion_4 = !this.accordion_4
    this.flagsButtonCrmLink = true
  },


normalizeFieldsTable() {
  const ft = this.current_button.fieldsTable_FIELDS
  if (!Array.isArray(ft) || ft.length !== 2) return

  const [crmFields, listFields] = ft

  this.current_button.fieldsTable_FIELDS = listFields.map((listCode, i) => {
    let entField = null

    if (crmFields[i] && crmFields[i] !== 'null') {
      entField = this.entFields.find(
        f => f.value === crmFields[i]
      ) || null
    }

    return {
      value: listCode,
      name: listCode,
      entField
    }
  })
},
async resetStylesButton() {
  if (!this.originalButtonStyles) return

  Object.keys(this.originalButtonStyles).forEach(key => {
    this.$set(this.current_button, key, this.originalButtonStyles[key])
  })
},


 checkEntitySelected() {
    if (!this.current_button?.entitySelection_FIELDS) {
      alert('Не выбрана сущность');
      return false;
    }
    return true;
  },









   onEntityChange() {
    // БП
    this.current_button.businessProcessesValue_FIELDS = [];
    this.allBizProc = [];

    // Документы
    this.current_button.documentTemplatesValue_FIELDS = [];
    this.allDocuments = [];

    // Списки
    this.current_button.listsValue_FIELDS = null;
    this.current_button.fieldsTable_FIELDS = [];
    this.allLists = [];
    this.entFields = [];

    // CRM-ссылка
    this.current_button.crmLinkFields_FIELDS = null;
    this.allCrmFieldsLink = [];

    // ❗ НЕ ТРОГАЕМ:
    // this.current_button.link_FIELDS

    // Закрываем accordion’ы
    this.accordion_0 = false;
    this.accordion_1 = false;
    this.accordion_2 = false;
    this.accordion_4 = false;

    // Сбрасываем флаги
    this.flagsButtonBizproc = false;
    this.flagsButtonDocument = false;
    this.flagsList = false;
    this.flagsButtonCrmLink = false;
  },




 SetStandardStyles() {
     this.current_button.textColor_FIELDS = "#ffffff";
        this.current_button.buttonColor_FIELDS = "#3bc8f5";
  this.current_button.buttonRadius_FIELDS = "0";
  this.current_button.buttonBorderWidth_FIELDS = 0;
  this.current_button.buttonBorderColor_FIELDS = 0;
  this.current_button.buttonBorder_FIELDS = false;
    },
 



























    


    



 



    async createBtn() {
      this.newButton = true
  let response = await getTemplate(window.memberId);
  this.current_button = response.result;
  this.activeButtonId = null; // 🔥 ВАЖНО


 this.originalButtonStyles = {
    buttonColor_FIELDS: this.current_button.buttonColor_FIELDS,
    color_text: this.current_button.color_text,
    buttonRadius_FIELDS: this.current_button.buttonRadius_FIELDS,
    buttonBorder_FIELDS: this.current_button.buttonBorder_FIELDS,
    buttonBorderWidth_FIELDS: this.current_button.buttonBorderWidth_FIELDS,
    buttonBorderColor_FIELDS: this.current_button.buttonBorderColor_FIELDS,
  }

    },
  
  
  },
  watch: {
  'current_button.buttonActionsId_FIELDS'(val) {
  }
},

  computed: {
  hasNewButton() {
    return this.newButton ? 1 : 0
  },
  totalButtonsCount() {
    return this.portalButtons.length + this.hasNewButton
  },



 
  

},
  async mounted() {
      this.loader = true
      await this.getButtons()
      this.loader = false

  }

});
