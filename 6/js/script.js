import { getAllButtons, getMoreButtons, getTemplate, saveBtnSettings, getButtonData, deleteButton} from "../js/api.js";


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
    

    };
  },

  methods: {
  


async selectButton(button) {
    this.newButton = false; // 🔥

  this.activeButtonId = button.ID;

        let response = await getButtonData(window.memberId, button);

  // 🔥 кладём данные из PHP
  this.current_button = JSON.parse(JSON.stringify(response.result));

  console.log('Активная кнопка:', this.current_button);
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

    // async saveSettings() {
    //         this.loader = true

    //   let response = await saveBtnSettings(window.memberId, this.current_button, this.activeButtonId);
    //   this.loader = false

    // },


async saveSettings() {
  this.loader = true;

  let response = await saveBtnSettings(
    window.memberId,
    this.current_button,
    this.activeButtonId
  );

  this.loader = false;

  // 🟢 если это новая кнопка
  if (this.newButton && response.result) {
    this.newButton = false;

    // 🔥 флаг = true → выбрать последнюю
    await this.getButtons(true);
  }
},

//  async delButton() {
//             this.loader = true

//       let response = await deleteButton(window.memberId, this.activeButtonId);
//       this.loader = false

//     },
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
    this.current_button = response.result;

    },

async getButtons(selectLast = false) {
  let response = await getAllButtons(window.memberId);

  if (response.result.total === 0) {
    await this.getTemp();
    return;
  }

  this.portalButtons = response.result.result;

  let buttonToSelect = null;

  if (selectLast) {
    // ✅ если передан флаг — берём последнюю
    buttonToSelect = this.portalButtons[this.portalButtons.length - 1];
  } else {
    // ❌ стандартно — первая
    buttonToSelect = this.portalButtons[0];
  }

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
  
  async mounted() {
      this.loader = true
      await this.getButtons()
      this.loader = false

  }

});
