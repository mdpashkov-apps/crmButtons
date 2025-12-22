const api = axios.create({ baseURL: 'https://app.overplan.ru/applications/crmButtons/1/applicationHandlers' })

// Получить все кнопки
export const getRowsData = async (memberId) => {
    const response = await api.post('/rows.php', {
        memberId: memberId
    })
    return response.data.rows

}
// Получить массив для новой кнопки
export const getNewRowData = async (memberId) => {
    const response = await api.post('/row_new.php', {
        memberId: memberId
    })
    return response.data.row
}

export const deleteRow = async (id, memberId) => {
    const response = await api.post('/delete_row.php', {
        id: id,
        memberId: memberId
    })
    return response.data.row
}

export const updateEntityData = async (valueEntity, memberId) => {
    const response = await api.post('/updateEntityData.php', {
        valueEntity: valueEntity,
        memberId: memberId
    })
    return response.data.newData
}

export const updateListFields = async (idList, entitie, memberId) => {
    const response = await api.post('/updateListFields.php', {
        idList: idList,
        entitie: entitie,
        memberId: memberId
    })
    return response.data
}
export const saveButton = async (allFields, domen, memberId) => {
    const response = await api.post('/saveButton.php', {
        allFields: allFields,
        domen: domen,
        memberId: memberId
    })
    return response.data.result
}
export const createButtonInCRM = async (id, domen, memberId) => {
    const response = await api.post('/CreateButtonInCRM.php', {
        id: id,
        domen: domen,
        memberId: memberId
    })
    return response.data.error
}

export const deleteButtonInCRM = async (id, domen, memberId) => {
    const response = await api.post('/deleteButtonInCRM.php', {
        id: id,
        domen: domen,
        memberId: memberId
    })
    return response.data.error
}

// echo json_encode([
//     'goods' => $dealProductsFancy,
//     'deal' => $dealData,
//     'sections' => $sections,
//     'purchasers' => $purchasers,
//     'statusOrder' => $statusOrder,
//     'dealWarehouseName' => $dealWareName,
//     'warehouseXML' => $warehouseXML,
//     'lineCode' => $line_code
// ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);