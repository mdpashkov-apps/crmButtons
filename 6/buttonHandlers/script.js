import { OpenCrmLink,startBp,createDocument} from "../buttonHandlers/api.js";

Vue.component("modal", {
  template: "#modal-template",
});
var app = new Vue({
  el: "#app",
  components: {
  },
  data() {
    return {

      buttonActionsId_FIELDS: [],
      loader: false,
    

    };
  },

  methods: {
  async action0() {
        this.loader = true;

          let response = await startBp(window.memberId,window.crmActions);

    this.loader = false;


  },



  async action1() {
        this.loader = true;

    console.log('Запуск действия 1');

          let response = await createDocument(window.memberId,window.crmActions);
    this.loader = false;


  },








  async action2() {
    console.log('Запуск действия 2');
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
    console.log('Запуск действия 4');

      let response = await OpenCrmLink(window.memberId,window.crmActions.crmLinkFields_FIELDS);
  let crmLink = response.result;

 if (!/^https?:\/\//i.test(crmLink)) {
    crmLink = 'https://' + crmLink;
  }
  window.open(crmLink, '_blank');

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

}

});
