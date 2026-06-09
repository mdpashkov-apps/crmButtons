const buttonApi = axios.create({
  baseURL: "https://app.overplan.ru/applications/crmButtons/7/buttonHandlers/api/",
});

export const getBpParams = async (memberId,crmActions,entityData) => {
  const response = await buttonApi.post("/getBpParams.php", {
    memberId: memberId,
    crmActions:crmActions,
    entityData:entityData
  });
  return response.data;
};

export const getBpChainParams = async (memberId, crmActions, entityData) => {
  const response = await buttonApi.post("/getBpChainParams.php", {
    memberId: memberId,
    crmActions: crmActions,
    entityData: entityData
  });
  return response.data;
};

export const startBp = async (memberId,bpParam, entityData) => {
  const response = await buttonApi.post("/startBp.php", {
    memberId: memberId,
    bpParam:bpParam,
      entityData:entityData

  });
  return response.data;
};

export const createDocument = async (memberId,crmActions,entityData) => {
  const response = await buttonApi.post("/createDocument.php", {
    memberId: memberId,
    crmActions:crmActions,
    entityData:entityData
  });
  return response.data;
};

export const createListElement = async (memberId,crmActions,entityData) => {
  const response = await buttonApi.post("/createListElement.php", {
    memberId: memberId,
    crmActions:crmActions,
    entityData:entityData
  });
  return response.data;
};

export const OpenCrmLink = async (memberId,crmActions, entityData) => {
  const response = await buttonApi.post("/OpenCrmLink.php", {
    memberId: memberId,
    crmActions:crmActions,
    entityData:entityData
  });
  return response.data;
};