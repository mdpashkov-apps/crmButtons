import {getAllUsers,addUsersInChat, deleteChatBot} from "../js/api.js";


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
      loader: false, // лодер
    allUsers: [],
    selectedUsers: []
    };
  },

  methods: {
   


    async getUsers() {
      let response = await getAllUsers(window.memberId);
      this.allUsers = response.result

    },
async addInChat() {
      let response = await addUsersInChat(window.memberId, this.selectedUsers);

    },
async delChatBot() {
      let response = await deleteChatBot(window.memberId);

    },

  
  
  },


  async mounted() {
      this.loader = true
      await this.getUsers()
      this.loader = false

  }

});
