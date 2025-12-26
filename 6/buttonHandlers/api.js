const mainApi = axios.create({
  baseURL: "https://app.overplan.ru/applications/crmButtons/6/buttonHandlers/api/",
});

// начало ф-ий для script js
export const OpenCrmLink = async (memberId,linkField) => {
  const response = await mainApi.post("/OpenCrmLink.php", {
    memberId: memberId,
    linkField:linkField
  });
  return response.data;
};

export const startBp = async (memberId,crmActions) => {
  const response = await mainApi.post("/startBp.php", {
    memberId: memberId,
    crmActions:crmActions
  });
  return response.data;
};

export const createDocument = async (memberId,crmActions) => {
  const response = await mainApi.post("/createDocument.php", {
    memberId: memberId,
    crmActions:crmActions
  });
  return response.data;
};
