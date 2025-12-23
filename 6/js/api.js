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



