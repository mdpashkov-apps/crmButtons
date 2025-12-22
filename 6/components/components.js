import {getUsersAndBotReport, addUserInChat, removeUserFromChat, checkApplicationsReport, turnApllicationReport} from '../js/app.js';

let multiselect = window.VueMultiselect.default;

export const applicationsReport = {
    template: `
        <div class="applicationsReport" style="width: 400px;">
        <label class="form-check-label" for="" style="margin-bottom: 5px;">Добавьте пользователей в чат для уведомлений:</label>
        <multiselect v-model="users" placeholder="Выберите, кого добавить в чат" label="fullName" deselect-label="Убрать" select-label="Выбрать" selected-label="" open-direction="bottom" :options="userList" :multiple="true" :taggable="false" :close-on-select="false" track-by="ID" :searchable="true" :limit="2" @select="addUser" @remove="removeUser">
            <span slot="noOptions">
                Такого пользователя нет
            </span>
            <span slot="noResult">
                Такого пользователя нет
            </span>
        </multiselect>
        <div class="addApplicationsReportBot" style="margin-top: 10px;">
            <button class="btn" :class="{'btn-success':buttonReport.check == '0' , 'btn-danger': buttonReport.check == '1' }" @click="turnReport" >{{buttonReport.text}}</button>
        </div>
    </div>
    `,
    components: {
        multiselect
    },
    data() {
        return {
            userList: [],
            users: [],
            botCheck: false,
            botCheckLabel: '',
            buttonReport: {
                check: '1',
                text: 'Удалить чат-бота'
            }
        }
    },
    methods: {
        addUser: async function (user) {
            let addResp = await addUserInChat(user, window.memberId);
        },
        removeUser: async function(user) {
            let removeResp = await removeUserFromChat(user, window.memberId);
        },
        turnReport: async function () {
            this.buttonReport.check = this.buttonReport.check == '1' ? '0' : '1'; 
            let turnReport = await turnApllicationReport(this.buttonReport.check, window.memberId);
            this.buttonReport.text = turnReport.result;
        }
    },
    async mounted() {
        let checkReport = await checkApplicationsReport(window.memberId);
        this.buttonReport.check = checkReport.result.check;
        this.buttonReport.text = checkReport.result.checkMessage;
        let users = await getUsersAndBotReport(window.memberId);
        this.userList = users.result.users.map((user) => {
            user.fullName = user.LAST_NAME + " " + user.NAME;
            return user;
        });
        this.users = users.result.users.filter((user) => {
            return  users.result.userInChat.includes(Number(user.ID))});
    },
}