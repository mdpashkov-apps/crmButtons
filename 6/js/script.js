import { getAllButtons, createButton, getMoreButtons} from "../js/api.js";


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
      
      loader: false,
      portalButtons: [],
      morebuttons: [],
        showMore: false,
        loadingMore: false,

    };
  },

  methods: {

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

    async getButtons() {
      let response = await getAllButtons(window.memberId);
      this.portalButtons = response.result.result

      console.log(this.portalButtons)
    },


 async getMorButtons() {
      let response = await getMoreButtons(window.memberId);
      this.morebuttons = response.result

    },


    async createBtn() {
      let response = await createButton(window.memberId);

    },
  
  
  },
  
  async mounted() {
      this.loader = true
      await this.getButtons()
      this.loader = false
  }

});
