import { getRowsData, getNewRowData, deleteRow, updateEntityData, updateListFields, saveButton, createButtonInCRM, deleteButtonInCRM } from "../applicationHandlers/index.js";
import { applicationsReport } from "../components/components.js"

Vue.component("modal", {
    template: "#modal-template"
});
var app = new Vue({
    el: '#app',
    components: { Multiselect: window.VueMultiselect.default, applicationsReport },
    data () {
        return {
            message: [],
            selected: [],
            rows: [],
            lists_btn_bool: false,
            color: 0,
            accordion_0: false,
            accordion_1: false,
            accordion_2: false,
            accordion_3: false,
            current_button: {},
            selected_button: {},
            countId: -1,
            newRow: {},
            showModal: false,
            test: [],
            loader: false
        }
    },
    computed: {
        calculateBooksMessage () {
            return this.current_button.buttonBorderSelection == true ? "solid" : "none"
        }
    },
    methods: {
        validator (min, max, value) {
            let currentValue = value
            if (value > max) {
                currentValue = max
            }
            if (value < min) {
                currentValue = min
            }
            return currentValue
        },
        // задать стили при сбросе
        resetStylesButton () {
            let styles = this.current_button.styleButton
            this.current_button.color_text = styles.color
            this.current_button.color_btn = styles.backgroundColor
            this.current_button.radius_btn = styles.borderRadius
            this.current_button.buttonBorderWidth = styles.borderWidth
            this.current_button.buttonBorderColor = styles.borderColor
            this.current_button.buttonBorderSelection = styles.border
        },
        // Задать стили штатной кнопки
        SetStandardStyles () {
            this.current_button.color_text = '#ffffff'
            this.current_button.color_btn = '#3bc8f5'
            this.current_button.radius_btn = '0'
            this.current_button.buttonBorderWidth = 0
            this.current_button.buttonBorderColor = 0
            this.current_button.buttonBorderSelection = false
        },
        // Сохарнить стили кнопки
        saveStylesButton () {
            let styles = this.current_button.styleButton
            styles.color = this.current_button.color_text
            styles.backgroundColor = this.current_button.color_btn
            styles.borderRadius = this.current_button.radius_btn
            styles.borderWidth = this.current_button.buttonBorderWidth
            styles.borderColor = this.current_button.buttonBorderColor
            styles.border = this.current_button.buttonBorderSelection
        },
        // Открытие, закрытие аккордиона
        accordion (elem) {
            this[`accordion_${elem}`] = !this[`accordion_${elem}`]
        },
        open_lists_btn (elem) {
            // подчеркивание выбранного таба 
            this.selected_button = Object.assign(this.selected_button, this.current_button)
            this.rows.forEach(element => {
                element.lists_btn_bool = false;
            });
            elem.lists_btn_bool = true
            // Вывод данных выбранного таба
            this.current_button = { ...elem }
            this.selected_button = elem
        },
        hide () {
            this.lists_btn_bool = false
        },
        show () {
            this.lists_btn_bool = true
        },
        // Сохранение настроек
        async saving_settings (domen) {
            let arrayBtn = this.current_button.button_actions
            let id = this.current_button.button_actions.id
            let paddingError = false;
            let paddingErrorText = '';
            id.forEach(elem => {
                switch (elem) {
                    case "0":
                        if (arrayBtn.business_processes.value.length == 0) {
                            paddingError = true
                            paddingErrorText += 'Не выбран бизнес-процесс \n'
                        }
                        break
                    case "1":
                        if (arrayBtn.document_templates.value.length == 0) {
                            paddingError = true
                            paddingErrorText += 'Не выбран шаблон документа \n'
                        }
                        break
                    case "2":
                        arrayBtn.fields_table.forEach(element => {
                            if (element.fieldsLists.isRequired == "Y" && element.fieldsEntiyValue == null) {
                                paddingError = true
                                paddingErrorText += 'Не заполнено обязательное поле списка:' + element.fieldsLists.name + ' \n'
                            }
                            if (element.fieldsEntiyValue != null) {
                                if ((element.fieldsLists.list == false && element.fieldsEntiyValue.list == true) || (element.fieldsLists.list == true && element.fieldsEntiyValue.list == false)) {
                                    paddingError = true
                                    paddingErrorText += 'Не верное сопостовление типов полей, одно поле не является списком:' + element.fieldsLists.name + ' \n'
                                }
                            }

                        })
                        break
                    case "3":
                        if (arrayBtn.link == '') {
                            paddingError = true
                            paddingErrorText += 'Не заполнена ссылка \n'
                        }
                        break
                }
            })

            if (paddingError) {
                alert(paddingErrorText)
                return false
            } else {
                this.loader = true
                this.saveStylesButton() // Сохранение стилей     
                let response = await saveButton(this.current_button, domen, window.memberId)
                if (typeof response == 'number') {
                    this.current_button.id = response
                    this.loader = false
                    return response
                }
                this.selected_button = Object.assign(this.selected_button, this.current_button)
                this.loader = false
                return this.current_button.id
            }
        },
        async changingAnEntity (event) {
            this.loader = true
            let newData = await updateEntityData(event.value, window.memberId)
            this.current_button.button_actions.business_processes = newData.businessProcesses
            this.current_button.button_actions.document_templates = newData.documentTemplates
            this.current_button.button_actions.lists = newData.lists
            this.current_button.button_actions.fields_table = []
            this.loader = false
        },
        removeAnEntity () {
            this.current_button.button_actions.business_processes = {
                "options": [],
                "value": []
            }
            this.current_button.button_actions.document_templates = {
                "options": [],
                "value": []
            }
            this.current_button.button_actions.lists = {
                "options": [],
                "value": []
            }
            this.current_button.button_actions.fields_table = [];
        },
        async listSelection (event) {
            this.loader = true
            let fieldslistNew = await updateListFields(event.value, this.current_button.array_entities_value.value, window.memberId)
            this.current_button.button_actions.fields_table = fieldslistNew.newData.fields_table
            this.current_button.button_actions.optionsEntity = fieldslistNew.newData.optionsEntity
            this.loader = false
        },
        listRemove (event) {
            this.current_button.button_actions.fields_table = [];
        },

        async addBtn (id, domen) {
            this.loader = true
            let res = this.rows.find(item => item.id == id)
            if (res.button_actions.button_in_CRM) {
                let saveSetting = await this.saving_settings()
                if (saveSetting) {
                    res.button_actions.button_in_CRM = false
                    let a = deleteButtonInCRM(id, domen, window.memberId);
                }
            } else {
                let id = await this.saving_settings()
                if (id) {
                    await createButtonInCRM(id, domen, window.memberId)
                    res.button_actions.button_in_CRM = true
                }
            }
            this.loader = false
        },
        resetData () {
            if (this.rows.length != 0) {
                this.current_button = { ...this.rows[0] }
                console.log(this.current_button)
                this.rows[0].lists_btn_bool = true
                this.selected_button = this.rows[0]
            } else {
                this.createBtn()
            }
            this.loader = false


        },
        async deleteBtn (domen) {
            this.rows = this.rows.filter((row) => row.id != this.current_button.id)
            await deleteButtonInCRM(this.current_button.id, domen, window.memberId)
            await deleteRow(this.current_button.id, window.memberId)
            this.resetData()
        },
        async createBtn () {
            console.log(window.memberId)
            this.newRow = await getNewRowData(window.memberId)
            this.newRow.id = this.countId
            this.rows.forEach(element => {
                element.lists_btn_bool = false;
            });
            this.rows.push(this.newRow)
            this.current_button = this.newRow
            this.selected_button = this.rows[this.rows.length - 1]
            this.countId = this.countId - 1

        },
        async initialData () {
            this.loader = true
            this.rows = await getRowsData(window.memberId)
            // console.log(this.rows)
        }
    },
    async created () {
        await this.initialData()
        this.resetData()
    }
})