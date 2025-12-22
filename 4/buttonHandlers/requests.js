const api = axios.create({ baseURL: `https://app.overplan.ru/applications/crmButtons/${window.versions}/buttonHandlers` })

export const getElement = async (id, memberId) => {
	const response = await api.post('/getElement.php', {
		id: id,
		memberId: memberId
	})
	return response.data
}
export const logInTable = async (prop, userId, domain, id, memberId) => {
	const response = await api.post('/logInTable.php', {
		prop: prop,
		domain: domain,
		id: id,
		userId: userId,
		memberId: memberId
	})
	return response.data.result
}
export const launchBP = async (id, idEntity, entity, memberId, parametrs) => {
	const response = await api.post('/launchBP.php', {
		id: id,
		idEntity: idEntity,
		entity: entity,
		memberId: memberId,
		parametrs: parametrs

	})
	return response.data.result
}
export const checkBPParametrs = async (id, memberId, options) => {
	const response = await api.post('/checkBPParametrs.php', {
		id: id,
		options: options,
		memberId: memberId
	})
	return response.data
}

export const createDocument = async (id, idEntity, entity, memberId) => {
	const response = await api.post('/createDocument.php', {
		id: id,
		idEntity: idEntity,
		entity: entity,
		memberId: memberId
	})
	return response.data.result
}
export const addListElement = async (id, idEntity, entity, fieldsList, memberId) => {
	const response = await api.post('/addListElement.php', {
		id: id,
		idEntity: idEntity,
		entity: entity,
		fieldsList: fieldsList,
		memberId: memberId
	})
	return response.data.result
}


