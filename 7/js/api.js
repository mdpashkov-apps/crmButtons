// js/api.js

// Ожидание загрузки axios с правильной проверкой
let axiosLoaded = false;
let axiosPromise = null;

function waitForAxios() {
    if (axiosPromise) return axiosPromise;
    
    axiosPromise = new Promise((resolve) => {
        if (typeof axios !== 'undefined' && axios && typeof axios.create === 'function') {
            axiosLoaded = true;
            resolve();
            return;
        }
        
        let attempts = 0;
        const maxAttempts = 20;
        
        const checkInterval = setInterval(() => {
            attempts++;
            if (typeof axios !== 'undefined' && axios && typeof axios.create === 'function') {
                clearInterval(checkInterval);
                axiosLoaded = true;
                console.log('Axios загружен успешно');
                resolve();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                console.warn('Axios не загрузился, используем fetch fallback');
                resolve();
            }
        }, 500);
    });
    
    return axiosPromise;
}

const API_BASE_URL = "https://app.overplan.ru/applications/crmButtons/7/api";

let apiClient = null;

async function getApiClient() {
    await waitForAxios();
    
    if (apiClient) return apiClient;
    
    if (axiosLoaded && typeof axios !== 'undefined' && axios.create) {
        apiClient = {
            isAxios: true,
            post: async (url, data) => {
                try {
                    const fullUrl = API_BASE_URL + (url.startsWith('/') ? url : '/' + url);
                    console.log('POST URL:', fullUrl);
                    const response = await axios.post(fullUrl, data, {
                        timeout: 30000,
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    return response;
                } catch (error) {
                    console.error('Axios POST error:', error);
                    throw error;
                }
            },
            get: async (url) => {
                try {
                    const fullUrl = API_BASE_URL + (url.startsWith('/') ? url : '/' + url);
                    console.log('GET URL:', fullUrl);
                    const response = await axios.get(fullUrl, {
                        timeout: 30000
                    });
                    return response;
                } catch (error) {
                    console.error('Axios GET error:', error);
                    throw error;
                }
            }
        };
        console.log('Используем axios для API');
    } else {
        apiClient = {
            isAxios: false,
            post: async (url, data) => {
                try {
                    const fullUrl = API_BASE_URL + (url.startsWith('/') ? url : '/' + url);
                    console.log('Fetch POST URL:', fullUrl);
                    const response = await fetch(fullUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const result = await response.json();
                    return { data: result };
                } catch (error) {
                    console.error('Fetch POST error:', error);
                    throw error;
                }
            },
            get: async (url) => {
                try {
                    const fullUrl = API_BASE_URL + (url.startsWith('/') ? url : '/' + url);
                    console.log('Fetch GET URL:', fullUrl);
                    const response = await fetch(fullUrl, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const result = await response.json();
                    return { data: result };
                } catch (error) {
                    console.error('Fetch GET error:', error);
                    throw error;
                }
            }
        };
        console.log('Используем fetch fallback для API');
    }
    
    return apiClient;
}

const safeRequest = async (requestFn) => {
    try {
        const client = await getApiClient();
        const response = await requestFn(client);
        return response.data;
    } catch (error) {
        console.error('API Error:', error);
        return { error: error.message, result: null };
    }
};

// ============================================================
// API ФУНКЦИИ ДЛЯ СТРАНИЦЫ УВЕДОМЛЕНИЙ
// ============================================================

export const getAllUsers = async (memberId) => {
    return safeRequest(async (client) => {
        return await client.post("/indexReport/getAllUsers.php", {
            memberId: memberId,
        });
    });
};

export const addUsersInChat = async (memberId, selectedUsers) => {
    return safeRequest(async (client) => {
        return await client.post("/indexReport/addUsersInChat.php", {
            memberId: memberId,
            selectedUsers: selectedUsers
        });
    });
};

// ============================================================
// API ФУНКЦИИ ДЛЯ СТРАНИЦЫ НАСТРОЕК КНОПОК
// ============================================================

export const getAllButtons = async (memberId) => {
    return safeRequest(async (client) => {
        return await client.post("/button-crud/getAllButtons.php", {
            memberId: memberId,
        });
    });
};

export const getTemplate = async (memberId) => {
    return safeRequest(async (client) => {
        return await client.get("/button-crud/getTemplate.php");
    });
};

export const getMoreButtons = async (memberId) => {
    return safeRequest(async (client) => {
        return await client.post("/button-crud/getMoreButtons.php", {
            memberId: memberId,
        });
    });
};

export const saveBtnSettings = async (memberId, current_button, activeButtonId) => {
    return safeRequest(async (client) => {
        return await client.post("/button-crud/saveBtnSettings.php", {
            memberId: memberId,
            btnSettings: current_button,
            activeButtonId: activeButtonId
        });
    });
};

export const deleteButton = async (memberId, activeButtonId) => {
    return safeRequest(async (client) => {
        return await client.post("/button-crud/deleteButton.php", {
            memberId: memberId,
            activeButtonId: activeButtonId
        });
    });
};

export const createButtonInCrm = async (memberId, activeButtonId, domen, btnSettings) => {
    return safeRequest(async (client) => {
        return await client.post("/button-crud/createButtonInCrm.php", {
            memberId: memberId,
            activeButtonId: activeButtonId,
            domen: domen,
            btnSettings: btnSettings
        });
    });
};

export const deleteButtonInCrm = async (memberId, activeButtonId, domen) => {
    return safeRequest(async (client) => {
        return await client.post("/button-crud/deleteButtonInCrm.php", {
            memberId: memberId,
            activeButtonId: activeButtonId,
            domen: domen,
        });
    });
};

export const createButtonInChat = async (memberId, activeButtonId, btnSettings) => {
    return safeRequest(async (client) => {
        return await client.post("/chatHandlers/createButtonInChat.php", {
            memberId: memberId,
            activeButtonId: activeButtonId,
            btnSettings: btnSettings
        });
    });
};

export const deleteButtonInChat = async (memberId, activeButtonId) => {
    return safeRequest(async (client) => {
        return await client.post("/chatHandlers/deleteButtonInChat.php", {
            memberId: memberId,
            activeButtonId: activeButtonId
        });
    });
};

export const getButtonData = async (memberId, button) => {
    return safeRequest(async (client) => {
        return await client.post("/button-crud/getButtonData.php", {
            memberId: memberId,
            button_ID: button.ID
        });
    });
};

// ============================================================
// API ФУНКЦИИ ДЛЯ ДЕЙСТВИЙ
// ============================================================

export const getAllEntitys = async (memberId) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getAllEntitys.php", {
            memberId: memberId,
        });
    });
};

export const getBPforEntity = async (memberId, current_button) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getBPforEntity.php", {
            memberId: memberId,
            current_button: current_button
        });
    });
};

export const getChainBpDefinitions = async (memberId, entity, bpIds) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getChainBpDefinitions.php", {
            memberId: memberId,
            entity: entity,
            bpIds: bpIds
        });
    });
};

export const getSubscriptionStatus = async (memberId, force = false) => {
    return safeRequest(async (client) => {
        return await client.post("/subscription/status.php", {
            memberId: memberId,
            force: force
        });
    });
};

export const startTrial = async (memberId, contact) => {
    return safeRequest(async (client) => {
        return await client.post("/billing/trial.php", {
            memberId: memberId,
            contact: contact
        });
    });
};

export const getDocumentsforEntity = async (memberId, current_button) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getDocumentsforEntity.php", {
            memberId: memberId,
            current_button: current_button
        });
    });
};

export const getAllLists = async (memberId, current_button) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getAllLists.php", {
            memberId: memberId,
            current_button: current_button
        });
    });
};

export const getListFields = async (memberId, current_button) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getListFields.php", {
            memberId: memberId,
            list: current_button
        });
    });
};

export const getEntityFieldsForList = async (memberId, current_button) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getEntityFieldsForList.php", {
            memberId: memberId,
            current_button: current_button
        });
    });
};

export const getCrmFieldsLink = async (memberId, current_button) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getCrmFieldsLink.php", {
            memberId: memberId,
            current_button: current_button
        });
    });
};

// ============================================================
// API ФУНКЦИИ ДЛЯ БП ИЗ ЛЕНТЫ НОВОСТЕЙ
// ============================================================

export const getFeedWorkflowsList = async (memberId) => {
    return safeRequest(async (client) => {
        return await client.post("/button-actions/getFeedWorkflowsList.php", {
            memberId: memberId,
        });
    });
};