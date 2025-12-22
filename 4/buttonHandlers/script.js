import {
  getElement,
  launchBP,
  createDocument,
  addListElement,
  logInTable,
  checkBPParametrs,
} from "../buttonHandlers/requests.js";

Vue.component("modal", {
  template: "#modal-template",
});
var app = new Vue({
  el: "#app",
  components: { Multiselect: window.VueMultiselect.default },
  data() {
    return {
      buttonFields: {},
      styleObject: {},
      options: {},
      businessProcessWithParameters: [],
      entity: "",
      loader: false,
    };
  },
  methods: {
    async parameterValidation() {
      this.loader = true;
      let error = "";
      Object.entries(
        this.businessProcessWithParameters[0]["PARAMETERS"],
      ).forEach((elem) => {
        let typeParametr = elem[1].Type;
        let value = elem[1].value;
        let multiple = elem[1].Multiple;
        let required = elem[1].Required;
        let name = elem[1].Name;
        if (["int", "double"].includes(typeParametr)) {
          if (multiple == "0") {
            if (required && [""].includes(value)) {
              error +=
                `Поле '${name}' должно быть обязательно заполнено` + "\n";
            }
          } else {
            if (required) {
              if (!value.some((item) => item !== "")) {
                error +=
                  `Поле '${name}' должно быть обязательно заполнено` + "\n";
              }
            }
          }
        } else if (
          typeParametr == "bool" ||
          typeParametr == "email" ||
          typeParametr == "phone" ||
          typeParametr == "web"
        ) {
        } else {
          if (multiple == "0") {
            if (required && value == "") {
              error +=
                `Поле '${name}' должно быть обязательно заполнено` + "\n";
            }
          } else {
            if (required) {
              if (!value.some((item) => item !== "")) {
                error +=
                  `Поле '${name}' должно быть обязательно заполнено` + "\n";
              }
            }
          }
        }
      });
      if (error != "") {
        alert(error);
      } else {
        await launchBP(
          JSON.stringify([this.businessProcessWithParameters[0].ID]),
          window.idEntityCRM,
          this.entity,
          window.memberId,
          this.businessProcessWithParameters[0]["PARAMETERS"],
        ).then(function (value) {});
        this.businessProcessWithParameters.shift();
      }
      this.loader = false;
    },
    delteFields(propID, index) {
      if (
        this.businessProcessWithParameters[0]["PARAMETERS"][propID].value
          .length != 1
      ) {
        this.businessProcessWithParameters[0]["PARAMETERS"][propID].value =
          this.businessProcessWithParameters[0]["PARAMETERS"][
            propID
          ].value.filter((num, key) => key != index);
      }
    },
    addFields(propID) {
      if (
        ["string", "text"].includes(
          this.businessProcessWithParameters[0]["PARAMETERS"][propID].Type,
        )
      ) {
        this.businessProcessWithParameters[0]["PARAMETERS"][propID].value.push(
          "",
        );
      }
      if (
        ["email", "phone"].includes(
          this.businessProcessWithParameters[0]["PARAMETERS"][propID].Type,
        )
      ) {
        this.businessProcessWithParameters[0]["PARAMETERS"][propID].value.push(
          "",
        );
        this.businessProcessWithParameters[0]["PARAMETERS"][
          propID
        ].additionalField.push({ name: "Рабочий", value: "WORK" });
      }
      if (
        ["datetime"].includes(
          this.businessProcessWithParameters[0]["PARAMETERS"][propID].Type,
        )
      ) {
        this.businessProcessWithParameters[0]["PARAMETERS"][propID].value.push(
          "",
        );
        this.businessProcessWithParameters[0]["PARAMETERS"][
          propID
        ].additionalField.push({ name: "Время сервера", value: "" });
      }
      if (
        ["int", "double"].includes(
          this.businessProcessWithParameters[0]["PARAMETERS"][propID].Type,
        )
      ) {
        this.businessProcessWithParameters[0]["PARAMETERS"][propID].value.push(
          0,
        );
      }
      if (
        ["web"].includes(
          this.businessProcessWithParameters[0]["PARAMETERS"][propID].Type,
        )
      ) {
        this.businessProcessWithParameters[0]["PARAMETERS"][propID].value.push(
          "",
        );
        this.businessProcessWithParameters[0]["PARAMETERS"][
          propID
        ].additionalField.push({ name: "Корпоративный", value: "WORK" });
      }
      if (
        ["bool"].includes(
          this.businessProcessWithParameters[0]["PARAMETERS"][propID].Type,
        )
      ) {
        this.businessProcessWithParameters[0]["PARAMETERS"][propID].value.push(
          false,
        );
      }
    },
    async performAnAction() {
      this.loader = true;
      await logInTable(
        this.buttonFields,
        window.userId,
        window.domain,
        window.idEntityCRM,
        window.memberId,
      ).then(function (value) {});
      let buttonActions = JSON.parse(this.buttonFields.buttonActionsId_FIELDS);
      this.entity = this.buttonFields.entitySelection_FIELDS;
      for (const element of buttonActions) {
        switch (element) {
          case "0":
            let idBP, resultLaunchBP, businessProcessWithoutParameters;
            idBP = this.buttonFields.businessProcessesValue_FIELDS;
            let getBP = await checkBPParametrs(
              idBP,
              window.memberId,
              this.options,
            );
            businessProcessWithoutParameters =
              getBP.businessProcessWithoutParameters;
            this.businessProcessWithParameters =
              getBP.businessProcessWithParameters;
            if (
              businessProcessWithoutParameters.length != 0 &&
              businessProcessWithoutParameters &&
              businessProcessWithoutParameters !== "undefined"
            ) {
              await launchBP(
                JSON.stringify(businessProcessWithoutParameters),
                window.idEntityCRM,
                this.entity,
                window.memberId,
                "",
              ).then(function (value) {
                resultLaunchBP = value;
              });
            }
            break;
          case "1":
            let idDocument, resultCreateDocument;
            idDocument = this.buttonFields.documentTemplatesValue_FIELDS;
            await createDocument(
              idDocument,
              window.idEntityCRM,
              this.entity,
              window.memberId,
            ).then(function (value) {
              resultCreateDocument = value;
            });
            break;
          case "2":
            let idLists, resultAddListElement, fieldsList;
            idLists = JSON.parse(this.buttonFields.listsValue_FIELDS);
            fieldsList = JSON.parse(this.buttonFields.fieldsTable_FIELDS);
            if (
              idLists !== null &&
              fieldsList &&
              idLists &&
              fieldsList !== "undefined" &&
              idLists !== "undefined"
            ) {
              await addListElement(
                idLists,
                window.idEntityCRM,
                this.entity,
                fieldsList,
                window.memberId,
              ).then(function (value) {
                resultAddListElement = value;
              });
            }
            break;
          case "3":
            let link = this.buttonFields.link_FIELDS;
            window.open(link, "_blank");
            break;
        }
      }
      this.loader = false;
    },
    resizeWindow() {
      let getSize = BX24.getScrollSize();
      let height = document.querySelector("#app").clientHeight + 30;
      BX24.resizeWindow(getSize.scrollWidth, height);
    },
    async intialData() {
      if (window.memberId != "") {
        let resGetElement = await getElement(window.idEntity, window.memberId);
        this.buttonFields = resGetElement.data;
        this.options = resGetElement.options;
        this.styleObject = {
          backgroundColor: this.buttonFields.buttonColor_FIELDS,
          color: this.buttonFields.textColor_FIELDS,
          borderRadius: this.buttonFields.buttonRadius_FIELDS + "px",
        };
        if (this.buttonFields.buttonBorder_FIELDS == "true") {
          this.styleObject.border =
            this.buttonFields.buttonBorderWidth_FIELDS +
            "px solid " +
            this.buttonFields.buttonBorderColor_FIELDS;
        }
      }
    },
  },
  updated: function () {
    this.resizeWindow();
  },
  async created() {
    await this.intialData();
  },
});

