import { getElement, launchBP, createDocument, addListElement, logInTable } from "../buttonHandlers/requests.js";
let btns
let userId = 0
btns = document.querySelectorAll('.btnAplicaton')
btns.forEach(function (btn) {
	// Вешаем событие клик
	btn.addEventListener('click', async function (e) {
		if(userId == 0){
			BX24.callMethod('user.current', {}, function(res){
				userId = res.answer.result.ID
			});
		}
		let id, buttonsProperties, idEntity, entity, buttonActions
		// получаем id элемента
		id = e.target.getAttribute('data-id')
		idEntity = e.target.getAttribute('data-identity')
		// Получаем свойства кнопки
		await getElement(id, window.memberId).then(function (value) {
			buttonsProperties = value
		})
		buttonsProperties.forEach(async function (buttonProperties) {
			if (buttonProperties.ID == id) {
				await logInTable(buttonProperties.PROPERTY_VALUES, userId, window.domain, id, window.memberId).then(function (value) {
				})
				buttonActions = JSON.parse(buttonProperties.PROPERTY_VALUES.buttonActionsId_FIELDS)
				entity = buttonProperties.PROPERTY_VALUES.entitySelection_FIELDS
				// смотрим выбранные действия кнопки и выполняем их
				buttonActions.forEach(async element => {
					switch (element) {
						case '0':
							let idBP, resultLaunchBP
							idBP = buttonProperties.PROPERTY_VALUES.businessProcessesValue_FIELDS
							await launchBP(idBP, idEntity, entity, window.memberId).then(function (value) {
								resultLaunchBP = value
							})
							break
						case '1':
							let idDocument, resultCreateDocument
							idDocument = buttonProperties.PROPERTY_VALUES.documentTemplatesValue_FIELDS
							await createDocument(idDocument, idEntity, entity, window.memberId).then(function (value) {
								resultCreateDocument = value
							})
							break
						case '2':
							let idLists, resultAddListElement, fieldsList
							idLists = JSON.parse(buttonProperties.PROPERTY_VALUES.listsValue_FIELDS)
							fieldsList = JSON.parse(buttonProperties.PROPERTY_VALUES.fieldsTable_FIELDS)
							if (idLists != null) {
								// console.log(idLists, idEntity, entity, fieldsList, window.memberId)
								await addListElement(idLists, idEntity, entity, fieldsList, window.memberId).then(function (value) {
									resultAddListElement = value
								})
							}
							break
						case '3':
							let link = buttonProperties.PROPERTY_VALUES.link_FIELDS
							window.open(link, '_blank');
							break
					}
				});
			}
		})
	})
})
