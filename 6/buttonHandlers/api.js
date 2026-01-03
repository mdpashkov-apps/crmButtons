const mainApi = axios.create({
  baseURL: "https://app.overplan.ru/applications/crmButtons/6/buttonHandlers/api/",
});

// начало ф-ий для script js
export const OpenCrmLink = async (memberId,crmActions, entityData) => {
  const response = await mainApi.post("/OpenCrmLink.php", {
    memberId: memberId,
    crmActions:crmActions,
    entityData:entityData
  });
  return response.data;
};

export const startBp = async (memberId,bpParam, entityData) => {
  const response = await mainApi.post("/startBp.php", {
    memberId: memberId,
    bpParam:bpParam,
      entityData:entityData

  });
  return response.data;
};

export const createDocument = async (memberId,crmActions,entityData) => {
  const response = await mainApi.post("/createDocument.php", {
    memberId: memberId,
    crmActions:crmActions,
    entityData:entityData
  });
  return response.data;
};

export const createListElement = async (memberId,crmActions,entityData) => {
  const response = await mainApi.post("/createListElement.php", {
    memberId: memberId,
    crmActions:crmActions,
    entityData:entityData
  });
  return response.data;
};


export const getBpParams = async (memberId,crmActions,entityData) => {
  const response = await mainApi.post("/getBpParams.php", {
    memberId: memberId,
    crmActions:crmActions,
    entityData:entityData
  });
  return response.data;
};
