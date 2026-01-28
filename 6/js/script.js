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
      loader: false, // лодер
      originalButtonStyles: null, // стили кнопки до сохранения (для отмены стилей)
      newButton: false, // флаг, новая ли кнопка или существующая
      portalButtons: [], // все кнопки (берутся из entity bitrix)
      morebuttons: [], // скрытые кнопки из еще
      showMore: false, // флаг показа большего кол-ва кнопок
      loadingMore: false,
      current_button: { // текущая кнопка (некоторые стили задаем заранее, после получения данных по кнопке меняем)
        buttonColor_FIELDS: '#000000',
        textColor_FIELDS: '#ffffff',
        buttonBorderColor_FIELDS: '#000000',
      },
      activeButtonId: null, // текущая активная кнопка
      allEntitys: [], // все сущности портала для мультиселекта
      allBizProc: [], // все бп выбранной сущности для мультиселекта
      allDocuments: [], // все документы выбранной сущности для мультиселекта
      allLists: [], // все списки выбранной сущности для мультиселекта
      allCrmFieldsLink: [], // все поля типа ссылка выбранной сущности для мультиселекта
      entFields:[], // все поля сущности для сопоставления со списками
      flagsButtonBizproc: false, // флаг открытия БП
      flagsButtonDocument: false, // флаг открытия документов
      flagsList: false, // флаг открытия списков
      flagsButtonEnteredLink: false, // флаг произвольной ссылки
      flagsButtonCrmLink: false, // флаг CRM-ссылки
      accordion_0: false, // accordion БП
      accordion_1: false, // accordion документов
      accordion_2: false, // accordion списков
      accordion_3: false, // accordion произвольной ссылки
      accordion_4: false, // accordion CRM-ссылки
    };
  },

  methods: {
    // получение всех кнопок портала
    async getButtons(selectLast = false) {
      const response = await getAllButtons(window.memberId);

    // проверяем, что с сервера пришёл массив кнопок
      const buttons = Array.isArray(response?.result?.result)
        ? response.result.result
        : [];
      this.portalButtons = buttons;

      // если кнопок нет — включаем режим создания новой
      if (this.portalButtons.length === 0) {
        // получаем шаблон кнопки
        await this.getTemp();
        // активной кнопки нет
        this.activeButtonId = null;
        // включаем режим новой кнопки
        this.newButton = true;
        return;
      }

      // выбираем кнопку: либо последнюю (после создания), либо первую в списке
      const buttonToSelect = selectLast
        ? this.portalButtons[this.portalButtons.length - 1]
        : this.portalButtons[0];
      // делаем кнопку активной
      this.selectButton(buttonToSelect);
    },

    // получаем шаблонные настройки для новой кнопки
    async getTemp() {
      let response = await getTemplate(window.memberId,);
      this.current_button = response.result;
    },

    // получаем скрытые кнопки
    async getMorButtons() {
      let response = await getMoreButtons(window.memberId);
      this.morebuttons = response.result
    },

    // получаем остальные кнопки
    async showMoreButtons() {
      // показываем блок скрытых кнопок
      this.showMore = true;
      // если кнопки ещё не загружены и нет активной загрузки
      if (this.morebuttons.length === 0 && !this.loadingMore) {
        // блокируем повторную загрузку
        this.loadingMore = true;
        try {
          let response = await getMoreButtons(window.memberId);
          this.morebuttons = response.result || [];
        } finally {
          // снимаем флаг загрузки
          this.loadingMore = false;
        }
      }
    },

    // скрываем блок "Ещё"
    hideMoreButtons() {
        this.showMore = false;
    },

    // сохранние настроек кнопки
    async saveSettings() {
      this.loader = true;
      let response = await saveBtnSettings(
        window.memberId,
        this.current_button,
        this.activeButtonId
      );
      // если сохранение успешно
      if (response.result) {
        // сохраняем стили кнопки как "оригинальные"
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
      // если это была новая кнопка — обновляем список
      if (this.newButton && response.result) {
        this.newButton = false;
        await this.getButtons(true);
      }
    },
    // удаление кнопки
    async delButton() {
      if (!this.activeButtonId) return;
      this.loader = true;
      await deleteButton(window.memberId, this.activeButtonId);
      this.loader = false;
      // удаляем из Vue массива
      this.portalButtons = this.portalButtons.filter(
        b => b.ID !== this.activeButtonId
      );
      // сбрасываем активную кнопку
      this.activeButtonId = null;
      // если кнопки ещё есть — выбираем первую
      if (this.portalButtons.length > 0) {
        this.selectButton(this.portalButtons[0]);
      } else {
        // иначе переходим в режим создания
        await this.getTemp();
        this.newButton = true;
      }
    },

    // создать кнопку физически в карточке
    async createBtnCrm(domen) {
      this.loader = true;
      await createButtonInCrm(window.memberId, this.activeButtonId, domen, this.current_button);
      const response = await getButtonData(window.memberId,{ID:this.activeButtonId});
      this.current_button = JSON.parse(JSON.stringify(response.result));
      this.normalizeBooleans();
      this.loader = false;
      // если это новая кнопка — обновляем список
      if (this.newButton && response.result) {
        this.newButton = false;
        await this.getButtons(true);
      }
    },

    // удалить кнопку физически в карточке
    async deleteBtnCrm(domen) {
      this.loader = true;
      await deleteButtonInCrm(window.memberId, this.activeButtonId, domen);
      const response = await getButtonData(window.memberId, {ID: this.activeButtonId});
      this.current_button = JSON.parse(JSON.stringify(response.result));
      this.normalizeBooleans();
      this.loader = false;
    },

    // список полей, которые должны быть boolean
    normalizeBooleans() {
      const boolFields = [
        'buttonBorder_FIELDS',
        'usingTheIcon_FIELDS',
        'buttonInCRM_FIELDS',
      ]
      // проходимся по каждому полю и приводим значение к true / false
      boolFields.forEach(field => {
        if (field in this.current_button) {
          this.current_button[field] = this.current_button[field] === true || this.current_button[field] === 1 || this.current_button[field] === '1' || this.current_button[field] === 'true'
        }
      })
    },

    // ф-я выбора кнопки
    async selectButton(button) {
      // выключаем режим новой кнопки
      this.newButton = false; 
      // сохраняем ID активной кнопки
      this.activeButtonId = button.ID;
      let response = await getButtonData(window.memberId, button);
      // 🔥 кладём данные из PHP
      this.current_button = JSON.parse(JSON.stringify(response.result));
      // нормализуем boolean-поля
      this.normalizeBooleans()
      // если выбрана сущность — загружаем её поля
      if (this.current_button?.entitySelection_FIELDS) {
        await this.getEntFields()
      }
      // нормализуем таблицу полей
      this.normalizeFieldsTable()
      // сохраняем текущие стили кнопки
      this.originalButtonStyles = {
          buttonColor_FIELDS: this.current_button.buttonColor_FIELDS,
          color_text: this.current_button.color_text,
          buttonRadius_FIELDS: this.current_button.buttonRadius_FIELDS,
          buttonBorder_FIELDS: this.current_button.buttonBorder_FIELDS,
          buttonBorderWidth_FIELDS: this.current_button.buttonBorderWidth_FIELDS,
          buttonBorderColor_FIELDS: this.current_button.buttonBorderColor_FIELDS,
      }
      // если выбран список — подгружаем поля
      if (this.current_button.listsValue_FIELDS) {
        await this.onListChange(true)
      }
      // гарантируем, что buttonActionsId_FIELDS — массив
      if (!Array.isArray(this.current_button.buttonActionsId_FIELDS)) {
        this.$set(this.current_button, 'buttonActionsId_FIELDS', [])
      }
    },

    // получаем все нужные сущности портала
    async getEntitys() {
      this.loader = true;
      let response = await getAllEntitys(window.memberId,);
      this.allEntitys = response.result;
      this.loader = false;
    },

    // получаем все бп выбранной сущности
    async getBP() {
      this.loader = true;
      let response = await getBPforEntity(window.memberId, this.current_button);
      this.allBizProc = response.result;
      this.loader = false;
    },

    // получаем все документы выбранной сущности
    async getDocs() {
      this.loader = true;
      let response = await getDocumentsforEntity(window.memberId, this.current_button);
      this.allDocuments = response.result;
      this.loader = false;
    },

    // получаем все списки
    async getLists() {
      this.loader = true;
      let response = await getAllLists(window.memberId, this.current_button);
      this.allLists = response.result;
      this.loader = false;
    },

    // ф-я при смене списка
    async onListChange(silent = false) {
      // если режим не "тихий" — включаем лоадер
      if (!silent) this.loader = true
      // получаем поля выбранного списка
      let response = await getListFields(window.memberId, this.current_button)
      let freshFields = response.result

      // если есть сохранённые соответствия
      if (Array.isArray(this.current_button.fieldsTable_FIELDS)) {
        freshFields.forEach(f => {
          // ищем сохранённое соответствие по коду поля
          let saved = this.current_button.fieldsTable_FIELDS.find(
            s => s.value === f.value
          )
          // если соответствие найдено — восстанавливаем CRM-поле
          if (saved) {
            f.entField = saved.entField
          }
        })
      }
      // сохраняем обновлённую таблицу соответствий
      this.current_button.fieldsTable_FIELDS = freshFields
      // выключаем лоадер, если он был включён
      if (!silent) this.loader = false
    },

    // получаем все поля выбранной сущности для сопоставления с полями списка
    async getEntFields() {
      this.loader = true;
      let response = await getEntityFieldsForList(window.memberId, this.current_button);
      this.entFields = response.result;
      this.loader = false;
    },

    // получаем все поля с типом ссылка для выбранной сущности
    async getCrmLinks() {
      this.loader = true;
      let response = await getCrmFieldsLink(window.memberId, this.current_button);
      this.allCrmFieldsLink = response.result;
      this.loader = false;
    },

    bpSettings() {
      // если сущность не выбрана — выходим
      if (!this.checkEntitySelected()) return;
      // переключаем accordion блока бизнес-процессов и фиксируем, что пользователь открыл настройки БП
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

    // ф-я нормализации таблицы полей
    normalizeFieldsTable() {
      // текущая таблица соответствий полей
      const ft = this.current_button.fieldsTable_FIELDS
      // если формат не старый (не массив из 2 элементов) — выходим
      if (!Array.isArray(ft) || ft.length !== 2) return
      // crmFields — массив CRM-полей, listFields — массив полей списка
      const [crmFields, listFields] = ft
      // приводим старый формат к объектному
      this.current_button.fieldsTable_FIELDS = listFields.map((listCode, i) => {
        // CRM-поле по умолчанию отсутствует
        let entField = null
        // если CRM-поле существует и не равно строке 'null'
        if (crmFields[i] && crmFields[i] !== 'null') {
          // ищем CRM-поле в списке доступных полей сущности
          entField = this.entFields.find(
            f => f.value === crmFields[i]
          ) || null
        }
        // возвращаем объект соответствия
        return {
          value: listCode, // код поля списка
          name: listCode,  // отображаемое имя
          entField         // связанное CRM-поле
        }
      })
    },

    // отмена выбранных стилей
    async resetStylesButton() {
      if (!this.originalButtonStyles) return
      Object.keys(this.originalButtonStyles).forEach(key => {
        this.$set(this.current_button, key, this.originalButtonStyles[key])
      })
    },

    // алерт сущности если сущность не выбрана
    checkEntitySelected() {
      if (!this.current_button?.entitySelection_FIELDS) {
        alert('Не выбрана сущность');
        return false;
      }
      return true;
    },

    // при смене сущности в настройках кнопки
    onEntityChange() {
      // сбрасываем бизнес-процессы
      this.current_button.businessProcessesValue_FIELDS = [];
      this.allBizProc = [];

      // сбрасываем документы
      this.current_button.documentTemplatesValue_FIELDS = [];
      this.allDocuments = [];

      // сбрасываем списки и сопоставления полей
      this.current_button.listsValue_FIELDS = null;
      this.current_button.fieldsTable_FIELDS = [];
      this.allLists = [];
      this.entFields = [];

      // сбрасываем поля CRM-ссылки
      this.current_button.crmLinkFields_FIELDS = null;
      this.allCrmFieldsLink = [];

      // закрываем все accordion-блоки
      this.accordion_0 = false;
      this.accordion_1 = false;
      this.accordion_2 = false;
      this.accordion_4 = false;

      // сбрасываем флаги посещения настроек
      this.flagsButtonBizproc = false;
      this.flagsButtonDocument = false;
      this.flagsList = false;
      this.flagsButtonCrmLink = false;
    },

    // задать кнопке штатный стиль (как у битрикс24)
    SetStandardStyles() {
      this.current_button.textColor_FIELDS = "#ffffff";
      this.current_button.buttonColor_FIELDS = "#3bc8f5";
      this.current_button.buttonRadius_FIELDS = "0";
      this.current_button.buttonBorderWidth_FIELDS = 0;
      this.current_button.buttonBorderColor_FIELDS = 0;
      this.current_button.buttonBorder_FIELDS = false;
    },
 
    // добавление новой кнопки по нажатии на плюс
    async createBtn() {
      this.newButton = true
      let response = await getTemplate(window.memberId);
      this.current_button = response.result;
      this.activeButtonId = null;
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
  // watch: {
  //   'current_button.buttonActionsId_FIELDS'(val) {
  //   },
  // },
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
