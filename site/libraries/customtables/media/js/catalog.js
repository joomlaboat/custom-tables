/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2025. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

let ctLinkLoading = false;

function ctCreateUser(msg, listing_id, toolbarBoxId, ModuleId) {
	if (confirm(msg)) {
		document.getElementById(toolbarBoxId).innerHTML = '';

		let returnTo = btoa(window.location.href);
		let deleteParams = ['task', "listing_id", 'returnto', 'ids', 'option', 'view'];
		let addParams = ['task=createuser', 'listing_id=' + listing_id, 'returnto=' + returnTo];

		if (CTEditHelper.cmsName === 'Joomla') {
			if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0) {
				addParams.push('option=com_customtables');
				addParams.push('view=catalog');
				addParams.push('ModuleId=' + ModuleId);
			} else {
				addParams.push('Itemid=' + CTEditHelper.itemId);
			}
		}
		window.location.href = esPrepareLink(deleteParams, addParams);
	}
}

function ctResetPassword(msg, listing_id, toolbarBoxId, ModuleId) {
	if (confirm(msg)) {
		document.getElementById(toolbarBoxId).innerHTML = '';

		let returnTo = btoa(window.location.href);
		let deleteParams = ['task', "listing_id", 'returnto', 'ids', 'option', 'view'];
		let addParams = ['task=resetpassword', 'listing_id=' + listing_id, 'returnto=' + returnTo];

		if (CTEditHelper.cmsName === 'Joomla') {
			if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0) {
				addParams.push('option=com_customtables');
				addParams.push('view=catalog');
				addParams.push('ModuleId=' + ModuleId);
			} else {
				addParams.push('Itemid=' + CTEditHelper.itemId);
			}
		}
		window.location.href = esPrepareLink(deleteParams, addParams);
	}
}

function esPrepareLink(deleteParams, addParams) {

	let link = window.location.href;

	const pair = link.split('#');
	link = pair[0];

	for (let i = 0; i < deleteParams.length; i++) link = removeURLParameter(link, deleteParams[i]);

	for (let a = 0; a < addParams.length; a++) {

		if (link.indexOf("?") === -1) link += "?"; else link += "&";

		link += addParams[a];
	}

	return link;
}

function esEditObject(objId, toolbarBoxId, Itemid, tmpl, returnto) {
	if (ctLinkLoading) return;

	ctLinkLoading = true;
	document.getElementById(toolbarBoxId).innerHTML = '';

	let return_to = btoa(window.location.href);
	let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=edititem&listing_id=' + objId + '&Itemid=' + Itemid + '&returnto=' + return_to;

	if (tmpl !== '') link += '&tmpl=' + tmpl;

	link += '&returnto=' + returnto;

	window.location.href = link;
}

function runTheTask(task, tableid, recordId, responses, last, reload, ModuleId) {

	let params = 'task=' + task + '&listing_id=' + recordId;

	if (CTEditHelper.cmsName === 'Joomla') {
		if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0)
			params += '&ModuleId=' + ModuleId;
		else
			params += '&Itemid=' + CTEditHelper.itemId;
	}

	let http = CreateHTTPRequestObject();   // defined in ajax.js

	let addParams = ['clean=1'];
	let url = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], addParams);

	console.warn(url);

	if (http) {
		http.open("POST", url, true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function () {

			if (http.readyState === 4) {
				let res = http.response.replace(/(\r\n|\n|\r)/gm, "");

				if (responses.indexOf(res) !== -1) {

					let element_tableid_tr = "ctTable_" + tableid + '_' + recordId;

					let table_object = document.getElementById("ctTable_" + tableid);
					if (!reload && table_object && CTEditHelper.cmsName === 'Joomla') {
						let index = findRowIndexById("ctTable_" + tableid, element_tableid_tr);
						if (task === 'delete')
							table_object.deleteRow(index);
						else
							ctCatalogUpdate(tableid, recordId, index, ModuleId);
					} else {
						window.location.reload();
					}

					ctLinkLoading = false;

					if (last) {
						let toolbarBoxId = 'esToolBar_' + task + '_box_' + tableid;
						let toolbarBoxIdObject = document.getElementById(toolbarBoxId);
						if (toolbarBoxIdObject) toolbarBoxIdObject.style.visibility = 'visible';
					}

				} else alert(res);
			}
		}
		http.send(params);
	}
}

function ctRefreshRecord(tableid, recordId, toolbarBoxId, ModuleId) {
	if (ctLinkLoading) return;
	ctLinkLoading = true;
	runTheTask('refresh', tableid, recordId, ['refreshed'], false, false, ModuleId);
}

function ctCopyRecord(tableid, listing_id, toolbarBoxId, ModuleId) {
	if (ctLinkLoading) return;

	ctLinkLoading = true;

	if (document.getElementById(toolbarBoxId))
		document.getElementById(toolbarBoxId).innerHTML = '';

	let returnTo = btoa(window.location.href);
	let deleteParams = ['task', "listing_id", 'returnto', 'ids', 'option', 'view'];
	let addParams = ['task=copy', 'listing_id=' + listing_id, 'returnto=' + returnTo];

	if (CTEditHelper.cmsName === 'Joomla') {
		if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0) {
			addParams.push('option=com_customtables');
			addParams.push('view=catalog');
			addParams.push('ModuleId=' + ModuleId);
		} else {
			addParams.push('Itemid=' + CTEditHelper.itemId);
		}
	}
	window.location.href = esPrepareLink(deleteParams, addParams);
}

function ctOrderChanged(objectValue, ModuleId) {
	let deleteParams = ['task', "listing_id", 'returnto', 'ids', 'option', 'view'];
	let addParams = ['task=setorderby', 'orderby=' + objectValue];

	if (CTEditHelper.cmsName === 'Joomla') {
		if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0) {
			addParams.push('option=com_customtables');
			addParams.push('view=catalog');
			addParams.push('ModuleId=' + ModuleId);
		} else {
			addParams.push('Itemid=' + CTEditHelper.itemId);
		}
	}
	window.location.href = esPrepareLink(deleteParams, addParams);
}

function ctLimitChanged(objectValue, ModuleId) {

	let deleteParams = ['task', "listing_id", 'returnto', 'ids', 'option', 'view'];
	let addParams = ['task=setlimit', 'limit=' + objectValue];

	if (CTEditHelper.cmsName === 'Joomla') {
		if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0) {
			addParams.push('option=com_customtables');
			addParams.push('view=catalog');
			addParams.push('ModuleId=' + ModuleId);
		} else {
			addParams.push('Itemid=' + CTEditHelper.itemId);
		}
	}
	window.location.href = esPrepareLink(deleteParams, addParams);
}

function ctPublishRecord(tableid, recordId, toolbarBoxId, publish, ModuleId) {
	if (ctLinkLoading) return;

	ctLinkLoading = true;
	document.getElementById(toolbarBoxId).innerHTML = '';
	runTheTask((publish === 0 ? 'unpublish' : 'publish'), tableid, recordId, ['published', 'unpublished'], false, false, ModuleId);
}

function findRowIndexById(tableid, rowId) {

	let table_object = document.getElementById(tableid);

	if (table_object) {
		let rows = table_object.rows;
		for (let i = 0; i < rows.length; i++) {
			if (rows.item(i).id === rowId) return i;
		}
	}
	return -1;
}

function ctDeleteRecord(msg, tableid, recordId, toolbarBoxId, ModuleId) {
	if (ctLinkLoading) return;

	ctLinkLoading = true;

	if (confirm(msg)) {
		runTheTask('delete', tableid, recordId, ['deleted'], false, false, ModuleId);
	} else {
		ctLinkLoading = false;
	}
}

function es_SearchBoxKeyPress(e) {
	if (e.keyCode === 13)//enter key pressed
		ctSearchBoxDo();
}

function ctSearchBoxDo() {
	if (ctLinkLoading) return;

	ctLinkLoading = true;
	let w = [];
	let allSearchElements = document.querySelectorAll('[ctSearchBoxField]');

	for (let i = 0; i < allSearchElements.length; i++) {
		let n = allSearchElements[i].getAttribute('ctSearchBoxField').split(":");
		let elementId = n[0];
		let obj = document.getElementById(elementId);

		if (obj) {
			let objValue = obj.value;

			if (obj.dataset.minlength) {
				let l = parseInt(obj.dataset.minlength);

				if (l > 0) {
					if (objValue.length < l) {
						alert(obj.dataset.label + ": " + TranslateText('COM_CUSTOMTABLES_SEARCH_ALERT_MINLENGTH', l));
						ctLinkLoading = false;
						return;
					}
				}
			}

			let operator = '=';

			if (objValue !== "" && (objValue !== "0" || obj.dataset.type === 'int' || obj.dataset.type === 'float' || obj.dataset.type === 'checkbox')) {
				if (n[2] === "") {
					if (objValue.indexOf("-to-") !== -1) {
						if (objValue !== "-to-") w.push(n[1] + "_r_=" + objValue);
					} else {
						//string search
						if (obj.dataset.match === 'exact')
							w.push(n[1] + '==' + objValue);
						else if (obj.dataset.match === 'startwith')
							w.push(n[1] + '==' + objValue + '*');
						else if (obj.dataset.match === 'endwith')
							w.push(n[1] + '==*' + objValue);
						else
							w.push(n[1] + operator + objValue);
					}
				} else w.push(n[1] + "=" + n[2] + "." + objValue);//Custom Tables Structure
			}
		} else {
			alert('Element "' + elementId + '" not found.');
		}
	}

	let addParams = [];

	if (w.length > 0)
		addParams.push("where=" + encodeURIComponent(w.join(" and ")));

	window.location.href = esPrepareLink(['where', 'task', "listing_id", 'returnto'], addParams);
}

function ctSearchReset() {
	if (ctLinkLoading) return;

	ctLinkLoading = true;

	window.location.href = esPrepareLink(['where', 'task', "listing_id", 'returnto'], []);
}

function esCheckboxAllClicked(tableid) {

	const checkboxObj = document.getElementById("esCheckboxAll" + tableid);
	const elements = document.getElementsByName("esCheckbox" + tableid);

	const ids = [];
	for (let i = 0; i < elements.length; i++) {
		const d = parseInt(elements[i].value);

		if (ids.indexOf(d) === -1) {
			ids.push(d);
			const obj = document.getElementById(elements[i].id);
			obj.checked = checkboxObj.checked;
		}
	}
}

function getListOfSelectedRecords(tableid) {

	const selectedIds = [];
	const elements = document.getElementsByName("esCheckbox" + tableid);

	for (let i = 0; i < elements.length; i++) {
		const obj = document.getElementById(elements[i].id);

		if (obj.checked) {
			const d = parseInt(elements[i].value);

			if (selectedIds.indexOf(d) === -1) selectedIds.push(d);
		}
	}
	return selectedIds;
}

function ctToolBarDO(task, tableid, ModuleId) {

	if (ctLinkLoading) return;

	ctLinkLoading = true;
	const elements = getListOfSelectedRecords(tableid);

	if (elements.length === 0) {
		alert(TranslateText('COM_CUSTOMTABLES_JS_SELECT_RECORDS'));
		ctLinkLoading = false;
		return;
	}

	if (task === 'delete') {

		let msg;
		if (elements.length === 1) msg = TranslateText('COM_CUSTOMTABLES_JS_SELECT_DO_U_WANT_TO_DELETE1'); else msg = TranslateText('COM_CUSTOMTABLES_JS_SELECT_DO_U_WANT_TO_DELETE').replace('%s', elements.length);

		if (!confirm(msg)) {
			ctLinkLoading = false;
			return;
		}
	}

	const toolbarBoxId = 'esToolBar_' + task + '_box_' + tableid;
	document.getElementById(toolbarBoxId).style.visibility = 'hidden';

	for (let i = 0; i < elements.length; i++) {
		let listing_id = elements[i];
		let accept_responses = [];

		if (task === 'refresh') accept_responses = ['refreshed']; else if (task === 'publish' || task === 'unpublish') accept_responses = ['published', 'unpublished']; else if (task === 'delete') accept_responses = ['published', 'deleted'];

		let last = i === elements.length - 1;
		runTheTask(task, tableid, listing_id, accept_responses, last, false, ModuleId);
	}
}

//https://stackoverflow.com/a/1634841
function removeURLParameter(url, parameter) {
	//prefer to use l.search if you have a location/link object
	const urlParts = url.split('?');
	if (urlParts.length >= 2) {
		const prefix = encodeURIComponent(parameter) + '=';
		const pars = urlParts[1].split(/[&;]/g);

		//reverse iteration as may be destructive
		for (let i = pars.length; i-- > 0;) {
			//idiom for string.startsWith
			if (pars[i].lastIndexOf(prefix, 0) !== -1) {
				pars.splice(i, 1);
			}
		}

		url = urlParts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
		return url;
	} else {
		return url;
	}
}

function ct_UpdateAllRecordsValues(WebsiteRoot, Itemid, fieldname_, record_ids, postfix, ModuleId) {
	let ids = record_ids.split(',');
	const obj_checkbox_off = document.getElementById(ctFieldInputPrefix + "_" + fieldname_ + "_off");
	if (obj_checkbox_off) {

		for (let i = 0; i < ids.length; i++) {
			let objectNameOff = ctFieldInputPrefix + ids[i] + "_" + fieldname_ + "_off";
			document.getElementById(objectNameOff).value = obj_checkbox_off.value;

			let objectName = ctFieldInputPrefix + ids[i] + "_" + fieldname_;
			document.getElementById(objectName).checked = parseInt(obj_checkbox_off.value) === 1;

			ct_UpdateSingleValue(WebsiteRoot, Itemid, fieldname_, ids[i], postfix, ModuleId);
		}

	} else {
		let objectName = ctFieldInputPrefix + "_" + fieldname_;
		let value = document.getElementById(objectName).value;

		for (let i = 0; i < ids.length; i++) {
			let objectName = ctFieldInputPrefix + "_" + ids[i] + "_" + fieldname_;
			let obj = document.getElementById(objectName);
			obj.value = value;
			ct_UpdateSingleValue(WebsiteRoot, Itemid, fieldname_, ids[i], postfix, ModuleId);
			if (obj.dataset.type === "sqljoin") {

				let tableid = obj.dataset.tableid;
				let table_object = document.getElementById("ctTable_" + tableid);

				if (table_object) {
					let element_tableid_tr = "ctTable_" + tableid + '_' + ids[i];
					let index = findRowIndexById("ctTable_" + tableid, element_tableid_tr);
					ctCatalogUpdate(tableid, ids[i], index, ModuleId);
				}
			}
		}
	}
}

function ct_UpdateSingleValue(WebsiteRoot, Itemid, fieldname_, record_id, postfix, ModuleId) {

	let params = "";

	const obj_checkbox_off = document.getElementById(ctFieldInputPrefix + record_id + "_" + fieldname_ + "_off");
	if (obj_checkbox_off) {
		//A bit confusing. But this is needed to save Unchecked values
		//It's because unchecked checkbox has value NULL
		params = ctFieldInputPrefix + fieldname_ + "_off=" + obj_checkbox_off.value; // if this set 1 then the checkbox value will be 0

		if (parseInt(obj_checkbox_off.value) === 1)
			params += "&" + ctFieldInputPrefix + fieldname_ + "=0";
		else
			params += "&" + ctFieldInputPrefix + fieldname_ + "=1";

	} else {
		let objectName = ctFieldInputPrefix + record_id + "_" + fieldname_;
		params += "&" + ctFieldInputPrefix + fieldname_ + "=" + document.getElementById(objectName).value;
	}
	ct_UpdateSingleValueSet(WebsiteRoot, Itemid, fieldname_, record_id, postfix, ModuleId, params);
}

function ct_UpdateSingleValueSet(WebsiteRoot, Itemid, fieldname_, record_id, postfix, ModuleId, valueParam) {

	const fieldname = fieldname_.split('_')[0];

	let deleteParams = ['task', "listing_id", 'returnto', 'ids', 'option', 'view'];
	let addParams = [];

	if (CTEditHelper.cmsName === 'Joomla') {
		if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0) {
			addParams.push('option=com_customtables');
			addParams.push('view=edititem');
			addParams.push('ModuleId=' + ModuleId);
		} else {
			addParams.push('Itemid=' + CTEditHelper.itemId);
		}
	}

	const url = esPrepareLink(deleteParams, addParams);

	let params = "";

	params += valueParam;
	params += "&task=save";
	params += "&listing_id=" + record_id;

	const obj = document.getElementById(ctFieldInputPrefix + record_id + "_" + fieldname + postfix + "_div");
	if (obj) obj.className = "ct_loader";

	let http = CreateHTTPRequestObject();   // defined in ajax.js

	if (http) {
		http.open("POST", url + "&clean=1", true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function () {
			if (http.readyState === 4) {

				let response;

				try {
					response = JSON.parse(http.response.toString());
				} catch (e) {
					return console.error(e);
				}

				if (response.status === "saved") {
					obj.className = "ct_checkmark ct_checkmark_hidden";//+css_class;
				} else if (response.status === "error") {
					obj.className = "ct_checkmark_err";
					alert(response.message);
				} else {
					obj.className = "ct_checkmark_err ";

					if (http.response.indexOf('<div class="alert-message">Nothing to save</div>') !== -1)
						alert(TranslateText('COM_CUSTOMTABLES_JS_NOTHING_TO_SAVE'));
					else if (http.response.indexOf('view-login') !== -1)
						alert(TranslateText('COM_CUSTOMTABLES_JS_SESSION_EXPIRED'));
					else alert(http.response);
				}
			}
		};
		http.send(params);
	}
}

function ctCatalogUpdate(tableid, recordsId, row_index, ModuleId) {

	let element_tableid = "ctTable_" + tableid;

	let deleteParams = ['task', "listing_id", 'returnto', 'ids', 'option', 'view', 'clean', 'component', 'frmt'];
	let addParams = ['listing_id=' + recordsId, 'number=' + row_index, 'clean=1'];

	if (CTEditHelper.cmsName === 'Joomla') {
		if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0) {
			addParams.push('option=com_customtables');
			addParams.push('view=catalog');
			addParams.push('ModuleId=' + ModuleId);
		} else {
			addParams.push('Itemid=' + CTEditHelper.itemId);
		}
	}

	let url = esPrepareLink(deleteParams, addParams);

	let params = "";
	let http = CreateHTTPRequestObject();   // defined in ajax.js

	if (http) {
		http.open("GET", url, true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function () {

			if (http.readyState === 4) {
				let res = http.response;
				let tableObj = document.getElementById(element_tableid);
				if (tableObj) {
					let rows = tableObj.rows;
					if (rows) {
						if (rows[row_index])
							rows[row_index].innerHTML = res;
					}
				}
			}
		}
		http.send(params);
	}
}

function getContainerElementIDTable(obj) {

	while (true) {
		let parts = obj.id.split('_');
		if (parts[0] === 'ctTable') {
			return parts;
		}

		obj = obj.parentElement;
		if (obj == null) return null;
	}
}

function ctCatalogOnDrop(event, ModuleId) {
	event.preventDefault();
	const importFromId = event
		.dataTransfer
		.getData('text');

	let to_parts = getContainerElementIDTable(event.target);
	if (to_parts == null) return false;

	let to_id = to_parts.join("_");

	if (importFromId === to_id) return false;

	if (confirm("Do you want to copy field content to target record?") === true) {

		let from_parts = importFromId.split('_');

		let from = from_parts[2] + '_' + from_parts[3];
		let to = to_parts[2] + '_' + to_parts[3];
		let element_tableid_tr = "ctTable_" + to_parts[1] + '_' + to_parts[2];

		let table_object = document.getElementById("ctTable_" + to_parts[1]);

		let index;
		if (table_object) index = findRowIndexById("ctTable_" + to_parts[1], element_tableid_tr);

		let deleteParams = ['task', "listing_id", 'returnto', 'ids', 'option', 'view', 'clean', 'component', 'frmt'];
		let addParams = ['task=copycontent', 'from=' + from, 'to=' + to, 'clean=1', 'tmpl=component', 'frmt=json'];

		if (CTEditHelper.cmsName === 'Joomla') {
			if (typeof ModuleId !== 'undefined' && ModuleId !== null && ModuleId !== 0) {
				addParams.push('option=com_customtables');
				addParams.push('view=catalog');
				addParams.push('ModuleId=' + ModuleId);
			} else {
				addParams.push('Itemid=' + CTEditHelper.itemId);
			}
		}

		let url = esPrepareLink(deleteParams, addParams);

		fetch(url)
			.then(r => r.json())
			.then(r => {
				if (r.error) {
					alert(r.error);
					return false;
				} else {
					if (table_object) ctCatalogUpdate(to_parts[1], to_parts[2], index, ModuleId);
				}
			})
			.catch(error => console.error("Error", error));

		return true;
	} else {
		return false;
	}
}

function ctCatalogOnDragStart(event) {
	event
		.dataTransfer
		.setData('text/plain', event.target.id);
}

function ctCatalogOnDragOver(event) {
	event.preventDefault();
}

function ctEditModal(url, parentFieldToUpdate = null) {

	let new_url = url + (url.indexOf('?') === -1 ? '?' : '&') + 'modal=1&time=' + Date.now();
	let params = "";

	if (parentFieldToUpdate !== null) new_url += '&parentfield=' + parentFieldToUpdate

	let http = CreateHTTPRequestObject();   // defined in ajax.js

	if (http) {
		http.open("GET", new_url, true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function () {
			if (http.readyState === 4) {
				let res = http.response;

				ctShowPopUp(res, true);

				//Activate Calendars if found
				let elements = document.querySelectorAll(".field-calendar");

				for (let i = 0, l = elements.length; i < l; i++) {
					JoomlaCalendar.init(elements[i]);
				}
			}
		}
		http.send(params);
	}
}

function ctUpdateCheckboxCounter(tableid) {

	let counterID = 'ctTable' + tableid + 'CheckboxCount';
	let counterIDObj = document.getElementById(counterID);
	if (counterIDObj) {

		let count = 0;
		let elements = document.getElementsByName("esCheckbox" + tableid);
		for (let i = 0; i < elements.length; i++) {
			if (elements[i].checked) count += 1;
		}

		counterIDObj.innerHTML = count.toString();
	}
}

function ctValue_googlemapcoordinates(boxId, lat, long, zoom) {

	setTimeout(function () {
		let cursorPoint = new google.maps.LatLng(lat, long);
		let map_obj = document.getElementById(boxId);

		gmapdata[boxId] = new google.maps.Map(map_obj, {
			center: cursorPoint, zoom: zoom, mapTypeId: 'roadmap'
		});

		gmapmarker[boxId] = new google.maps.Marker({
			map: gmapdata[boxId], position: cursorPoint
		});

		let infoWindow = new google.maps.InfoWindow;
	}, 500)
	return false;
}

function ctSearchBarDateRangeUpdate(fieldName) {
	setTimeout(function () {
		let obj = document.getElementById(ctFieldInputPrefix + "search_box_" + fieldName);
		let date_start = document.getElementById(ctFieldInputPrefix + "search_box_" + fieldName + "_start").value
		let date_end = document.getElementById(ctFieldInputPrefix + "search_box_" + fieldName + "_end").value;
		obj.value = date_start + "-to-" + date_end;
	}, 300)
}