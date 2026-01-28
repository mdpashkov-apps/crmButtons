import {getBpParams,startBp, createDocument, createListElement, OpenCrmLink, } from "../js/api.js";

const BX24 = await window.__bxReady;

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
      loader: false, // лодер
      buttonActionsId_FIELDS: [], // id действий кнопки
      paramResult: null, // если в buttonActionsId_FIELDS есть 0, то мы получаем параметры нужных бп и кладем сюда
      formValues: {}, 
      currentBpIndex: 0, // текущий БП для которого вводятся параметры для запуска
      allUsers: [], // список пользователей портала в мультиселекте с параметрами бп 
      boolOptions: [
        { value: 'Y', name: 'Да' },
        { value: 'N', name: 'Нет' },
      ],
    };
  },

  methods: {
    // ф-я запуска нужных действий
    async runActions() {
      // Получаем список действий кнопки из глобальных настроек (JSON) и преобразуем строку JSON в массив ID действий
      let raw = window.crmActions.buttonActionsId_FIELDS;
      const actions = JSON.parse(raw);
      // Карта соответствия: ID действия -> ф-я которую надо выполнить
      const actionsMap = {
        0: this.action0,
        1: this.action1,
        2: this.action2, 
        3: this.action3, 
        4: this.action4, 
      };
      // Последовательно выполняем все действия кнопки
      for (const actionId of actions) {
        // получаем функцию действия по его ID
        const actionFn = actionsMap[actionId];
        // если действие существует — выполняем его
        if (actionFn) {
          // await нужен, чтобы действия выполнялись строго по порядку
          await actionFn();
        }
      }
    },

    // данное действие работает на запуск бп (если у бп параметров нет то бп запустится в php файле, если есть то вернет бп с параметрами)
    async action0() {
      this.loader = true;
      const response = await getBpParams(window.memberId, window.crmActions,window.entityData);
      this.paramResult = response.result;
      if (response.withoutParams?.length) {
        await this.runBpWithoutParams(response.withoutParams, response.document);
      }
      // если есть бп с параметром привязки к пользователю, из запроса вернется список юзеров портала
      if (response.allUserFio) {
        this.allUsers = response.allUserFio;
      }
      // тоже самое для bool
      if (response.BoolOptions) {
        this.boolOptions = response.BoolOptions;
      }
      // Инициализация формы параметров БП
      this.paramResult.forEach(bp => {
        // Создаём объект для хранения значений формы конкретного БП
        // Ключ — ID бизнес-процесса
        // this.$set нужен, чтобы Vue сделал объект реактивным
        this.$set(this.formValues, bp.ID, {});
        // Проходимся по всем параметрам текущего БП
        bp.PARAMETERS.forEach(p => {
          let values;
          if (p.Type === 'user') {
            values = p.Multiple ? [] : null;
          } else {
            const defaultVal = p.Default || '';
            values = p.Multiple ? [defaultVal] : [defaultVal];
          }
          // Сохраняем начальное значение параметра в модель формы
          // Ключ — имя параметра бизнес-процесса
          this.$set(this.formValues[bp.ID], p.Name, values);
        });
      });
      this.loader = false;
    },

    // запуск бп без параметров
    async runBpWithoutParams(withoutParams, document) {
      // Если БП нет — выходим
      if (!withoutParams || !withoutParams.length) return;
      // Последовательно запускаем каждый БП через js
      for (const bp of withoutParams) {
        await new Promise((resolve, reject) => {
          BX24.callMethod(
            'bizproc.workflow.start',
            {
              TEMPLATE_ID: bp.ID,
              DOCUMENT_ID: document
            },
            result => {
              if (result.error()) {
                reject(result.error());
              } else {
                resolve(result.data());
              }
            }
          );
        });
      }
    },

    // данное действие работает на создание документа
    async action1() {
      this.loader = true;
      let response = await createDocument(window.memberId,window.crmActions, window.entityData);
      this.loader = false;
    },

    // данное действие работает на создание элемента списка
    async action2() {
      this.loader = true;
      let response = await createListElement(window.memberId,window.crmActions, window.entityData);
      this.loader = false;
    },

    // данное действие работает на переход по произвольной ссылке 
    async action3() {
      let arbitraryLink = window.crmActions.link_FIELDS; // получаем сразу из параметра кнопки ссылку
      // проверяем, начинается ли ссылка с http:// или https://, если нет — добавляем https://
      if (!/^https?:\/\//i.test(arbitraryLink)) {
        arbitraryLink = 'https://' + arbitraryLink;
      }
      // открываем ссылку в новой вкладке
      window.open(arbitraryLink, '_blank');
    },

    // данное действие работает на переход по ссылке из поля crm
    async action4() {
      let response = await OpenCrmLink(window.memberId,window.crmActions, window.entityData);
      let crmLink = response.result;
      // проверяем, начинается ли ссылка с http:// или https://, если нет — добавляем https://
      if (!/^https?:\/\//i.test(crmLink)) {
        crmLink = 'https://' + crmLink;
      }
      // открываем ссылку в новой вкладке
      window.open(crmLink, '_blank');
    },

    // ф-я запуска текущего бизнес-процесса с параметрами
    async runCurrentBp() {
      // Текущий бизнес-процесс
      const bp = this.paramResult[this.currentBpIndex];
      const preparedParams = {};
      // Формируем параметры БП из данных формы
      bp.PARAMETERS.forEach(param => {
        const values = this.formValues[bp.ID][param.Name];
        // Если параметр не заполнен — пропускаем
        if (values === undefined || values === null) return;
        preparedParams[param.paramKey] = {
          type: param.Type,
          multiple: !!param.Multiple,
          // user передаём как есть,
          // остальные — оборачиваем одиночное значение в объект
          value:
            param.Type === 'user'
              ? values
              : (param.Multiple ? values : { value: values })  // 🔴 оборачиваем в объект
        };
      });
      // Получаем данные для запуска БП
      const response = await startBp(
        window.memberId,
        { [bp.ID]: preparedParams },
        window.entityData
      );

      const { templateId, document, parameters } = response;
      // Запуск БП через BX24
      await new Promise((resolve, reject) => {
        BX24.callMethod(
          'bizproc.workflow.start',
          {
            TEMPLATE_ID: templateId,
            DOCUMENT_ID: document,
            PARAMETERS: parameters
          },
          res => {
            if (res.error()) reject(res.error());
            else resolve(res.data());
          }
        );
      });

      // Переходим к следующему БП или закрываем форму
      if (this.currentBpIndex < this.paramResult.length - 1) {
        this.currentBpIndex++;
      } else {
        this.paramResult = null;
      }
    },

    // Добавляет новое пустое поле для параметра и инпут (если множественное)
    addField(bpId, paramName) {
      // В массив значений добавляем пустую строку, во Vue появится новое поле ввода
      this.formValues[bpId][paramName].push('');
    },
  
    // Удаляет значение параметра и инпут (еслимножественное поле)
    removeField(bpId, paramName, index) {
      // Если это первый элемент — ничего не делаем
      if (index === 0) return;
      // Удаляем элемент массива по индексу
      this.formValues[bpId][paramName].splice(index, 1);
    },

    // Функция подгоняет высоту iframe приложения под контент
    resizeBx() {
      // Если это не Bitrix24 — выходим
      if (!window.BX24) return;
      // Ищем корневой элемент приложения
      const app = document.querySelector('#app');
      if (!app) return;
      // Получаем ширину скролла iframe
      const size = BX24.getScrollSize();
      // Высота приложения + небольшой отступ
      const height = app.clientHeight + 30;
      // Меняем размер iframe в Битриксе
      BX24.resizeWindow(size.scrollWidth, height);
    },
  },
  computed: {
    // Проверка: заполнены ли все обязательные поля текущего БП
    isCurrentBpValid() {
      // Если бизнес-процессов нет — невалидно
      if (!this.paramResult) return false;
      // Берём текущий бизнес-процесс
      const bp = this.paramResult[this.currentBpIndex];
      // Проверяем все параметры БП
      for (const p of bp.PARAMETERS) {
        // Интересуют только обязательные
        if (p.Required) {
          // Значения параметра из формы
          const values = this.formValues[bp.ID][p.Name];
          // Если значений нет или есть пустые — форма невалидна
          if (!values || values.some(v => !v || v === '')) {
            return false;
          }
        }
      }
      // Все обязательные поля заполнены
      return true;
    }
  },
  mounted() {
    // Подгоняем высоту iframe сразу после рендера
    this.resizeBx();
  },
  // Подгоняем высоту iframe после каждого обновления DOM
  updated() {
    this.resizeBx();
  },
});
