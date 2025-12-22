let api = axios.create({ baseURL: 'https://app.overplan.ru/applications/crmButtons/3/handlers'});

export const getUsersAndBotReport = async (memberId) => {
    let users = await api.get('/getUsersAndBotReport.php', {
        params: {
            memberId: memberId
        }
    });
    return users.data;
}

export const addUserInChat = async (user, memberId) => {
    let add = await api.post('/addUserInReportChat.php', {
        user: user,
        memberId: memberId
    })
    return add.data
}

export const removeUserFromChat = async (user, memberId) => {
    let remove = await api.post('removeUserFromChat.php', {
        user: user,
        memberId: memberId
    })
    return remove.data
}

export const checkApplicationsReport = async (memberId) => {
    let check = await api.get('/checkApplicationsReport.php', {
        params: {
            memberId: memberId
        }
    })
    return check.data
}

export const turnApllicationReport = async (reportCheck) => {
    let turnReport = await api.post('/turnOffReport.php', {
        reportCheck: reportCheck,
        memberId: memberId
    })
    return turnReport.data
}