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
      allUsers: [], // все юзеры портала
      selectedUsers: [] // выбранные юзеры портала
    };
  },
  methods: {
    // получаем всех юзеров
    async getUsers() {
      let response = await getAllUsers(window.memberId);
      this.allUsers = response.result
    },
    // ф-я добавления выбранных юзеров в чат
    async addInChat() {
      this.loader = true
      let response = await addUsersInChat(window.memberId, this.selectedUsers);
      this.loader = false
    },
    // ф-я удаления чатбота с портала
    async delChatBot() {
      this.loader = true
      let response = await deleteChatBot(window.memberId);
      this.loader = false
    },
  },
  async mounted() {
      this.loader = true
      await this.getUsers()
      this.loader = false
  }
});
