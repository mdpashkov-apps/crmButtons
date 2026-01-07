const api = axios.create({
  baseURL: "https://app.overplan.ru/applications/crmButtons/6/api/",
});

export const getAllButtons = async (memberId) => {
  const response = await api.post("/button-crud/getAllButtons.php", {
    memberId: memberId,
  });
  return response.data;
};

export const getTemplate = async () => {
  const response = await api.get("/button-crud/getTemplate.php");
  return response.data;
};

export const getMoreButtons = async (memberId) => {
  const response = await api.post("/getMoreButtons.php", {
    memberId: memberId,
  });
  return response.data;
};

export const saveBtnSettings = async (memberId, current_button, activeButtonId) => {
  const response = await api.post("/button-crud/saveBtnSettings.php", {
    memberId: memberId,
    btnSettings: current_button,
    activeButtonId: activeButtonId
  });
  return response.data;
};

export const deleteButton = async (memberId, activeButtonId) => {
  const response = await api.post("/button-crud/deleteButton.php", {
    memberId: memberId,
    activeButtonId: activeButtonId
  });
  return response.data;
};

export const createButtonInCrm = async (memberId,activeButtonId, domen  ) => {
  const response = await api.post("/createButtonInCrm.php", {
    memberId: memberId,
 activeButtonId: activeButtonId,
      domen: domen,
  });
  return response.data;
};

export const deleteButtonInCrm = async (memberId,activeButtonId, domen  ) => {
  const response = await api.post("/deleteButtonInCrm.php", {
    memberId: memberId,
 activeButtonId: activeButtonId,
      domen: domen,
  });
  return response.data;
};

export const getButtonData = async (memberId, button) => {
  const response = await api.post("/getButtonData.php", {
    memberId: memberId,
    button_ID: button.ID
  });
  return response.data;
};

export const getAllEntitys = async (memberId, ) => {
  const response = await api.post("/getAllEntitys.php", {
    memberId: memberId,
  });
  return response.data;
};

export const getBPforEntity = async (memberId,current_button ) => {
  const response = await api.post("/getBPforEntity.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};

export const getDocumentsforEntity = async (memberId,current_button ) => {
  const response = await api.post("/getDocumentsforEntity.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};


export const getAllLists = async (memberId,current_button ) => {
  const response = await api.post("/getAllLists.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};


export const getListFields = async (memberId,current_button ) => {
  const response = await api.post("/getListFields.php", {
    memberId: memberId,
    entity: current_button.entitySelection_FIELDS,
    list: current_button.listsValue_FIELDS

  });
  return response.data;
};

export const getEntityFieldsForList = async (memberId,current_button ) => {
  const response = await api.post("/getEntityFieldsForList.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};

export const getCrmFieldsLink = async (memberId,current_button ) => {
  const response = await api.post("/getCrmFieldsLink.php", {
    memberId: memberId,
    current_button: current_button.entitySelection_FIELDS

  });
  return response.data;
};


// export const createButton = async (memberId) => {
//   const response = await api.post("/createButton.php", {
//     memberId: memberId,
//   });
//   return response.data;
// };

