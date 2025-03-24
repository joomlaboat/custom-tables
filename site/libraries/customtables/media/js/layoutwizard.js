/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/js/layoutwizard.js
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2025. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

let tableselector_id = "";
let field_box_id = "";
let tableselector_obj = null;
let current_table_id = 0;
let wizardFields = [];
let wizardLayouts = [];
let joomlaVersion = 3;
let languages = [];
let custom_fields = [];

//Used in layouteditor.php
function loadLayout(version) {

	if (typeof version === 'number' && !isNaN(version)) {
		joomlaVersion = version;
	} else {
		let v1 = version.split('.');
		joomlaVersion = parseInt(v1[0]);
	}

	let obj = document.getElementById("allLayoutRaw");

	if (obj)
		wizardLayouts = JSON.parse(obj.innerHTML);
}

function openLayoutWizard() {
	FillLayout();
}

//Used in layouteditor.php
function loadFields(tableselector_id_, field_box_id_) {
	tableselector_id = tableselector_id_;
	field_box_id = field_box_id_;
	tableselector_obj = document.getElementById(tableselector_id);
	loadFieldsUpdate();
}

function loadFieldsUpdate() {
	let tableid = tableselector_obj.value;
	if (tableid !== current_table_id)
		loadFieldsData(tableid);
}

function loadFieldsData(tableid) {
	current_table_id = 0;
	tableid = parseInt(tableid);
	if (isNaN(tableid) || tableid === 0)
		return;//table not selected

	let url = '';

	if (window.Joomla instanceof Object) {
		const parts = location.href.split("/administrator/");
		url = parts[0] + "/administrator/index.php?option=com_customtables&view=api&frmt=json&task=getfields&tableid=" + tableid;
	} else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
		let parts = location.href.split("wp-admin/admin.php?");
		url = parts[0] + 'wp-admin/admin.php?page=customtables-api-fields&table=' + tableid;
	} else {
		alert('loadTags: CMS Not Supported.');
		return;
	}

	if (typeof fetch === "function") {

		fetch(url, {method: 'GET', mode: 'no-cors', credentials: 'same-origin'}).then(function (response) {

			if (response.ok) {
				response.json().then(function (json) {

					wizardFields = json;
					current_table_id = tableid;
					updateFieldsBox();
				});
			} else {
				console.log('Network request for products.json failed with response ' + response.status + ': ' + response.statusText);
			}

		}).catch(function (err) {
			console.log('Fetch Error :', err);
		});
	} else {
		//for IE
		let params = "";
		let http = CreateHTTPRequestObject();   // defined in ajax.js

		if (http) {
			http.open("GET", url, true);
			http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			http.onreadystatechange = function () {
				if (http.readyState === 4) {
					let res = http.response;

					console.log("layoutwizard:98 result:" + res);

					wizardFields = JSON.parse(res);
					current_table_id = tableid;
					updateFieldsBox();
				}
			};
			http.send(params);
		}
	}
}

function updateFieldsBox() {

	//let result=renderFieldsBox();
	//result+='<p>Position cursor to the code editor where you want to insert a new dynamic tag and click on the Tag Button.</p>';
	//field_box_obj.innerHTML='';//<div class="dynamic_values">'+result+'</div>';
}

function renderTabs(tabSetId, tabs) {

	if (typeof Joomla !== 'undefined') {
		return renderTabsJoomla(tabSetId, tabs);
	} else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
		return renderTabsWordPress(tabSetId, tabs);
	} else {
		console.log('CMS not supported.');
		return 'CMS not supported.';
	}
}

function activateTabsWordPress(tabClassName) {

	const tabs = document.querySelectorAll('[data-tabs=".gtabs.' + tabClassName + '"]');
	tabs.forEach(tab => {
		tab.addEventListener('click', function () {
			// Activate the clicked tab and deactivate others
			tabs.forEach(t => t.classList.remove('nav-tab-active'));
			this.classList.add('nav-tab-active');

			const tabsContentContainer = document.querySelectorAll('.' + tabClassName);
			if (tabsContentContainer.length > 0) {
				const tabsContentDivs = tabsContentContainer[0].querySelectorAll('.gtab');

				tabsContentDivs.forEach(t => t.classList.remove('active'));
				//this.classList.add('active');

				let tabId = this.dataset.tab;
				let tabDiv = document.querySelectorAll(tabId);
				tabDiv.forEach(t => t.classList.add('active'));
			}
		});
	});
}

function renderTabsWordPress(tabSetId, tabs) {

	let buttons = '';
	let divs = '';
	for (let i = 0; i < tabs.length; i++) {
		let tab = tabs[i];

		let cssclass_buttons = "";
		let cssclass_divs = "";
		if (i === 0) {
			cssclass_buttons = ' nav-tab-active';
			cssclass_divs = ' active';
		}
		buttons += '<button data-toggle="tab" data-tabs=".gtabs.' + tabSetId + '" data-tab=".' + tab.id + '-tab' + (i + 1) + '" class="nav-tab' + cssclass_buttons + '" >' + tab.title + '</button>';
		divs += '<div class="gtab' + cssclass_divs + ' ' + tab.id + '-tab' + (i + 1) + '" style="margin-left:-20px;">' + tab.content + '</div>';
	}
	return '<h2 class="nav-tab-wrapper wp-clearfix">' + buttons + '</h2><div class="gtabs ' + tabSetId + '">' + divs + '</div>';
}

function renderTabsJoomla(tabSetId, tabs) {
	// Tabs is the array of tab elements [{"title":"Tab Title","id":"Tab Name","content":"Tab Content"}...]

	if (joomlaVersion < 4) {
		let result_li = '';
		let result_div = '';
		//let activeTabSet=true;

		for (let i = 0; i < tabs.length; i++) {
			let tab = tabs[i];

			let cssclass = "";
			if (i === 0)
				cssclass = "active";

			result_li += '<li' + (cssclass !== '' ? ' class="' + cssclass + '"' : '') + '><a href="#' + tab.id + '" onclick="resizeModalBox();" data-toggle="tab">' + tab.title + '</a></li>';
			result_div += '<div id="' + tab.id + '" class="tab-pane' + (i === 0 ? ' active' : '') + '">' + tab.content + '</div>';
		}
		return '<ul class="nav nav-tabs" >' + result_li + '</ul>\n\n<div class="tab-content" id="' + tabSetId + '">' + result_div + '</div>';
	} else {
		//let result_li = '';
		let result_div = '';

		for (let i = 0; i < tabs.length; i++) {
			let tab = tabs[i];

			let cssClass = "";
			if (i === 0)
				cssClass = "active";

			result_div += '<joomla-tab-element' + (i === 0 ? ' active' : '') + ' style="height:fit-content;overflow-y: auto;overflow-x: none;" id="' + tab.id + '" name="' + tab.title + '">' + tab.content + '</joomla-tab-element>';
		}

		//let result_div_li = '<div role="tablist">' + result_li + '</div>';
		return '<joomla-tab id="' + tabSetId + '" orientation="horizontal" recall="" breakpoint="768">' + result_div + '</joomla-tab>';
	}
}

function replaceOldFieldTitleTagsWithTwigStyle() {

	let editor = getActiveEditor(-1);
	let documentText = editor.getValue();
	let count = 0;
	let changesMade = false;

	//Titles
	for (let i = 0; i < wizardFields.length; i++) {
		let oldFieldTag = '*' + wizardFields[i].fieldname + '*';
		if (documentText.indexOf(oldFieldTag) !== -1)
			count += 1;
	}

	if (count > 0) {
		if (confirm("Found " + count + " old field title tags. Would you like to replace them with Twig style tags?") === true) {
			for (let i = 0; i < wizardFields.length; i++) {
				let oldFieldTag = '*' + wizardFields[i].fieldname + '*';
				let newFieldTag = '{{ ' + wizardFields[i].fieldname + '.title }}';
				documentText = documentText.replace(oldFieldTag, newFieldTag)
				changesMade = true;
			}
		}
	}

	count = 0;

	//values
	for (let i = 0; i < wizardFields.length; i++) {
		let oldFieldTag = '|' + wizardFields[i].fieldname + '|';
		if (documentText.indexOf(oldFieldTag) !== -1)
			count += 1;
	}

	if (count > 0) {
		if (confirm("Found " + count + " old field value tags. Would you like to replace them with Twig style tags?") === true) {
			for (let i = 0; i < wizardFields.length; i++) {
				let oldFieldTag = '|' + wizardFields[i].fieldname + '|';
				let newFieldTag = '{{ ' + wizardFields[i].fieldname + '.value }}';
				documentText = documentText.replace(oldFieldTag, newFieldTag)
				changesMade = true;
			}
		}
	}

	if (changesMade === true) {
		editor.setValue(documentText);
		editor.refresh();
	}
}

function renderFieldsBox() {
	//1 - Simple Catalog
	//2 - Edit Form
	//3 - Record Link
	//4 - Details
	//5 - Catalog Page
	//6 - Catalog Item
	//7 - Email Message
	//8 - XML File
	//9 - CSV File
	//10 - JSON - File

	let tabs = [];

	current_table_id = parseInt(current_table_id);
	if (isNaN(current_table_id) || current_table_id === 0) {
		return '<div class="FieldTagWizard"><p>Table not selected.</p></div>';
	}

	const l = wizardFields.length;

	if (l === 0) {
		return '<div class="FieldTagWizard"><p>There are no fields in selected table.</p></div>';
	} else {
		replaceOldFieldTitleTagsWithTwigStyle();
	}

	const a = [1, 3, 4, 6, 7, 8, 9, 10];//Layout Types that may have Field Values.
	const fieldtypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'filebox', 'dummy'];

	if (a.indexOf(current_layout_type) !== -1) {
		tabs.push({
			'id': 'layouteditor_fields_value', 'title': 'Field Values',
			'content': '<p>Dynamic Field Tags that produce Field Values:</p>' + renderFieldTags('{{ ', '', ' }}', ['dummy'], 'valueparams') //skip 'dummy'
		});
	}

	//Any Layout Type
	tabs.push({
		'id': 'layouteditor_fields_titles', 'title': 'Field Titles',
		'content': '<p>Dynamic Field Tags that produce Field Titles (Language dependable):</p>' + renderFieldTags('{{ ', '.title', ' }}', [], 'titleparams')
	});
	tabs.push({
		'id': 'layouteditor_fields_labels', 'title': 'Field Labels',
		'content': '<p>Dynamic Field Tags that produce Field Title Label HTML tag (Language dependable):</p>' + renderFieldTags('{{ ', '.label', ' }}', [], 'titleparams')
	});

	if (a.indexOf(current_layout_type) !== -1) {
		tabs.push({
			'id': 'layouteditor_fields_purevalue', 'title': 'Pure Values',
			'content': '<p>Dynamic Field Tags that returns pure Field Values (as it stored in database):</p>' + renderFieldTags('{{ ', '.value', ' }}', ['string', 'md5', 'changetime', 'creationtime', 'lastviewtime', 'viewcount', 'id', 'phponadd', 'phponchange', 'phponview', 'server', 'multilangstring', 'text', 'multilangtext', 'int', 'float', 'email', 'date', 'filelink', 'creationtime', 'dummy'], '')
		});

		tabs.push({
			'id': 'layouteditor_fields_ajaxedit', 'title': 'Edit',
			'content': '<p>Renders input/select box for selected field. It works in all types of layout except Edit Form:</p>' + renderFieldTags('{{ ', '.edit', ' }}', fieldtypes_to_skip, '')
		});
	}

	if (current_layout_type === 2) {
		let fieldtypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'filebox', 'dummy'];

		let label = '<p>Dynamic Field Tags that renders an input field where the user can enter data.<span style="font-weight:bold;color:darkgreen;">(more <a href="https://ct4.us/docs-category/field-types/" target="_blank">here</a>)</span></p>';
		tabs.push({
			'id': 'layouteditor_fields_edit', 'title': 'Input/Edit',
			'content': label + renderFieldTags('{{ ', '.edit', ' }}', fieldtypes_to_skip, 'editparams')
		});


		tabs.push({
			'id': 'layouteditor_fields_valueineditform', 'title': 'Field Values',
			'content': '<p>Dynamic Field Tags that produce Field Values (if the record is alredy created ID!=0):</p>' + renderFieldTags('{{ ', '', ' }}', ['dummy'], 'valueparams')
		});
	}


	if (tabs.length > 0)
		return renderTabs('layouteditor_fields', tabs);
	else
		return '<div class="FieldTagWizard"><p>No Field Tags available for this Layout Type</p></div>';
}

function findFieldObjectByName(fieldname) {

	let l = wizardFields.length;
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];
		if (field.fieldname === fieldname)
			return field;
	}
	return null;
}

function renderFieldTags(startChar, postfix, endChar, fieldtypes_to_skip, param_group) {
	let result = '';
	const l = wizardFields.length;

	for (let index = 0; index < l; index++) {
		const field = wizardFields[index];

		if (fieldtypes_to_skip.indexOf(field.type) === -1) {
			let t = field.fieldname + postfix;
			let p = 0;
			let alt = field.fieldtitle;
			let button_value = "";
			const typeparams = findTheType(field.type);
			if (typeparams != null) {

				const type_att = typeparams["@attributes"];
				alt += ' (' + type_att.label + ')';

				if (param_group !== '') {
					const param_group_object = typeparams[param_group];
					if (typeof (param_group_object) != "undefined") {
						const params = getParamOptions(param_group_object.params, 'param');
						p = params.length;

						if (p > 0)
							t += '(<span>Params</span>)';
					}
				}

				button_value = startChar + t + endChar;
			} else {
				alt += ' (UNKNOWN FIELD TYPE)';

				button_value = '<span class="text_error">' + startChar + t + endChar + '</span>';
			}

			result += '<div style="vertical-align:top;display:inline-block;">';
			result += '<div style="display:inline-block;">';

			if (joomlaVersion < 4)
				result += '<a href=\'javascript:addFieldTag("' + startChar + '","' + postfix + '","' + endChar + '","' + btoa(field.fieldname) + '",' + p + ');\' class="btn" title="' + alt + '">' + button_value + '</a>';
			else
				result += '<a href=\'javascript:addFieldTag("' + startChar + '","' + postfix + '","' + endChar + '","' + btoa(field.fieldname) + '",' + p + ');\' class="btn-primary" title="' + alt + '">' + button_value + '</a>';

			result += '</div>';
			result += '</div>';
		}
	}
	return result;
}

function getParamGroup(tagstartchar, postfix, tagendchar) {
	let param_group = '';
	const a = [1, 3, 4, 6, 7, 8, 9, 10];

	if (postfix === '.title' || (current_layout_type !== 5 && tagstartchar === '*' && tagendchar === '*'))
		param_group = 'titleparams';
	else if (postfix === '.edit' || current_layout_type === 2)
		param_group = 'editparams';
	else if (a.indexOf(current_layout_type) !== -1 && ((tagstartchar === '[' && tagendchar === ']') || (tagstartchar === '{{ ' && tagendchar === ' }}')))
		param_group = 'valueparams';

	return param_group;
}

function showModalTagsList(e) {
	document.getElementById("layouteditor_modal_content_box").innerHTML = do_render_current_TagSets();
	showModal();
}

function showModalDependenciesList(e) {
	document.getElementById("layouteditor_modal_content_box").innerHTML = document.getElementById("dependencies_content").innerHTML;
	showModal();
}

function showModalFieldTagsList(e) {

	document.getElementById("layouteditor_modal_content_box").innerHTML = '<div class="dynamic_values">' + renderFieldsBox() + '</div>';
	showModal();
}

function showModalFieldTagForm(tagStartChar, postfix, tagEndChar, tag, top, left, line, positions, isNew) {
	let modalContentObject = document.getElementById("layouteditor_modal_content_box");
	let paramValueString = "";
	let tag_pair = parseQuote(tag, '(', false)

	if (tag_pair.length > 1) {

		let sub_tag_pair = parseQuote(tag_pair[0], '.', false);
		if (sub_tag_pair.length > 1) {
			temp_params_tag = sub_tag_pair[0].trim();
			postfix = '.' + sub_tag_pair[1].trim();
		} else {
			temp_params_tag = tag_pair[0].trim();
		}

		paramValueString = findTagParameter(tag);
	} else {
		tag_pair = parseQuote(tag, ':', false);
		if (tag_pair.length > 1) {
			if (tag_pair[0] === "_value" || tag_pair[0] === "_edit") {

				if (tag_pair[0] === "_value")
					postfix = '.value';
				else if (tag_pair[0] === "_edit")
					postfix = '.edit';

				temp_params_tag = tag_pair[1].trim();

				if (tag_pair.length === 2)
					paramValueString = tag_pair[1];

			} else {
				temp_params_tag = tag_pair[0].trim();

				let pos1 = tag.indexOf(":");
				paramValueString = tag.substring(pos1 + 1, tag.length);
			}
		} else {

			tag_pair = parseQuote(tag, '.', false);
			if (tag_pair.length > 1) {
				temp_params_tag = tag_pair[0].trim();
				postfix = '.' + tag_pair[1].trim();
			} else {
				temp_params_tag = tag.trim();
			}
		}
	}

	const field = findFieldObjectByName(temp_params_tag);
	if (field == null) {
		modalContentObject.innerHTML = '<p>Cannot find the field. Probably the field does not belong to selected table.</p>';
		showModal();
		return;
	}

	const param_group = getParamGroup(tagStartChar, postfix, tagEndChar);

	if (param_group === '') {
		modalContentObject.innerHTML = '<p>Something went wrong. Field Type Tag should not have any parameters in this Layout Type. Try to reload the page.</p>';
		showModal();
		return;
	}

	const fieldTypeObject = findTheType(field.type);
	if (fieldTypeObject === null) {
		modalContentObject.innerHTML = "<p>Something went wrong. Field Type Tag doesn't have any parameters. Try to reload the page.</p>";
		showModal();
		return;
	}
	const fieldType_att = fieldTypeObject["@attributes"];
	const group_params_object = fieldTypeObject[param_group];

	if (!group_params_object || !group_params_object.params) {
		let cursor_from = {line: line, ch: positions[0]};
		let cursor_to = {line: line, ch: positions[1]};

		let tagPair1 = parseQuote(tag, '(', false);
		let tagPair2 = parseQuote(tagPair1[0], '.', false);
		if ((tagPair1.length > 1 || tagPair2.length > 1) && tagPair2[0] + postfix === tagPair1[0])
			return;

		let result = '{{ ' + tag + postfix + ' }}';

		let editor = getActiveEditor(-1);
		let doc = editor.getDoc();
		doc.replaceRange(result, cursor_from, cursor_to, "");
		return;
	}

	let fieldTypeParamsClean = field.typeparams.replaceAll('"', '').replaceAll('****quote****', '"');
	let fieldTypeParametersList = parseQuote(fieldTypeParamsClean, ",", true);

	const param_array = getParamOptions(group_params_object.params, 'param');
	const countParams = param_array.length;
	const form_content = getParamEditForm(group_params_object, line, positions, isNew, countParams, '{{ ', postfix, ' }}', paramValueString, fieldTypeParametersList);

	if (form_content == null)
		return false;

	let result = '<h3>Field "<b>' + field.fieldtitle + '</b>"  <span style="font-size:smaller;">(<i>Type: ' + fieldType_att.label + '</i>)</span>';

	if (typeof (fieldType_att.helplink) !== "undefined")
		result += ' <a href="' + fieldType_att.helplink + '" target="_blank">Read more</a>';

	result += '</h3>';

	modalContentObject.innerHTML = result + form_content;

	if (joomlaVersion < 4) {
		jQuery(function ($) {
			$(modalContentObject).find(".hasPopover").popover({
				"html": true,
				"trigger": "hover focus",
				"layouteditor_modal_content_box": "body"
			});
		});
	}

	updateParamString("fieldtype_param_", 1, countParams, "current_tagparameter", null, false);
	showModal();
}

//Used in generated html link
function addFieldTag(tagStartChar, postfix, tagEndChar, tag, param_count) {

	let cm = getActiveEditor(-1);

	if (param_count > 0) {
		const cr = cm.getCursor();
		const positions = [cr.ch, cr.ch];
		const mousepos = cm.cursorCoords(cr, "window");

		showModalFieldTagForm(tagStartChar, postfix, tagEndChar, atob(tag), mousepos.top, mousepos.left, cr.line, positions, 1);
	} else {
		updateCodeMirror(tagStartChar + atob(tag) + postfix + tagEndChar);////-----------------todo

		//in case modal window is open
		const modal = document.getElementById('layouteditor_Modal');
		modal.style.display = "none";

		cm.focus();
	}
}

function FillLayout() {
	let editor = getActiveEditor(-1);//codemirror_editors[codemirror_active_index];

	let layoutType;
	let tableId;

	if (window.Joomla instanceof Object) {
		layoutType = parseInt(document.getElementById("jform_layouttype").value);
		tableId = parseInt(document.getElementById("jform_tableid").value);
	} else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
		layoutType = parseInt(document.getElementById('layouttype').value);
		tableId = parseInt(document.getElementById('table').value);

		if (codemirror_active_index == 1)
			codemirror_active_areatext_id = 'layoutmobile';
		else if (codemirror_active_index == 2)
			codemirror_active_areatext_id = 'layoutcss';
		else if (codemirror_active_index == 3)
			codemirror_active_areatext_id = 'layoutjs';
		else
			codemirror_active_areatext_id = 'layoutcode';
	}

	if (isNaN(layoutType) || layoutType === 0) {
		alert("Type not selected.");
		return;
	}

	if (isNaN(tableId) || tableId === 0) {
		alert("Table not selected.");
		return;
	}

	let result = '<p>Select the layout type</p>';

	result += '<select id="modal_layoutTypeSelector"';
	result += ' class="form-select list_class required valid form-control-success" required="" onchange="modal_layoutTypeSelector_update();">';
	result += '<option value="100"' + (layoutType === 1 ? ' selected="selected"' : '') + '>Simple Catalog (All Features)</option>';
	result += '<option value="101">- Simple Catalog (No Features)</option>';
	result += '<option value="111">- Ordered List</option>';
	result += '<option value="112">- Unordered List</option>';
	result += '<option value="120">- Map With Markers</option>';
	result += '<option value="500"' + (layoutType === 5 ? ' selected="selected"' : '') + '>Catalog Page (All Features)</option>';
	result += '<option value="501">- Catalog Page (No Features)</option>';
	result += '<option value="600"' + (layoutType === 6 ? ' selected="selected"' : '') + '>Catalog Item</option>';
	result += '<option value="200"' + (layoutType === 2 ? ' selected="selected"' : '') + '>Edit form</option>';
	result += '<option value="210">- Edit form (REST API)</option>';
	result += '<option value="400"' + (layoutType === 4 ? ' selected="selected"' : '') + '>Details</option>';
	result += '<option value="410">- Details (REST API)</option>';
	result += '<option value="700"' + (layoutType === 7 ? ' selected="selected"' : '') + '>Email Message</option>';
	result += '<option value="800"' + (layoutType === 8 ? ' selected="selected"' : '') + '>XML File</option>';
	result += '<option value="900"' + (layoutType === 9 ? ' selected="selected"' : '') + '>CSV File</option>';
	result += '<option value="1000"' + (layoutType === 10 ? ' selected="selected"' : '') + '>JSON File</option>';
	result += '</select><div id="layoutWizardModalGuide" style="background-color:#eeeeee;width:100%;min-height:200px;max-height:300px;overflow-y: auto;"></div>';
	result += '<button class="btn btn-primary button button-primary" onclick="layoutWizardGenerateLayout(event);">Generate</button>';

	document.getElementById("layouteditor_modal_content_box").innerHTML = '<div class="dynamic_values">' + result + '</div>';
	showModal();
	modal_layoutTypeSelector_update();
}

function getFieldSelector(id, searchByField) {
	let fieldCount = wizardFields.length;
	let fieldtypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'filebox', 'dummy'];

	let result = '<select id="' + id + '"';
	result += ' class="form-select list_class required valid form-control-success" required=""">';

	if (searchByField) {
		fieldtypes_to_skip.push('googlemapcoordinates');
		result += '<option value=""> - Select</option>';
	}

	for (let index = 0; index < fieldCount; index++) {
		let field = wizardFields[index];

		if (fieldtypes_to_skip.indexOf(field.type) === -1) {

			if (field.type === 'googlemapcoordinates') {
				result += '<option value="' + field.fieldname + '.latitude">' + field.fieldname + '("latitude")</option>';
				result += '<option value="' + field.fieldname + '.longitude">' + field.fieldname + '("longitude")</option>';
			} else {
				result += '<option value="' + field.fieldname + '">' + field.fieldname + '</option>';
			}
		}
	}
	result += '</select>';
	return result;
}

function getFieldOptions() {
	let fieldCount = wizardFields.length;

	let resultOption = '<p><span title="To reorder fields, navigate to Table - Fields and drag fields using the three-dot (⋮)">Fields:</span><br/>';
	resultOption += '<select class="form-select list" id="wizardGuide_fields" MULTIPLE style="width: 100%;">';

	let fieldtypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'filebox', 'dummy'];

	for (let index = 0; index < fieldCount; index++) {
		let field = wizardFields[index];

		if (fieldtypes_to_skip.indexOf(field.type) === -1)
			resultOption += '<option value="' + field.fieldname + '" selected>' + field.fieldname + '</option>';
	}
	resultOption += '</select></p>';

	return resultOption;
}

function modal_layoutTypeSelector_update() {
	let layoutTypeExtended = parseInt(document.getElementById("modal_layoutTypeSelector").value);
	let resultOption = '';

	switch (layoutTypeExtended) {

		case 100:
		case 500:

			//Simple Catalog and Catalog Page

			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_count" checked="checked" /> Add "Record Count"</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_add_record" checked="checked" /> Add "Add Record" button</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_print" checked="checked" /> Add "Print" button</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_datagrid" checked="checked" /> Add "datagrid" CSS class</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_batch_toolbar" checked="checked" /> Add Batch Toolbar</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_id" checked="checked" /> Add record ID column</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_search" checked="checked" /> Add search</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_toolbar" checked="checked" /> Add record toolbar</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_pagination" checked="checked" /> Add pagination</p>';
			resultOption += getFieldOptions();
			break;

		case 101:
		case 501:

			//Simple Catalog and Catalog Page
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_count" /> Add "Record Count"</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_add_record" /> Add "Add Record" button</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_print" /> Add "Print" button</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_datagrid" /> Add "datagrid" CSS class</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_batch_toolbar" /> Add Batch Toolbar</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_id" /> Add record ID column</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_search" /> Add search</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_toolbar" /> Add record toolbar</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_pagination" /> Add pagination</p>';
			resultOption += getFieldOptions();
			break;

		case 111:
		case 112:
			//Ordered List and Unordered List
			resultOption += getFieldOptions();
			break;

		case 120:
			//Map With Markers
			resultOption += '<p>Add search by field<br/>' + getFieldSelector('wizardGuide_add_search_field') + '</p>';
			resultOption += '<p>Latitude field:<br/>' + getFieldSelector('wizardGuide_latitude') + '</p>';
			resultOption += '<p>Longitute field:<br/>' + getFieldSelector('wizardGuide_longitude') + '</p>';

			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_count" /> Add "Record Count"</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_add_record" /> Add "Add Record" button</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_print" /> Add "Print" button</p>';

			break;

		case 200:
			//Edit Form
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_goback" checked="checked" /> Add "Go Back" button</p>';
			resultOption += getFieldOptions();
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_save_button" checked="checked" /> Add "Save" button</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_saveandclose_button" checked="checked" /> Add "Save and Close" button</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_saveascopy_button" checked="checked" /> Add "Save as Copy" button</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_saveascopy_cancel" checked="checked" /> Add "Cancel" button</p>';
			break;

		case 210:
			//Edit Form - REST API for dynamic form generation.
			resultOption += getFieldOptions();
			break;

		case 400:
			//Details Page
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_goback" checked="checked" /> Add "Go Back" button</p>';
			resultOption += getFieldOptions();
			break;

		case 410:
			//Details Page - REST API for dynamic form generation.
			resultOption += getFieldOptions();
			break;

		case 600:

			//Catalog Item
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_batch_toolbar" checked="checked" /> Add Batch Toolbar</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_id" checked="checked" /> Add record ID column</p>';
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_toolbar" checked="checked" /> Add record toolbar</p>';
			resultOption += getFieldOptions();
			break;

		case 700:
			//Email Message
			resultOption += getFieldOptions();
			break;

		case 800:
			//XML File
			resultOption += getFieldOptions();
			break;

		case 900:
			//CSV File
			resultOption += getFieldOptions();
			break;

		case 1000:
			//JSON File
			resultOption += '<p><input type="checkbox" id="wizardGuide_add_record_id" checked="checked" /> Add record ID</p>';
			resultOption += getFieldOptions();
			break;
	}

	if (resultOption !== "")
		resultOption = '<div style="padding:11px;">' + resultOption + '</div>';

	document.getElementById("layoutWizardModalGuide").innerHTML = resultOption;
}

function layoutWizardGenerateLayout(event) {
	event.preventDefault();

	let layout_obj = document.getElementById(codemirror_active_areatext_id);
	let editor = getActiveEditor(-1);
	layout_obj.value = editor.getValue();

	let v = layout_obj.value;

	if (v !== '') {
		if (!confirm('Layout Content is not empty. Are you sure you want to replace it?')) {
			return;
		}
	}

	let layoutTypeExtended = parseInt(document.getElementById("modal_layoutTypeSelector").value);
	let layoutType = Math.floor(layoutTypeExtended / 100);
	type_obj.value = layoutType + "";

	updateTagsParameters();

	switch (layoutTypeExtended) {
		case 100:
		case 101:
			layout_obj.value = getLayout_SimpleCatalog();
			break;

		case 110:
			layout_obj.value = getLayout_Ordered_List();
			break;

		case 112:
			layout_obj.value = getLayout_Unordered_List();
			break;

		case 120:
			let result = getLayout_MapWithMarkers();
			layout_obj.value = result.html;

			let css_layout_obj = document.getElementById('jform_layoutcss');
			if (css_layout_obj) {
				let css_editor = getActiveEditor(2);//JS Layout tab
				css_layout_obj.value = css_editor.getValue();
				if (css_layout_obj.value !== '') {
					if (!confirm('CSS Layout Content is not empty. Are you sure you want to replace it?')) {
						return;
					}
				}

				css_layout_obj.value = result.css;
				css_editor.getDoc().setValue(result.css);
			} else {
				alert('This layout requires the CSS code to be inserted into CSS Tab.')
				return;
			}

			let js_layout_obj = document.getElementById('jform_layoutjs');
			if (js_layout_obj) {
				let js_editor = getActiveEditor(3);//JS Layout tab
				js_layout_obj.value = js_editor.getValue();
				if (js_layout_obj.value !== '') {
					if (!confirm('JavaScript Layout Content is not empty. Are you sure you want to replace it?')) {
						return;
					}
				}

				js_layout_obj.value = result.js;
				js_editor.getDoc().setValue(result.js);
			} else {
				alert('This layout requires the JavaScript code to be inserted into JavaScript Tab.')
				return;
			}

			break;

		case 200:
			layout_obj.value = getLayout_Edit();
			break;

		case 210:
			layout_obj.value = getLayout_Edit_REST_API();
			break;

		case 300:
			layout_obj.value = getLayout_Record();
			break;

		case 400:
			layout_obj.value = getLayout_Details();
			break;

		case 410:
			layout_obj.value = getLayout_Details_REST_API();
			break;

		case 500:
		case 501:
			layout_obj.value = getLayout_Page();
			break;

		case 600:
			layout_obj.value = getLayout_Item();
			break;

		case 700:
			layout_obj.value = getLayout_Email();
			break;

		case 800:
			layout_obj.value = getLayout_XML();
			break;

		case 900:
			layout_obj.value = getLayout_CSV();
			break;

		case 1000:
			layout_obj.value = getLayout_JSON();
			break;
	}

	editor.getDoc().setValue(layout_obj.value);

	const modal = document.getElementById('layouteditor_Modal');
	modal.style.display = "none";
}

//Only for Joomla
function getLayout_Page() {

	let result = "";

	let obj;

	obj = document.getElementById("wizardGuide_add_datagrid");
	let addDatagrid = false;
	if (!obj || obj.checked) {
		addDatagrid = true;
		result += '<style>\r\n.datagrid th{text-align:left;}\r\n.datagrid td{text-align:left;}\r\n</style>\r\n';
	}

	result += '<legend>{{ table.title }}</legend>\r\n';

	obj = document.getElementById("wizardGuide_add_record_count");
	if (!obj || obj.checked)
		result += '<div style="float:right;">{{ html.recordcount }}</div>\r\n';

	obj = document.getElementById("wizardGuide_add_add_record");
	if (!obj || obj.checked) {
		result += '<div style="float:left;">{{ html.add }}</div>\r\n';
		result += '\r\n';
	}

	obj = document.getElementById("wizardGuide_add_print");
	if (!obj || obj.checked) {
		if (window.Joomla instanceof Object)
			result += '<div style="text-align:center;">{{ html.print }}</div>\r\n';
	}

	if (addDatagrid)
		result += '<div class="datagrid">\r\n';

	obj = document.getElementById("wizardGuide_add_batch_toolbar");
	if (!obj || obj.checked)
		result += '<div>{{ html.batch("publish","unpublish","refresh","delete") }}</div>\r\n\r\n';

	result += '<table>';

	let fieldtypes_to_skip = ['log', 'filebox', 'dummy'];
	let fieldtypes_withsearch = ['email', 'string', 'multilangstring', 'text', 'multilangtext', 'sqljoin', 'records', 'user', 'userid', 'int', 'checkbox'];
	let fieldtypes_allowed_to_orderby = ['string', 'email', 'url', 'sqljoin', 'phponadd', 'phponchange', 'int', 'float', 'ordering', 'changetime', 'creationtime', 'date', 'multilangstring', 'customtables', 'userid', 'user'];
	fieldtypes_allowed_to_orderby.push('virtual');

	let fields_to_skip = getFieldsToSkip();

	result += renderTableHead(fieldtypes_to_skip, fields_to_skip, fieldtypes_withsearch, fieldtypes_allowed_to_orderby);

	result += '<tbody>\r\n';

	result += '{{ document.layout("LAYOUT NAME") }}<!-- Please create a "Catalog Item" layout and type the name of that layout instead of LAYOUT NAME -->\r\n';

	result += '</tbody>\r\n';
	result += '</table>\r\n';

	if (addDatagrid)
		result += '</div>\r\n';

	obj = document.getElementById("wizardGuide_add_pagination");
	if (!obj || obj.checked) {
		result += '<br/><div style=\'text-align:center;\'>{{ html.pagination }}</div>\r\n';
	}

	return result;
}

//Only for Joomla
function getLayout_Item() {
	let result = "";
	let l = wizardFields.length;

	let fieldtypes_to_skip = ['log', 'filebox', 'dummy'];
	let fields_to_skip = getFieldsToSkip();
	let user_fieldtypes = ['user', 'userid'];

	//Look for ordering field type
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];
		if (field.type === 'ordering') {
			result += '<td style="text-align:center;">{{ ' + field.fieldname + ' }}</td>\r\n';
		}
	}

	let obj;

	obj = document.getElementById("wizardGuide_add_batch_toolbar");
	if (!obj || obj.checked)
		result += '<td style="text-align:center;">{{ html.toolbar("checkbox") }}</td>\r\n';

	obj = document.getElementById("wizardGuide_add_record_id");
	if (!obj || obj.checked)
		result += '<td style="text-align:center;"><a href="{{ record.link(true) }}">{{ record.id }}</a></td>\r\n';

	let user_field = '';

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {

			let fieldValue = '';
			if (field.type === 'url')
				fieldValue = '<a href="{{ ' + field.fieldname + ' }}" target="_blank">{{ ' + field.fieldname + ' }}</a>';
			else
				fieldValue = '{{ ' + field.fieldname + ' }}';

			result += '<td>' + fieldValue + '</td>\r\n';
		}

		if (user_fieldtypes.indexOf(field.type) !== -1)
			user_field = field.fieldname;
	}

	if (user_field === '') {

		obj = document.getElementById("wizardGuide_add_record_toolbar");
		if (!obj || obj.checked)
			result += '<td>{{ html.toolbar("edit","publish","refresh","delete") }}</td>\r\n';

	} else {
		result += '<td>\r\n';
		result += '\t<!-- The "if" statement is to show the toolbar for the record\'s author only. -->\r\n';
		result += '\t{% if ' + user_field + '.value == {{ user.id }} %} <!-- Where "' + user_field + '" is the user type field name. -->\r\n';
		result += '\t\t<!-- toolbar -->\r\n';
		result += '\t\t{{ html.toolbar("edit","publish","refresh","delete") }}\r\n';
		result += '\t\t<!-- end of toolbar -->\r\n';
		result += '\t{% endif %}\r\n';
		result += '</td>\r\n';
	}
	return '<tr>\r\n' + result + '</tr>\r\n';
}


function getLayout_List(tag1, tag2) {

	let result = "";
	let fieldtypes_to_skip = ['log', 'filebox', 'dummy'];
	let l = wizardFields.length;

	result += tag1;
	result += '\r\n{% block record %}';
	result += '\r\n<li>';

	let fields_to_skip = getFieldsToSkip();

	let list = [];
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {

			let fieldValue = '';
			if (field.type === 'url')
				fieldValue = '<a href="{{ ' + field.fieldname + ' }}" target="_blank">{{ ' + field.fieldname + ' }}</a>';
			else
				fieldValue = '{{ ' + field.fieldname + ' }}';

			list.push(fieldValue);
		}
	}

	result += list.join(", ");

	result += '</li>';

	result += '\r\n{% endblock %}';
	result += '\r\n' + tag2;

	return result;
}

function getLayout_Ordered_List() {
	return getLayout_List('<ol>', '</ol>');
}

function getLayout_Unordered_List() {
	return getLayout_List('<ul>', '</ul>');
}

function getLayout_SimpleCatalog() {

	let result = "";
	let l = wizardFields.length;

	let obj;

	obj = document.getElementById("wizardGuide_add_datagrid");
	let addDatagrid = false;
	if (!obj || obj.checked) {
		addDatagrid = true;
		result += '<style>\r\n.datagrid th{text-align:left;}\r\n.datagrid td{text-align:left;}\r\n</style>\r\n';
	}

	obj = document.getElementById("wizardGuide_add_record_count");
	if (!obj || obj.checked)
		result += '<div style="float:right;">{{ html.recordcount }}</div>\r\n';

	obj = document.getElementById("wizardGuide_add_add_record");
	if (!obj || obj.checked) {
		result += '<div style="float:left;">{{ html.add }}</div>\r\n';
		result += '\r\n';
	}

	obj = document.getElementById("wizardGuide_add_print");
	if (!obj || obj.checked) {
		if (window.Joomla instanceof Object)
			result += '<div style="text-align:center;">{{ html.print }}</div>\r\n';
	}

	if (addDatagrid)
		result += '<div class="datagrid">\r\n\r\n';

	obj = document.getElementById("wizardGuide_add_batch_toolbar");
	let addBatchToolbar = true;
	if (!obj || !obj.checked) {
		addBatchToolbar = false;
	}

	if (addBatchToolbar) {
		result += '<div>{{ html.batch(\'publish\',\'unpublish\',\'refresh\',\'delete\') }}</div>';
		result += '\r\n';
	}

	let fieldtypes_to_skip = ['log', 'filebox', 'dummy'];
	let fieldTypesWithSearch = ['email', 'string', 'multilangstring', 'text', 'multilangtext', 'sqljoin', 'records', 'user', 'userid', 'int', 'checkbox'];
	let fieldtypes_allowed_to_orderby = ['string', 'email', 'url', 'sqljoin', 'phponadd', 'phponchange', 'int', 'float', 'ordering', 'changetime', 'creationtime', 'date', 'multilangstring', 'customtables', 'userid', 'user'];
	fieldtypes_allowed_to_orderby.push('virtual');
	let fields_to_skip = getFieldsToSkip();

	result += '<table>\r\n';

	result += renderTableHead(fieldtypes_to_skip, fields_to_skip, fieldTypesWithSearch, fieldtypes_allowed_to_orderby);

	result += '\r\n<tbody>';
	result += '\r\n{% block record %}';
	result += '\r\n<tr>\r\n';

	//Look for ordering field type
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];
		if (field.type === 'ordering') {
			result += '<td style="text-align:center;">{{ ' + field.fieldname + ' }}</td>\r\n';
		}
	}

	if (addBatchToolbar)
		result += '<td style="text-align:center;">{{ html.toolbar("checkbox") }}</td>\r\n';

	obj = document.getElementById("wizardGuide_add_record_id");
	if (!obj || obj.checked)
		result += '<td style="text-align:center;"><a href=\'{{ record.link(true) }}\'>{{ record.id }}</a></td>\r\n';

	obj = document.getElementById("wizardGuide_add_search");
	let addSearch = true;
	if (!obj || !obj.checked)
		addSearch = false;

	obj = document.getElementById("wizardGuide_add_record_toolbar");
	let addToolBar = true;
	if (!obj || !obj.checked)
		addToolBar = false;

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {

			let fieldValue = '';
			if (field.type === 'url')
				fieldValue = '<a href="{{ ' + field.fieldname + ' }}" target="_blank">{{ ' + field.fieldname + ' }}</a>';
			else
				fieldValue = '{{ ' + field.fieldname + ' }}';

			result += '<td>' + fieldValue + '</td>\r\n';
		}
	}

	if (addSearch || addToolBar)
		result += '<td>{{ html.toolbar("edit","publish","refresh","delete") }}</td>\r\n';

	result += '</tr>';

	result += '\r\n{% endblock %}';
	result += '\r\n</tbody>';
	result += '\r\n</table>\r\n';

	if (addDatagrid) {
		result += '\r\n';
		result += '</div>\r\n';
	}

	obj = document.getElementById("wizardGuide_add_pagination");
	if (!obj || obj.checked) {
		result += '<br/><div style=\'text-align:center;\'>{{ html.pagination }}</div>\r\n';
	}

	return result;
}

function getLayout_MapWithMarkers() {

	let result = "";
	let obj;

	obj = document.getElementById("wizardGuide_add_record_count");
	if (!obj || obj.checked)
		result += '<div style="float:right;">{{ html.recordcount }}</div>\r\n';

	obj = document.getElementById("wizardGuide_add_add_record");
	if (!obj || obj.checked) {
		result += '<div style="float:left;">{{ html.add }}</div>\r\n';
		result += '\r\n';
	}

	obj = document.getElementById("wizardGuide_add_print");
	if (!obj || obj.checked) {
		if (window.Joomla instanceof Object)
			result += '<div style="text-align:center;">{{ html.print }}</div>\r\n';
	}

	obj = document.getElementById("wizardGuide_add_search_field");
	if (!obj || !obj.value !== "") {
		result += '{{ html.search("' + obj.value + '") }}\r\n';
		result += '{{ html.searchbutton }}\r\n\r\n';
	}

	let latitudeField = '';
	let longitudeField = '';

	obj = document.getElementById("wizardGuide_latitude");
	if (!obj || !obj.value !== "") {
		latitudeField = obj.value;

		if (latitudeField.indexOf('.') !== -1) {
			let parts = latitudeField.split('.');
			latitudeField = parts[0] + '("' + parts[1] + '")';
		}

	} else {
		alert("Latitude field is required");
		return;
	}

	obj = document.getElementById("wizardGuide_longitude");
	if (!obj || !obj.value !== "") {
		longitudeField = obj.value;

		if (longitudeField.indexOf('.') !== -1) {
			let parts = longitudeField.split('.');
			longitudeField = parts[0] + '("' + parts[1] + '")';
		}

	} else {
		alert("Longitude field is required");
		return;
	}

	result += '<div id="map">The map will be here.</div>\r\n';
	result += '<script>\r\n';
	result += 'let list_of_{{ table.name }} = [\r\n';
	result += '{% block record %}\r\n';
	result += '{% if ' + latitudeField + ' != "" and ' + longitudeField + ' != "" %}\r\n';
	result += '{\r\n';
	result += '"id_":"{{ record.id }}",\r\n';
	result += '"latitude":"{{ ' + latitudeField + ' }}",\r\n';
	result += '"longitude":"{{ ' + longitudeField + ' }}",\r\n';
	result += '}{% if not record.islast %},{% endif %}\r\n';
	result += '{% endif %}\r\n';
	result += '{% endblock %}\r\n';
	result += ']\r\n';
	result += '</script>\r\n';

	let css = '';
	css += '@media screen and (max-width: 600px) {\r\n';
	css += '\t#map {\r\n';
	css += '\t\twidth: 100%;\r\n';
	css += '\t\theight: 100%;\r\n';
	css += '\t\t}\r\n';
	css += '}\r\n';
	css += '@media screen and (min-width: 601px) {\r\n';
	css += '\t#map {\r\n';
	css += '\t\twidth: 100%;\r\n';
	css += '\t\theight: 400px;\r\n';
	css += '\t}\r\n';
	css += '}\r\n';

	let js = '';
	js += 'let checkGoogleMaps_{{ table.name }} = null;\r\n';
	js += 'window.initMap_{{ table.name }} = function() {\r\n';
	js += '\tmap = new google.maps.Map(document.getElementById(\'map\'));\r\n';
	js += '\taddMarkers_{{ table.name }}(list_of_{{ table.name }});\r\n';
	js += '}\r\n';
	js += '\r\n';
	js += 'window.addEventListener(\'load\', function () {\r\n';
	js += '\t// More thorough check for Google Maps API\r\n';
	js += '\tcheckGoogleMaps_{{ table.name }} = setInterval(function () {\r\n';
	js += '\t\tif (typeof google !== \'undefined\' && typeof google.maps !== \'undefined\' && typeof google.maps.Map === \'function\') {\r\n';
	js += '\t\t\tinitMap_{{ table.name }}();\r\n';
	js += '\t\t\tclearInterval(checkGoogleMaps_{{ table.name }});\r\n';
	js += '\t\t}\r\n';
	js += '\t}, 300);//Wait a little to make sure that Google Map is loaded.\r\n';
	js += '});\r\n';

	js += 'function addMarkers_{{ table.name }}(items) {\r\n';
	js += '\tif (!items || items.length === 0) return;\r\n';
	js += '\t// Create bounds object to track marker positions\r\n';
	js += '\tconst bounds = new google.maps.LatLngBounds();\r\n';
	js += '\tconst markers = [];\r\n';
	js += '\titems.forEach((data, index) => {\r\n';
	js += '\tconst position = {lat: parseFloat(data.latitude), lng: parseFloat(data.longitude)};\r\n';
	js += '\t// Extend bounds with each marker position\r\n';
	js += '\tbounds.extend(position);\r\n';
	js += '\t// Create the marker with a specific color based on activity status\r\n';
	js += '\tconst marker = new google.maps.Marker({\r\n';
	js += '\tposition: position,\r\n';
	js += '\tmap: map,\r\n';
	js += '\ttitle: data.name,\r\n';
	js += '\ticon: {\r\n';
	js += '\tpath: google.maps.SymbolPath.CIRCLE,\r\n';
	js += '\tscale: 10, // Adjust size here\r\n';
	js += '\tfillColor: \'red\',\r\n';
	js += '\tfillOpacity: 1,\r\n';
	js += '\tstrokeWeight: 1,\r\n';
	js += '\tstrokeColor: \'black\' // Marker border color\r\n';
	js += '\t}\r\n';
	js += '\t});\r\n';
	js += '\tmarkers.push(marker);\r\n';
	js += '\t});\r\n';
	js += '\r\n';
	js += '\t// Fit the map to the bounds and adjust zoom\r\n';
	js += '\tmap.fitBounds(bounds);\r\n';
	js += '\r\n';
	js += '\t// Add a listener for when the zoom_changed event fires\r\n';
	js += '\tgoogle.maps.event.addListenerOnce(map, \'bounds_changed\', function () {\r\n';
	js += '\t// Get the current zoom level\r\n';
	js += '\tlet currentZoom = map.getZoom();\r\n';
	js += '\t// If markers are too close together, prevent excessive zoom\r\n';
	js += '\tif (currentZoom > 15) {\r\n';
	js += '\tmap.setZoom(15);\r\n';
	js += '\t}\r\n';
	js += '\t// If markers are too far apart, set minimum zoom\r\n';
	js += '\tif (currentZoom < 3) {\r\n';
	js += '\tmap.setZoom(3);\r\n';
	js += '\t}\r\n';
	js += '\t});\r\n';
	js += '}\r\n';

	return {"html": result, "css": css, "js": js};
}

function renderTableHead(fieldtypes_to_skip, fields_to_skip, fieldTypesWithSearch, fieldtypes_allowed_to_orderby) {

	let obj = document.getElementById("wizardGuide_add_batch_toolbar");
	let addBatchToolbar = true;
	if (!obj || !obj.checked) {
		addBatchToolbar = false;
	}

	let l = wizardFields.length;
	let result = '';

	result += '<thead><tr>\r\n';

	//Look for ordering field type
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];
		if (field.type === 'ordering') {
			result += '<th class="short">{{ ' + field.fieldname + '.label(true) }}</th>\r\n';
		}
	}

	if (addBatchToolbar)
		result += '<th class="short">{{ html.batch("checkbox") }}</th>\r\n';

	obj = document.getElementById("wizardGuide_add_record_id");
	if (!obj || obj.checked)
		result += '<th class="short">{{ record.label(true) }}</th>\r\n';

	obj = document.getElementById("wizardGuide_add_search");
	let addSearch = true;
	if (!obj || !obj.checked) {
		addSearch = false;
	}

	for (let index = 0; index < l; index++) {
		result += renderTableColumnHeader(wizardFields[index], fieldtypes_to_skip, fields_to_skip, fieldTypesWithSearch, fieldtypes_allowed_to_orderby, addSearch);
	}

	let letColumnItems = [];

	obj = document.getElementById("wizardGuide_add_record_toolbar");
	if (!obj || obj.checked)
		letColumnItems.push('Action')

	if (addSearch)
		letColumnItems.push('{{ html.searchbutton }}')

	if (letColumnItems.length > 0)
		result += '<th>' + letColumnItems.join('<br/>') + '</th>\r\n';

	result += '</tr></thead>\r\n';

	return result;
}

function renderTableColumnHeader(field, fieldtypes_to_skip, fields_to_skip, fieldtypes_withsearch, fieldtypes_allowed_to_orderby, addSearch) {

	let result = '';

	if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {

		result += '<th>';

		let allowOrdering = fieldtypes_allowed_to_orderby.indexOf(field.type) !== -1;

		if (allowOrdering && field.type === 'virtual') {

			let fieldTypeParamsClean = field.typeparams.replaceAll('"', '').replaceAll('****quote****', '"');
			let fieldTypeParametersList = parseQuote(fieldTypeParamsClean, ",", true);

			if (fieldTypeParametersList.length > 1 && fieldTypeParametersList[1] === 'virtual')
				allowOrdering = false;
		}

		if (allowOrdering)
			result += '{{ ' + field.fieldname + '.label(true) }}';
		else
			result += '{{ ' + field.fieldname + '.title }}';

		if (addSearch && fieldtypes_withsearch.indexOf(field.type) !== -1) {

			if (field.type === 'checkbox' || field.type === 'sqljoin' || field.type === 'records')
				result += '<br/>{{ html.search("' + field.fieldname + '","","reload") }}';
			else
				result += '<br/>{{ html.search("' + field.fieldname + '") }}';
		}

		result += '</th>\r\n';
	}

	return result;
}

function getFieldsToSkip() {

	let fields_to_skip = [];

	let obj = document.getElementById("wizardGuide_fields");
	if (obj) {
		// Loop through all options in the select
		for (let i = 0; i < obj.options.length; i++) {
			// If the option is not selected and not already in the array
			if (!obj.options[i].selected && !fields_to_skip.includes(obj.options[i].value)) {
				fields_to_skip.push(obj.options[i].value);
			}
		}
	}

	return fields_to_skip;
}

function getLayout_Details_REST_API() {
	let result = "";
	let l = wizardFields.length;

	result += '{\n';
	result += '\t  "table": "{{ table.name }}",\r\n';
	result += '\t  "tablelabel": "{{ table.title }}",\r\n';
	result += '\t  "fields": [\r\n';

	let fieldtypes_to_skip = ['log', 'filebox', 'imagegallery', 'dummy'];
	let fields_to_skip = getFieldsToSkip();

	let fields = [];
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {
			fields.push(field);
		}
	}

	for (let index = 0; index < fields.length; index++) {
		let field = fields[index];

		result += '\t  \t  {\n';
		result += '\t  \t  \t  "fieldname": "' + field.fieldname + '",\r\n';
		result += '\t  \t  \t  "label": "{{ ' + field.fieldname + '.title }}",\r\n';
		result += '\t  \t  \t  "value": "{{ ' + field.fieldname + '.value }}",\r\n';
		result += '\t  \t  \t  "processedValue": "{{ ' + field.fieldname + ' }}"\r\n';
		result += '\t  \t  }' + (index < fields.length - 1 ? ',' : '') + '\n';
	}

	result += '\t  ]\r\n';
	result += '}';

	return result;
}

function getLayout_Edit_REST_API() {
	let result = "";
	let l = wizardFields.length;

	result += '{\n';
	result += '\t  "table": "{{ table.name }}",\r\n';
	result += '\t  "tablelabel": "{{ table.title }}",\r\n';
	result += '\t  "fields": [\r\n';

	let fieldtypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'filebox', 'dummy'];
	let fields_to_skip = getFieldsToSkip();

	let fields = [];
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {
			fields.push(field);
		}
	}

	for (let index = 0; index < fields.length; index++) {
		let field = fields[index];

		result += '\t  \t  {\n';
		result += '\t  \t  \t  "fieldname": "' + field.fieldname + '",\r\n';
		result += '\t  \t  \t  "label": "{{ ' + field.fieldname + '.title }}",\r\n';
		//result += '\t  \t  \t  "type": "{{ ' + field.fieldname + '.type }}",\r\n';
		//result += '\t  \t  \t  "params": "{{ ' + field.fieldname + '.params | join(",") }}",\r\n';
		//result += '\t  \t  \t  "options": {{ ' + field.fieldname + '.options | json_encode }},\r\n';
		//result += '\t  \t  \t  "required": {{ ' + field.fieldname + '.required }},\r\n';
		//result += '\t  \t  \t  "value": {{ ' + field.fieldname + '.value | json_encode }},\r\n';
		result += '\t  \t  \t  "input": {{ ' + field.fieldname + '.input | json_encode }}\r\n';
		result += '\t  \t  }' + (index < fields.length - 1 ? ',' : '') + '\n';
	}

	result += '\t  ]\r\n';
	result += '}';

	return result;
}

function getLayout_Edit() {
	let result = "";
	let l = wizardFields.length;

	result += '<legend>{{ table.title }}</legend>\r\n\r\n';

	let obj;

	obj = document.getElementById("wizardGuide_add_goback");
	if (!obj || obj.checked)
		result += '{{ html.goback("Go back") }}\r\n\r\n';

	result += '<div class="form-horizontal">\r\n\r\n';

	let fieldtypes_to_skip = ['log', 'phponview', 'phponchange', 'phponadd', 'md5', 'id', 'server', 'userid', 'viewcount', 'lastviewtime', 'changetime', 'creationtime', 'filebox', 'dummy'];
	let fields_to_skip = getFieldsToSkip();

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {
			result += '\t<div class="control-group">\r\n';
			result += '\t\t<div class="control-label">{{ ' + field.fieldname + '.title }}</div><div class="controls">{{ ' + field.fieldname + '.edit }}</div>\r\n';
			result += '\t</div>\r\n\r\n';
		}
	}

	result += '</div>\r\n';
	result += '\r\n';

	for (let index2 = 0; index2 < l; index2++) {
		let field2 = wizardFields[index2];

		if (field2.fieldtyue === "dummy") {
			result += '<p><span style="color: #FB1E3D; ">*</span> {{ ' + field2.fieldname + '.title }}</p>\r\n';
			break;
		}
	}

	let buttons = [];

	obj = document.getElementById("wizardGuide_add_save_button");
	if (!obj || obj.checked)
		buttons.push('{{ html.button("save") }}');

	obj = document.getElementById("wizardGuide_add_saveandclose_button");
	if (!obj || obj.checked)
		buttons.push('{{ html.button("saveandclose") }}');

	obj = document.getElementById("wizardGuide_add_saveascopy_button");
	if (!obj || obj.checked)
		buttons.push('{{ html.button("saveascopy") }}');

	obj = document.getElementById("wizardGuide_add_saveascopy_cancel");
	if (!obj || obj.checked)
		buttons.push('{{ html.button("cancel") }}');

	if (buttons.length > 0)
		result += '<div style="text-align:center;">' + buttons.join(" ") + '</div>\r\n';

	return result;
}

function getLayout_Details() {
	let result = "";
	let l = wizardFields.length;

	result += '<legend>{{ table.title }}</legend>\r\n\r\n';

	let obj = document.getElementById("wizardGuide_add_goback");
	if (!obj || obj.checked)
		result += '{{ html.goback("Go back") }}\r\n\r\n';

	result += '<div class="form-horizontal">\r\n\r\n';

	let fieldtypes_to_skip = ['log', 'filebox', 'dummy'];
	let fields_to_skip = getFieldsToSkip();

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {
			result += '\t<div class="control-group">\r\n';
			result += '\t\t<div class="control-label">{{ ' + field.fieldname + '.title }}</div>';

			let fieldValue = '';
			if (field.type == 'url')
				fieldValue = '<a href="{{ ' + field.fieldname + ' }}" target="_blank">{{ ' + field.fieldname + ' }}</a>';
			else
				fieldValue = '{{ ' + field.fieldname + ' }}';

			result += '<div class="controls">' + fieldValue + '</div>\r\n';
			result += '\t</div>\r\n\r\n';
		}
	}
	result += '</div>\r\n';
	result += '\r\n';
	return result;
}

function getLayout_Email() {
	let result = "";
	let l = wizardFields.length;
	result += '<p>New form entry registered:</p>\r\n\r\n';

	let fieldtypes_to_skip = ['log', 'filebox', 'dummy'];
	let fields_to_skip = getFieldsToSkip();

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1)
			result += '\t\t<p>{{ ' + field.fieldname + '.title }}: {{ ' + field.fieldname + ' }}</p>\r\n';
	}
	return result;
}

function getLayout_CSV() {
	let result = "";
	let l = wizardFields.length;

	let fieldtypes_to_skip = ['log', 'filebox', 'dummy', 'ordering'];
	let fieldtypes_to_purevalue = ['image', 'filebox', 'file'];
	let fields_to_skip = getFieldsToSkip();

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {
			if (result !== '')
				result += ',';

			result += '"{{ ' + field.fieldname + '.title }}"';
		}
	}

	result += '\r\n{% block record %}';

	let firstField = true;
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {
			if (!firstField)
				result += ',';

			if (fieldtypes_to_purevalue.indexOf(field.type) === -1)
				result += '"{{ ' + field.fieldname + ' }}"';
			else
				result += '"{{ ' + field.fieldname + '.value }}"';

			firstField = false;
		}
	}
	result += '\r\n{% endblock %}';
	return result;
}

function getLayout_JSON() {
	let result = "";
	let l = wizardFields.length;

	let idFieldNameFound = false;
	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];
		if (field.fieldname === 'id') {
			idFieldNameFound = true;
			break;
		}
	}

	result += '[\r\n{% block record %}\r\n{';

	let obj = document.getElementById("wizardGuide_add_record_id");
	if (!obj || obj.checked) {
		if (idFieldNameFound)
			result += '"_id":{{ record.id }},\r\n';
		else
			result += '"id":{{ record.id }},\r\n';
	}

	let fieldtypes_to_skip = ['log', 'filebox', 'dummy', 'ordering'];
	let fieldtypes_to_purevalue = ['image', 'filebox', 'file', 'article', 'imagegallery'];

	let fieldtypes_numbers = ['int', 'ordering', 'time', 'float', 'viewcount', 'imagegallery', 'id', 'filebox', 'checkbox', 'article'];
	let fields_to_skip = getFieldsToSkip();
	let firstField = true;

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1) {

			if (!firstField)
				result += ',\r\n';

			if (fieldtypes_to_purevalue.indexOf(field.type) === -1) {

				if (field.type === "usergroup") {
					if (typeof wp === 'undefined')
						result += '"' + field.fieldname + '":{{ ' + field.fieldname + ' }}';
					else
						result += '"' + field.fieldname + '":"{{ ' + field.fieldname + ' }}"';

				} else if (fieldtypes_numbers.indexOf(field.type) !== -1) {
					result += '"' + field.fieldname + '":{{ ' + field.fieldname + ' }}';
				} else
					result += '"' + field.fieldname + '":"{{ ' + field.fieldname + ' }}"';
			} else
				result += '"' + field.fieldname + '":"{{ ' + field.fieldname + '.value }}"';

			firstField = false;
		}
	}
	result += '}{% if not record.islast %},{% endif %}\r\n{% endblock %}]\r\n';
	return result;
}

function getLayout_XML() {
	let result = "";
	let l = wizardFields.length;
	result += '<?xml version="1.0" encoding="utf-8"?>\r\n<document>\r\n';

	result += '{% block record %}\r\n';

	let fieldtypes_to_skip = ['log', 'filebox', 'dummy', 'ordering'];
	let fields_to_skip = getFieldsToSkip();

	result += '<record id="{{ record.id }}">\r\n';

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];

		if (field.type !== 'ordering' && fieldtypes_to_skip.indexOf(field.type) === -1 && fields_to_skip.indexOf(field.fieldname) === -1)
			result += '\t<field name=\'' + field.fieldname + '\' label=\'{{ ' + field.fieldname + '.title }}\'>{{ ' + field.fieldname + ' }}</field>\r\n';
	}

	result += '</record>{% endblock %}\r\n';

	result += '</document>';
	return result;
}

function getLayout_Record() {
	let result = "";
	let l = wizardFields.length;
	let fieldtypes_to_skip = ['log', 'dummy'];
	let fieldtypes_to_purevalue = ['image', 'filebox', 'file', 'ordering'];

	for (let index = 0; index < l; index++) {
		let field = wizardFields[index];
		if (fieldtypes_to_skip.indexOf(field.type) === -1) {
			if (fieldtypes_to_purevalue.indexOf(field.type) === -1)
				result += '\t<div>{{ ' + field.fieldname + ' }}</div>\r\n';
			else
				result += '\t<div>{{ ' + field.fieldname + '.value }}</div>\r\n';
		}
	}
	return result;
}

function getActiveEditor(index) {

	if (index === -1)
		index = codemirror_active_index;

	let cm;
	if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar'))
		cm = codemirror_editors[index].codemirror;
	else if (typeof Joomla !== 'undefined')
		cm = codemirror_editors[index];

	return cm;
}