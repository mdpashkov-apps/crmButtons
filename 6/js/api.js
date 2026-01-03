const mainApi = axios.create({
  baseURL: "https://app.overplan.ru/applications/crmButtons/6/api/",
});


// начало ф-ий для script js
export const getAllButtons = async (memberId) => {
  const response = await mainApi.post("/getAllButtons.php", {
    memberId: memberId,
  });
  return response.data;
};
export const getMoreButtons = async (memberId) => {
  const response = await mainApi.post("/getMoreButtons.php", {
    memberId: memberId,
  });
  return response.data;
};
export const createButton = async (memberId) => {
  const response = await mainApi.post("/createButton.php", {
    memberId: memberId,
  });
  return response.data;
};

// начало ф-ий для script js
export const getTemplate = async (memberId) => {
  const response = await mainApi.post("/getTemplate.php", {
    memberId: memberId,
  });
  return response.data;
};


export const saveBtnSettings = async (memberId, current_button, activeButtonId) => {
  const response = await mainApi.post("/saveBtnSettings.php", {
    memberId: memberId,
    btnSettings: current_button,
    activeButtonId: activeButtonId
  });
  return response.data;
};

export const deleteButton = async (memberId, activeButtonId) => {
  const response = await mainApi.post("/deleteButton.php", {
    memberId: memberId,
    activeButtonId: activeButtonId
  });
  return response.data;
};

export const getButtonData = async (memberId, button) => {
  const response = await mainApi.post("/getButtonData.php", {
    memberId: memberId,
    button_ID: button.ID
  });
  return response.data;
};

export const getAllEntitys = async (memberId, ) => {
  const response = await mainApi.post("/getAllEntitys.php", {
    memberId: memberId,
  });
  return response.data;
};


export const getBPforEntity = async (memberId,current_button ) => {
  const response = await mainApi.post("/getBPforEntity.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};

export const getDocumentsforEntity = async (memberId,current_button ) => {
  const response = await mainApi.post("/getDocumentsforEntity.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};


export const getListsforEntity = async (memberId,current_button ) => {
  const response = await mainApi.post("/getListsforEntity.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};


export const getListFields = async (memberId,current_button ) => {
  const response = await mainApi.post("/getListFields.php", {
    memberId: memberId,
    entity: current_button.entitySelection_FIELDS,
    list: current_button.listsValue_FIELDS

  });
  return response.data;
};

export const getEntityFields = async (memberId,current_button ) => {
  const response = await mainApi.post("/getEntityFields.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};


export const createButtonInCrm = async (memberId,activeButtonId, domen  ) => {
  const response = await mainApi.post("/createButtonInCrm.php", {
    memberId: memberId,
 activeButtonId: activeButtonId,
      domen: domen,
  });
  return response.data;
};
export const deleteButtonInCrm = async (memberId,activeButtonId, domen  ) => {
  const response = await mainApi.post("/deleteButtonInCrm.php", {
    memberId: memberId,
 activeButtonId: activeButtonId,
      domen: domen,
  });
  return response.data;
};


export const getCrmFieldsLink = async (memberId,current_button ) => {
  const response = await mainApi.post("/getCrmFieldsLink.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};