const api = axios.create({
  baseURL: "https://app.overplan.ru/applications/crmButtons/6/api/",
});

// получить все кнопки
export const getAllButtons = async (memberId) => {
  const response = await api.post("/button-crud/getAllButtons.php", {
    memberId: memberId,
  });
  return response.data;
};

// получить шаблонные параметры
export const getTemplate = async () => {
  const response = await api.get("/button-crud/getTemplate.php");
  return response.data;
};

// получитьт больше кнопок
export const getMoreButtons = async (memberId) => {
  const response = await api.post("/button-crud/getMoreButtons.php", {
    memberId: memberId,
  });
  return response.data;
};

// сохранить настройки
export const saveBtnSettings = async (memberId, current_button, activeButtonId) => {
  const response = await api.post("/button-crud/saveBtnSettings.php", {
    memberId: memberId,
    btnSettings: current_button,
    activeButtonId: activeButtonId
  });
  return response.data;
};

// удалить настройки кнопки
export const deleteButton = async (memberId, activeButtonId) => {
  const response = await api.post("/button-crud/deleteButton.php", {
    memberId: memberId,
    activeButtonId: activeButtonId
  });
  return response.data;
};

// создать кнопку в crm
export const createButtonInCrm = async (memberId,activeButtonId, domen, btnSettings  ) => {
  const response = await api.post("/button-crud/createButtonInCrm.php", {
    memberId: memberId,
 activeButtonId: activeButtonId,
      domen: domen,
      btnSettings:btnSettings
  });
  return response.data;
};

// удалить кнопку из crm
export const deleteButtonInCrm = async (memberId,activeButtonId, domen  ) => {
  const response = await api.post("/button-crud/deleteButtonInCrm.php", {
    memberId: memberId,
 activeButtonId: activeButtonId,
      domen: domen,
  });
  return response.data;
};

// получить данные кнопки
export const getButtonData = async (memberId, button) => {
  const response = await api.post("/button-crud/getButtonData.php", {
    memberId: memberId,
    button_ID: button.ID
  });
  return response.data;
};

// получить все сущности портала
export const getAllEntitys = async (memberId, ) => {
  const response = await api.post("/button-actions/getAllEntitys.php", {
    memberId: memberId,
  });
  return response.data;
};

// получить все бп выбранной сущности
export const getBPforEntity = async (memberId,current_button ) => {
  const response = await api.post("/button-actions/getBPforEntity.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};

// получить все документы выбранной сущности
export const getDocumentsforEntity = async (memberId,current_button ) => {
  const response = await api.post("/button-actions/getDocumentsforEntity.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};

// получить все списки
export const getAllLists = async (memberId,current_button ) => {
  const response = await api.post("/button-actions/getAllLists.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};

// получить все поля выбранного списка
export const getListFields = async (memberId,current_button ) => {
  const response = await api.post("/button-actions/getListFields.php", {
    memberId: memberId,
    list: current_button.listsValue_FIELDS

  });
  return response.data;
};

// получить все поля выбранной сущности
export const getEntityFieldsForList = async (memberId,current_button ) => {
  const response = await api.post("/button-actions/getEntityFieldsForList.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};

// получить все поля типа ссылка выбранной сущности
export const getCrmFieldsLink = async (memberId,current_button ) => {
  const response = await api.post("/button-actions/getCrmFieldsLink.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};


export const getAllUsers = async (memberId) => {
  const response = await api.post("/getAllUsers.php", {
    memberId: memberId,
  });
  return response.data;
};

export const addUsersInChat = async (memberId,selectedUsers) => {
  const response = await api.post("/addUsersInChat.php", {
    memberId: memberId,
    selectedUsers: selectedUsers
  });
  return response.data;
};

export const deleteChatBot = async (memberId) => {
  const response = await api.post("/deleteChatBot.php", {
    memberId: memberId,
  });
  return response.data;
};

