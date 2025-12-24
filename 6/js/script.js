import { getAllButtons, getMoreButtons, getTemplate, saveBtnSettings, getButtonData, deleteButton, getAllEntitys,createButtonInCrm, getBPforEntity, getDocumentsforEntity,getEntityFields, getListsforEntity,getListFields} from "../js/api.js";


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
      newButton: false,
      loader: false,
      portalButtons: [],
      morebuttons: [],
      showMore: false,
      loadingMore: false,
      current_button: {},
      activeButtonId: null, // 👈 текущая активная кнопка
      allEntitys: [],
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
    accordion_0: false,
accordion_1: false,
accordion_2: false,
accordion_3: false,
    };
  },

  methods: {
  
  bpSettings() {
    this.accordion_0 = !this.accordion_0
    this.flagsButtonBizproc = true
  },
documentSettings() {
    this.accordion_1 = !this.accordion_1
    this.flagsButtonDocument = true
  },
listSettings() {
    this.accordion_2 = !this.accordion_2
    this.flagsList = true
  },
followEnteredLink() {
    this.accordion_3 = !this.accordion_3
    this.flagsButtonEnteredLink = true
  },


async selectButton(button) {
  
    this.newButton = false; // 🔥

  this.activeButtonId = button.ID;

        let response = await getButtonData(window.memberId, button);

  // 🔥 кладём данные из PHP
  this.current_button = JSON.parse(JSON.stringify(response.result));
 if (this.current_button.listsValue_FIELDS) {
    await this.onListChange(true)
  }
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
  this.loader = true;

  let response = await saveBtnSettings(
    window.memberId,
    this.current_button,
    this.activeButtonId
  );

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

 async getTemp() {
      let response = await getTemplate(window.memberId,);
      // console.log(response.result)

    this.current_button = response.result;
      // console.log(this.current_button)

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


async createBtnCrm() {
    this.loader = true;

      let response = await createButtonInCrm(window.memberId,);
     

      this.loader = false;


    },


async getEntFields() {
    this.loader = true;

      let response = await getEntityFields(window.memberId, this.current_button);
     

    this.entFields = response.result;
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

      let response = await getListsforEntity(window.memberId, this.current_button);
     

    this.allLists = response.result;
      this.loader = false;


    },


async onListChange(silent = false) {
  if (!silent) {
    this.loader = true
  }
      let response = await getListFields(window.memberId, this.current_button);
     

    this.current_button.fieldsTable_FIELDS = response.result;
  if (!silent) {
    this.loader = false
  }

    },
    


    
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

 async getMorButtons() {
      let response = await getMoreButtons(window.memberId);
      this.morebuttons = response.result

    },


    async createBtn() {
      this.newButton = true
  let response = await getTemplate(window.memberId);
  this.current_button = response.result;
  this.activeButtonId = null; // 🔥 ВАЖНО
    },
  
  
  },
  computed: {
  hasNewButton() {
    return this.newButton ? 1 : 0
  },
  totalButtonsCount() {
    return this.portalButtons.length + this.hasNewButton
  },

buttonColorModel: {
    get() {
      return this.current_button?.buttonColor_FIELDS || '#354873'
    },
    set(val) {
      this.$set(this.current_button, 'buttonColor_FIELDS', val)
    }
  },

  textColorModel: {
    get() {
      return this.current_button?.color_text || '#ffffff'
    },
    set(val) {
      this.$set(this.current_button, 'color_text', val)
    }
  }

},
  async mounted() {
      this.loader = true
      await this.getButtons()
      this.loader = false

  }

});
