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


