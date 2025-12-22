<html>
	<?$versions = basename(dirname(__DIR__));?>
<head>
	<link rel="stylesheet" href="../<?=$versions?>/buttonHandlers/style.css">
	<link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.css">
	<script src="https://gcore.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://gcore.jsdelivr.net/npm/vue/dist/vue.min.js"></script>
    <!-- <script src="https://unpkg.com/vue-multiselect@2.1.0"></script> -->
	<script src="https://unpkg.com/vue-multiselect@2.1.0/dist/vue-multiselect.min.js"></script>

	<script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>	
	<?
		$path = pathinfo(__DIR__, PATHINFO_DIRNAME);
		include_once($path . '/overCRest.php');
		overCRest::setCurrentBitrix24($_REQUEST['member_id']);
		$idEntity = explode('|', $_SERVER['SCRIPT_NAME'])[1];
		$idEntityCRM = (array)json_decode($_REQUEST['PLACEMENT_OPTIONS']);
		$idEntityCRM = $idEntityCRM['ENTITY_VALUE_ID'];
	?>	
	<script>
		window.memberId = '<?echo $_REQUEST['member_id']?>'	
		window.domain = '<?echo $_REQUEST['DOMAIN']?>'		
		window.idEntity = '<?echo $idEntity?>'
		window.idEntityCRM = '<?echo $idEntityCRM?>'
		window.versions = '<?echo $versions?>'
		BX24.callMethod('user.current', {}, function(res){
			window.userId = res.answer.result.ID
		});
	</script>
	<div id="app">
		<div v-if="loader" class="modal-mask">
            <div class="modal-wrapper">
                <div class="loader"></div>
            </div>
        </div>	
		<button v-if="businessProcessWithParameters.length == 0" class="btn" :style="styleObject" @click="performAnAction">
			<span v-if="buttonFields.usingTheIcon_FIELDS">
				{{buttonFields.iconOnTheButton_FIELDS}}
			</span>
			{{buttonFields.textOnTheButton_FIELDS}}
		</button>		
		<div class="modal" ref="myElement" v-if="businessProcessWithParameters.length != 0">
			<div class="modal__inner">
				<h2>{{businessProcessWithParameters[0]['NAME']}}</h2>
				<div class="modal-fields">				
					<!-- строка -->
					<div class="inner-field" v-for="(prop,propId) in businessProcessWithParameters[0]['PARAMETERS']">
						<div v-if="prop.Type == 'string' && prop.Multiple == '0'" class="modal-fields__field field" data-type="string" data-multiple="false" data-id="" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<input v-model="prop.value" type="text" class="field__input">
						</div>										
						<!-- строка множественное -->
						<div v-if="prop.Type == 'string' && prop.Multiple == '1'" class="modal-fields__field field" data-type="string" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field__multiple">
								<div v-for="(valueMultiple,index) in prop.value" class="field-row">
									<input type="text" v-model="prop.value[index]" class="field__input">
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?=$versions?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)" >Добавить еще</button>
						</div>
						<!-- число -->						
						<div v-if="prop.Type == 'double' && prop.Multiple == '0'" class="modal-fields__field field" data-type="double" data-multiple="false" data-id="" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<input type="number" v-model="prop.value" class="field__input">
						</div>						
						<!-- число множественное -->
						<div v-if="prop.Type == 'double' && prop.Multiple == '1'" class="modal-fields__field field" data-type="double" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field__multiple">
								<div v-for="(valueMultiple,index) in prop.value" class="field-row">
									<input type="number" v-model="prop.value[index]" class="field__input">
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?=$versions?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)">Добавить еще</button>
						</div>						
						<!-- целое число -->
						<div v-if="prop.Type == 'int' && prop.Multiple == '0'" class="modal-fields__field field" data-type="int" data-multiple="false" data-id="" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<input type="number" v-model="prop.value" class="field__input">
						</div>						
						<!-- целое число множественное -->
						<div v-if="prop.Type == 'int' && prop.Multiple == '1'" class="modal-fields__field field" data-type="int" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field__multiple">
								<div v-for="(valueMultiple,index) in prop.value" class="field-row">
									<input type="number" v-model="prop.value[index]" class="field__input">
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?=$versions?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)">Добавить еще</button>
						</div>						
						<!-- E-mail множественное-->
						<!-- <div v-if="prop.Type == 'email'" class="modal-fields__field field" data-type="email" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field__multiple">
								<div class="field-row" v-for="(valueMultiple,index) in prop.value">
									<input type="email" v-model="prop.value[index]" class="field__input">
									<multiselect v-model="prop.additionalField[index]" placeholder="Выберите почту" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="options.email" :multiple="false" :searchable="false" :close-on-select="true" :limit="1" :allow-empty="false">
										<span slot="noResult">
											Такого варианта нет
										</span>
									</multiselect>
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?/*?><?=$versions?><?*/?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)">Добавить еще</button>
						</div> -->
						<!-- Телефон множественное-->
						<!-- <div v-if="prop.Type == 'phone'" class="modal-fields__field field" data-type="phone" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field__multiple">
								<div class="field-row" v-for="(valueMultiple,index) in prop.value">
									<input type="tel" v-model="prop.value[index]" class="field__input">
									<multiselect v-model="prop.additionalField[index]" placeholder="Выберите телефон" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="options.phone" :multiple="false" :taggable="false" :allow-empty="false"
									:searchable="false" :close-on-select="true" :limit="1">
										<span slot="noResult">
											Такого варианта нет
										</span>
									</multiselect>
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?/*?><?=$versions?><?*/?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)">Добавить еще</button>
						</div> -->
						<!-- Ссылка -->
						<!-- <div v-if="prop.Type == 'web'" class="modal-fields__field field" data-type="web" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field__multiple">
								<div class="field-row" v-for="(valueMultiple,index) in prop.value">
									<input type="text" v-model="prop.value[index]" class="field__input">
									<multiselect v-model="prop.additionalField[index]" placeholder="Выберите сайт" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="options.site" :multiple="false" :taggable="false" :close-on-select="true" :limit="1" :searchable="false" :allow-empty="false">
										<span slot="noResult">
											Такого варианта нет
										</span>
									</multiselect>
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?/*?><?=$versions?><?*/?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)">Добавить еще</button>
						</div> -->
						<!-- Текст -->
						<div v-if="prop.Type == 'text' && prop.Multiple == '0'" class="modal-fields__field field" data-type="text" data-multiple="false" data-id="" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<textarea v-model="prop.value"></textarea>
						</div>						
						<!-- Текст -->
						<div v-if="prop.Type == 'text' && prop.Multiple == '1'" class="modal-fields__field field" data-type="text" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field__multiple">
								<div class="field-row" v-for="(valueMultiple,index) in prop.value">
									<textarea v-model="prop.value[index]"></textarea>
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?=$versions?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)">Добавить еще</button>
						</div>						
						<!-- да/нет -->
						<div v-if="prop.Type == 'bool' && prop.Multiple == '0'" class="modal-fields__field field" data-type="bool" data-multiple="false" data-id="" :data-required="prop.Required">
							<input v-model="prop.value" class="field__input custom-checkbox" :id="propId" type="checkbox">
							<label :for="propId" class="field__label">
								{{prop.Name}}<span class="field__label--required">*</span>
							</label>
						</div>						
						<!-- да/нет -->
						<div v-if="prop.Type == 'bool' && prop.Multiple == '1'" class="modal-fields__field field" data-type="bool" data-id="" data-multiple="true" :data-required="prop.Required">
							<label  class="field__label">
								{{prop.Name}}
								<span class="field__label--required">*</span>
							</label>
							<div class="field__multiple">
								<div class="field-row" v-for="(valueMultiple,index) in prop.value">
									<input v-model="prop.value[index]" 
									:id="propId+index" 
									class="field__input custom-checkbox" type="checkbox">
									<label :for="propId+index" class="field__label">
										<span v-if="prop.value[index]">
											Да
										</span>
										<span v-else>
											Нет
										</span>
									</label>
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?=$versions?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)">Добавить еще</button>
						</div>						
						<!-- селект -->
						<div v-if="prop.Type == 'select' && prop.Multiple == '0'" class="modal-fields__field field" data-type="select" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<multiselect v-model="prop.value" placeholder="Выберите значение" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="prop.Options" :multiple="false" :taggable="false" :close-on-select="true" :limit="1">
								<span slot="noResult">
									Такого варианта нет
								</span>
							</multiselect>
						</div>						
						<!-- селект множественный -->
						<div v-if="prop.Type == 'select' && prop.Multiple == '1'" class="modal-fields__field field" data-type="select" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<multiselect v-model="prop.value" placeholder="Выберите значение" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="prop.Options" :multiple="true" :taggable="false" :close-on-select="false" :limit="1">
								<span slot="noResult">
									Такого варианта нет
								</span>
							</multiselect>
						</div>						
						<!-- пользователи -->
						<div v-if="prop.Type == 'user' && prop.Multiple == '0'" class="modal-fields__field field" data-type="user" data-id="" data-multiple="false" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<multiselect v-model="prop.value" placeholder="Выберите пользователя" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="options.user" :multiple="false" :taggable="false" :close-on-select="true" :limit="1">
								<span slot="noResult">
									Такого варианта нет
								</span>
							</multiselect>
						</div>						
						<!-- пользователи множественный -->
						<div v-if="prop.Type == 'user' && prop.Multiple == '1'" class="modal-fields__field field" data-type="user" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<multiselect v-model="prop.value" placeholder="Выберите пользователей" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="options.user" :multiple="true" :taggable="false" :close-on-select="false" :limit="1">
								<span slot="noResult">
									Такого варианта нет
								</span>
							</multiselect>
						</div>						
						<!-- Дата-время -->
						<div v-if="prop.Type == 'datetime' && prop.Multiple == '0'" class="modal-fields__field field" data-type="datetime" data-multiple="false" data-id="" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field-row">
								<input class="field__input" v-model="prop.value" type="datetime-local">
								<multiselect v-model="prop.additionalField" placeholder="Выберите часовой пояс" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="options.date" :multiple="false" :taggable="false" :close-on-select="true" :limit="1" :allow-empty="false" :allow-empty="false">
									<span slot="noResult">
										Такого варианта нет
									</span>
								</multiselect>
							</div>
						</div>						
						<!-- Дата-время -->
						<div v-if="prop.Type == 'datetime' && prop.Multiple == '1'" class="modal-fields__field field" data-type="datetime" data-id="" data-multiple="true" :data-required="prop.Required">
							<label class="field__label">{{prop.Name}}<span class="field__label--required">*</span></label>
							<div class="field__multiple">
								<div class="field-row" v-for="(valueMultiple,index) in prop.value">
									<input v-model="prop.value[index]" class="field__input" type="datetime-local">
									<multiselect v-model="prop.additionalField[index]" placeholder="Выберите часовой пояс" label="name" track-by="value" deselect-label="Убрать" select-label="Выбрать" selected-label="" :options="options.date" :multiple="false" :taggable="false" :close-on-select="true" :limit="1" :allow-empty="false">
										<span slot="noResult">
											Такого варианта нет
										</span>
									</multiselect>
									<img class="field__delete" @click="delteFields(propId,index)" src="../<?=$versions?>/buttonHandlers/icon/close.svg">
								</div>
							</div>
							<button class="fields__button" @click="addFields(propId)">Добавить еще</button>
						</div>
					</div>
				</div>
				<button class="send-button" @click="parameterValidation">
					Запустить БП
				</button>
			</div>
		</div>
	</div>
	<style>
		/* подключенные шрифты */
		@font-face {
			font-family: 'Gilroy';
			src: url(../<?=$versions?>/buttonHandlers/fonts/gilroy-regular.ttf);
		}

		@font-face {
			font-family: 'Gilroy-medium';
			src: url(../<?=$versions?>/buttonHandlers/fonts/gilroy-medium.ttf);
		}

		@font-face {
			font-family: 'Gilroy-bold';
			src: url(../<?=$versions?>/buttonHandlers/fonts/gilroy-bold.ttf);
		}
		*{
			font-family: 'Gilroy', sans-serif;
		}
		.custom-checkbox:checked+label::before {
			background-image: url("../<?=$versions?>/buttonHandlers/icon/icon-check.svg");
		}
	</style>
	<script src="https://kit.fontawesome.com/c9f5eeb571.js" crossorigin="anonymous"></script>
	<script type="module" src="../<?=$versions?>/buttonHandlers/script.js"></script>
</body>
</html>