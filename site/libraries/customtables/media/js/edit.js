/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2024. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
class CustomTablesEdit {

    constructor() {

    }

    //A method to create or update table records using JavaScript. CustomTables handles data sanitization and validation.
    saveRecord(url, fieldsAndValues, listing_id, successCallback, errorCallback) {

        let completeURL = url + '?view=edititem&task=save&tmpl=component&clean=1';
        if (listing_id !== undefined && listing_id !== null)
            completeURL += '&listing_id=' + listing_id;

        let postData = new URLSearchParams();

        // Iterate over keysObject and append each key-value pair
        for (const key in fieldsAndValues) {
            if (fieldsAndValues.hasOwnProperty(key)) {
                postData.append('comes_' + key, fieldsAndValues[key]);
            }
        }

        fetch(completeURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: postData,
        })
            .then(response => {
                if (response.redirected) {
                    if (errorCallback && typeof errorCallback === 'function') {
                        errorCallback('Login required or not authorized.');
                    } else {
                        console.error('Login required or not authorized. Error status code 200: Redirect.');
                    }
                    return null;
                }

                if (!response.ok) {
                    // If the HTTP status code is not successful, throw an error object that includes the response
                    throw {status: 'error', message: 'HTTP status code: ' + response.status, response: response};
                    return null;
                }
                return response.json();
            })
            .then(data => {
                if (data === null)
                    return;

                if (data.status === 'saved') {
                    if (successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    } else {

                    }
                } else if (data.status === 'error') {
                    if (errorCallback && typeof errorCallback === 'function') {
                        errorCallback(data);
                    } else {
                        console.error(data.message);
                    }
                }
            })
            .catch(error => {
                if (errorCallback && typeof errorCallback === 'function') {
                    errorCallback({
                        status: 'error',
                        message: 'An error occurred during the request.',
                    });
                } else {
                    console.error('Error', error);
                    console.log(completeURL);
                }
            });
    }


    async refreshRecord(url, listing_id, successCallback, errorCallback) {
        let completeURL = url + '?tmpl=component&clean=1&task=refresh';
        if (listing_id !== undefined && listing_id !== null)
            completeURL += '&ids=' + listing_id;

        try {
            const response = await fetch(completeURL);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();
            console.log(data);
        } catch (error) {
            console.error('There was a problem with the fetch operation:', error);
        }

        //let postData = new URLSearchParams();
        //postData.append('task', 'refresh');

        fetch(completeURL, {
            method: 'GET'
        })
            .then(response => {

                if (response.redirected) {
                    if (errorCallback && typeof errorCallback === 'function') {
                        errorCallback('Login required or not authorized.');
                    } else {
                        console.error('Login required or not authorized. Error status code 200: Redirect.');
                    }
                    return null;
                }

                if (!response.ok) {
                    // If the HTTP status code is not successful, throw an error object that includes the response
                    throw {status: 'error', message: 'HTTP status code: ' + response.status, response: response};
                    return null;
                }
                return response.json();
            })
            .then(data => {
                if (data === null)
                    return;

                if (data.status === 'saved') {
                    if (successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    } else {

                    }
                } else if (data.status === 'error') {
                    if (errorCallback && typeof errorCallback === 'function') {
                        errorCallback(data);
                    } else {
                        console.error(data.message);
                    }
                }
            })
            .catch(error => {
                if (errorCallback && typeof errorCallback === 'function') {
                    errorCallback({
                        status: 'error',
                        message: 'An error occurred during the request.',
                    });
                } else {
                    console.error('Error 145:', error);
                    console.log(completeURL);
                }
            });
    }

    //Reloads a particular table row (record) after changes have been made. It identifies the table and the specific row based on the provided listing_id and then triggers a refresh to update the displayed data.
    reloadRecord(listing_id) {

        // Select all table elements whose id attribute starts with 'ctTable_'
        const tables = document.querySelectorAll('table[id^="ctTable_"]');
        tables.forEach(table => {
            let parts = table.id.split("_");
            if (parts.length === 2) {
                let tableId = parts[1];
                let trId = 'ctTable_' + tableId + '_' + listing_id;
                const records = table.querySelectorAll('tr[id^="' + trId + '"]');
                if (records.length == 1) {
                    let index = findRowIndexById(table.id, trId);
                    ctCatalogUpdate(tableId, listing_id, index);
                }
            }
        });
    }
}

//---------------------------------

let ctItemId = 0;

function setTask(event, task, returnLink, submitForm, formName, isModal, modalFormParentField) {

    event.preventDefault();

    if (returnLink !== "") {
        let obj = document.getElementById('returnto');
        if (obj)
            obj.value = returnLink;
    }

    let obj2 = document.getElementById('task');
    if (obj2)
        obj2.value = task;
    else
        alert("Task Element not found.");

    if (submitForm) {
        let objForm = document.getElementById(formName);
        if (objForm) {
            const tasks_with_validation = ['saveandcontinue', 'save', 'saveandprint', 'saveascopy'];
            if (isModal && task !== 'saveascopy') {
                let hideModelOnSave = true;
                if (task === 'saveandcontinue')
                    hideModelOnSave = false;

                if (tasks_with_validation.includes(task)) {
                    if (checkRequiredFields(objForm))
                        submitModalForm(objForm.action, objForm.elements, objForm.dataset.tableid, objForm.dataset.recordid, hideModelOnSave, modalFormParentField, returnLink)
                } else
                    submitModalForm(objForm.action, objForm.elements, objForm.dataset.tableid, objForm.dataset.recordid, hideModelOnSave, modalFormParentField, returnLink)

                return false;
            } else {
                if (tasks_with_validation.includes(task)) {
                    if (checkRequiredFields(objForm))
                        objForm.submit();
                } else {
                    objForm.submit();
                }
            }
        } else
            alert("Form not found.");
    }
}

function stripInvalidCharacters(str) {
    // This regular expression matches all non-printable ASCII characters
    return str.replace(/[^\x20-\x7E]/g, '');
}

function submitModalForm(url, elements, tableid, recordId, hideModelOnSave, modalFormParentField, returnLinkEncoded) {

    let params = "";
    let opt;
    for (let i = 0; i < elements.length; i++) {
        if (elements[i].name && elements[i].name !== '' && elements[i].name !== 'returnto') {

            if (elements[i].type === "select-multiple") {

                const options = elements[i] && elements[i].options;

                for (let x = 0; x < options.length; x++) {
                    opt = options[x];
                    if (opt.selected)
                        params += "&" + elements[i].name + "=" + opt.value;
                }

            } else
                params += "&" + elements[i].name + "=" + elements[i].value;
        }
    }

    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {

        http.open("POST", url + "&clean=1&ctmodalform=1&load=1", true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {
            if (http.readyState === 4) {
                let response;

                try {
                    response = JSON.parse(http.response.toString());
                } catch (e) {
                    console.log(url + "&clean=1&ctmodalform=1&load=1");
                    console.log(http.response.toString());
                    return console.error(e);
                }

                if (response.status === "saved") {
                    let element_tableid_tr = "ctTable_" + tableid + '_' + recordId;
                    let table_object = document.getElementById("ctTable_" + tableid);

                    if (table_object) {
                        let index = findRowIndexById("ctTable_" + tableid, element_tableid_tr);
                        ctCatalogUpdate(tableid, recordId, index);
                    }

                    if (modalFormParentField !== null) {
                        let parts = modalFormParentField.split('.');
                        let parentField = parts[1];
                        refreshTableJoinField(parentField, response);
                    }

                    if (hideModelOnSave)
                        ctHidePopUp();

                    if (returnLinkEncoded !== "")
                        location.href = stripInvalidCharacters(Base64.decode(returnLinkEncoded));

                } else {
                    if (http.response.indexOf('<div class="alert-message">Nothing to save</div>') !== -1)
                        alert('Nothing to save. Check Edit From layout.');
                    else if (http.response.indexOf('view-login') !== -1)
                        alert(TranslateText('COM_CUSTOMTABLES_JS_SESSION_EXPIRED'));
                    else
                        alert(http.response);
                }
            }
        };
        http.send(params);
    }
}

/*
function recaptchaCallback() {
    let obj1 = document.getElementById("customtables_submitbutton");
    if (typeof obj1 != "undefined")
        obj1.removeAttribute('disabled');

    let obj2 = document.getElementById("customtables_submitbuttonasnew");
    if (typeof obj2 != "undefined")
        obj2.removeAttribute('disabled');
}
*/

function checkFilters() {

    let passed = true;
    let inputs = document.getElementsByTagName('input');

    for (let i = 0; i < inputs.length; i++) {
        let t = inputs[i].type.toLowerCase();

        if (t === 'text' && inputs[i].value !== "") {
            //let n = inputs[i].name.toString();
            let d = inputs[i].dataset;
            let label = "";

            if (d.label)
                label = d.label;

            if (d.sanitizers)
                doSanitanization(inputs[i], d.sanitizers);

            if (d.filters) {
                passed = doFilters(inputs[i], label, d.filters);
                if (!passed)
                    return false;
            }

            if (d.valuerule) {
                let caption = "";
                if (d.valuerulecaption)
                    caption = d.valuerulecaption;

                passed = doValueRules(inputs[i], label, d.valuerule, caption);
                if (!passed)
                    return false;
            }
        }
    }
    return passed;
}

//https://stackoverflow.com/questions/5717093/check-if-a-javascript-string-is-a-url
function isValidURL(str) {
    let regex = /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/;
    return regex.test(str);
}

function doValueRules(obj, label, valueRules, caption) {
    let ct_fieldName = obj.name.replaceAll('comes_', '');
    let value_rules_and_arguments = doValuerules_ParseValues(valueRules, ct_fieldName);

    if (value_rules_and_arguments === null)
        return true;

    let result = false;

    let rules_str = "return " + value_rules_and_arguments.new_valuerules;

    try {
        let rules = new Function(rules_str); // this |x| refers global |x|
        result = rules(value_rules_and_arguments.new_args);
    } catch (error) {
        return true;//TODO replace it with JS Twig
    }

    if (result)
        return true;

    if (caption === '')
        caption = 'Invalid value for "' + label + '"';

    alert(caption);
    return false;
}

function doValuerules_ParseValues(valuerules, ct_fieldName) {
    //let matches=valuerules.match(/(?<=\[)[^\][]*(?=])/g);  Doesn't work on Safari
    let matches = valuerules.match(/\[(.*?)\]/g); // return example: ["[subject]","[date]"]

    if (matches == null) {
        valuerules = '[' + ct_fieldName + ']' + valuerules;
        matches = valuerules.match(/\[(.*?)\]/g); // return example: ["[subject]","[date]"]

        if (matches == null)
            return null;
    }

    let args = [];

    for (let i = 0; i < matches.length; i++) {
        let fieldname = matches[i].replace("[", "").replace("]", "");
        let objID = "comes_" + fieldname;

        let obj = document.getElementById(objID);

        if (obj) {
            valuerules = valuerules.replaceAll("[" + fieldname + "]", 'arguments[0][' + i + ']');
            args[i] = obj.value;
        }
    }
    return {new_valuerules: valuerules, new_args: args};
}

function doFilters(obj, label, filters_string) {
    let filters = filters_string.split(",");
    let value = obj.value;

    for (let i = 0; i < filters.length; i++) {
        let filter_parts = filters[i].split(':');
        let filter = filter_parts[0];

        if (filter === 'email') {
            // /^[^\s@]+@[^\s@]+\.[^\s@]+$/

            let lastAtPos = value.lastIndexOf('@');
            let lastDotPos = value.lastIndexOf('.');
            let isEmailValid = (lastAtPos < lastDotPos && lastAtPos > 0 && value.indexOf('@@') === -1 && lastDotPos > 2 && (value.length - lastDotPos) > 2);
            if (!isEmailValid) {
                alert(TranslateText('COM_CUSTOMTABLES_JS_EMAIL_INVALID', label, value));
                return false;
            }
        } else if (filter === 'url') {
            if (!isValidURL(value)) {
                alert(TranslateText('COM_CUSTOMTABLES_JS_URL_INVALID', label, value));
                return false;
            }
        } else if (filter === 'https') {
            if (value.indexOf("https") !== 0) {
                alert(TranslateText('COM_CUSTOMTABLES_JS_SECURE_URL_INVALID', label, value));
                return false;
            }
        } else if (filter === 'domain' && filter_parts.length > 1) {
            let domain = filter_parts[1].split(",");
            let hostname = "";

            try {
                hostname = (new URL(value)).hostname;

            } catch (err) {
                alert(TranslateText('COM_CUSTOMTABLES_JS_URL_INVALID', label, value));
                return false;
            }
            console.log("hostname: " + hostname);
            console.log("domain: " + domain);

            /*
            let found = false;
            for (let f = 0; f < domains.length; f++) {

                if (domains[f] === hostname) {
                    found = true;
                    break;
                }
            }
*/
            if (domain !== hostname && 'www.' + domain !== hostname && domain !== 'www.' + hostname) {
                alert(TranslateText('COM_CUSTOMTABLES_JS_HOSTNAME_INVALID', value, label, filter_parts[1]));
                return false;
            }
        }
    }
    return true;
}

function doSanitanization(obj, sanitizers_string) {
    let sanitizers = sanitizers_string.split(",");
    let value = obj.value;

    for (let i = 0; i < sanitizers.length; i++) {
        if (sanitizers[i] === 'trim')
            value = value.trim();
    }

    obj.value = value;
}

function checkRequiredFields(formObject) {
    if (!checkFilters())
        return false;

    if (ct_signaturePad_fields.length > 0) {
        if (!ctInputbox_signature_apply()) {
            event.preventDefault();
            return false;
        }
    }

    let requiredFields = formObject.getElementsByClassName("required");
    let label = "One field";

    for (let i = 0; i < requiredFields.length; i++) {
        if (typeof requiredFields[i].id != "undefined") {
            if (requiredFields[i].id.indexOf("sqljoin_table_comes_") !== -1) {
                if (!CheckSQLJoinRadioSelections(requiredFields[i].id))
                    return false;
            }
            if (requiredFields[i].id.indexOf("ct_uploadfile_box_") !== -1) {
                if (!CheckImageUploader(requiredFields[i].id)) {
                    let d = requiredFields[i].dataset;
                    if (d.label)
                        label = d.label;
                    else
                        label = "Unlabeled field";

                    let imageObjectName = requiredFields[i].id + '_image';
                    let imageObject = document.getElementById(imageObjectName);

                    if (imageObject)
                        return true;

                    alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
                    return false;
                }
            }
        }

        if (typeof requiredFields[i].name != "undefined") {
            let n = requiredFields[i].name.toString();

            if (n.indexOf("comes_") !== -1) {

                let objName = n.replace('_selector', '');

                let d = requiredFields[i].dataset;
                if (d.label)
                    label = d.label
                else
                    label = "Unlabeled field";

                if (d.type === 'sqljoin') {
                    if (requiredFields[i].type === "hidden") {
                        let obj = document.getElementById(objName);

                        if (obj.value === '') {
                            alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
                            return false;
                        }
                    }

                } else if (requiredFields[i].type === "text") {
                    let obj = document.getElementById(objName);
                    if (obj.value === '') {
                        alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
                        return false;
                    }
                } else if (requiredFields[i].type === "select-one") {
                    let obj = document.getElementById(objName);

                    if (obj.value === null || obj.value === '') {
                        alert(TranslateText('COM_CUSTOMTABLES_NOT_SELECTED', label));
                        return false;
                    }
                } else if (requiredFields[i].type === "select-multiple") {
                    let count_multiple_obj = document.getElementById(lbln);
                    let options = count_multiple_obj.options;
                    let count_multiple = 0;

                    for (let i2 = 0; i2 < options.length; i2++) {
                        if (options[i2].selected)
                            count_multiple++;
                    }

                    if (count_multiple === 0) {
                        alert(TranslateText('COM_CUSTOMTABLES_NOT_SELECTED', label));
                        return false;
                    }
                } else if (d.selector == 'switcher') {
                    //Checkbox element with Yes/No visual effect
                    if (d.label)
                        label = d.label;
                    else
                        label = "Unlabeled field";

                    if (requiredFields[i].value === "1") {

                        if (d.valuerulecaption && d.valuerulecaption !== "")
                            alert(d.valuerulecaption);
                        else
                            alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
                        return false;
                    }
                } else if (d.type == 'checkbox') {
                    //Simple HTML Checkbox element
                    if (d.label)
                        label = d.label;
                    else
                        label = "Unlabeled field";

                    if (!requiredFields[i].checked) {
                        if (d.valuerulecaption && d.valuerulecaption !== "")
                            alert(d.valuerulecaption);
                        else
                            alert(TranslateText('COM_CUSTOMTABLES_REQUIRED', label));
                        return false;
                    }
                }
            }
        }
    }
    return true;
}

function TranslateText() {
    if (arguments.length == 0)
        return 'Nothing to translate';

    let str;

    if (typeof Joomla !== 'undefined' && Joomla.JText && typeof Joomla.JText._ === 'function') {
        // Joomla JText class exists
        str = Joomla.JText._(arguments[0]);
        // Use the JText class as needed
    } else {
        // Joomla JText class does not exist or is not properly loaded
        // Handle the situation accordingly
        str = arguments[0];
    }

    if (arguments.length == 1)
        return str;

    for (let i = 1; i < arguments.length; i++)
        str = str.replace('%s', arguments[i]);

    return str;
}

function SetUnsetInvalidClass(id, isValid) {
    let obj = document.getElementById(id);
    if (isValid) {
        obj.classList.remove("invalid");
    } else {
        obj.classList.add("invalid");
    }
}

function CheckImageUploader(id) {
    let objId = id.replace("ct_uploadfile_box_", "comes_");
    let obj = document.getElementById(objId);
    if (obj.value === "") {
        SetUnsetInvalidClass(id, false);
        return false;
    }
    SetUnsetInvalidClass(id, true);
    return true;
}

function CheckSQLJoinRadioSelections(id) {
    let field_name = id.replace('sqljoin_table_comes_', '');
    let obj_name = 'comes_' + field_name;
    let radios = document.getElementsByName(obj_name);

    let selected = false;
    for (let i = 0; i < radios.length; i++) {
        if (radios[i].type === 'radio' && radios[i].checked) {
            selected = true;
            break;
        }
    }

    if (!selected) {
        let labelObject = document.getElementById(obj_name + "-lbl");
        let label = labelObject.innerHTML;
        alert(Joomla.JText._('COM_CUSTOMTABLES_NOT_SELECTED', label));
        return false;
    }

    return true;
}

function recaptchaCallback() {
    let buttons = ['save', 'saveandclose', 'saveandprint', 'saveandcopy', 'delete'];
    for (let i = 0; i < buttons.length; i++) {
        let button = 'customtables_button_' + buttons[i];
        let obj = document.getElementById(button);

        if (obj)
            obj.disabled = false;
    }
}

function decodeHtml(html) {
    let txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}

function ctRenderTableJoinSelectBox(control_name, r, index, execute_all, sub_index, parent_object_id, formId, forceValue) {

    let wrapper = document.getElementById(control_name + "Wrapper");
    let filters = [];
    if (wrapper.dataset.valuefilters !== '') {
        let decodedFilterString = Base64.decode(wrapper.dataset.valuefilters).replace(/[^ -~]+/g, "");
        filters = JSON.parse(decodedFilterString);
    }

    let attributesStringDataSet = Base64.decode(wrapper.dataset.attributes);

    //The code searches the string for any characters that are not in the printable ASCII range (from space to tilde).
    //It replaces any such characters with an empty string, effectively removing them from the string.
    let attributesStringClean = attributesStringDataSet.replace(/[^ -~]+/g, "");
    //let attributes = JSON.parse(attributesStringClean);
    //let onchange = Base64.decode(wrapper.dataset.onchange).replace(/[^ -~]+/g, "");

    let next_index = index;
    let next_sub_index = sub_index;
    let val;

    if (Array.isArray(filters[index])) {
        //Self Parent field
        next_sub_index += 1;
        if (next_sub_index === filters[index].length) {
            // Max sub index reached
            /*
            next_sub_index = 0;
            next_index += 1;

            if(Array.isArray(filters[next_index]))
                val = filters[next_index][next_sub_index];
            else
                val = filters[next_index];
            */
            val = null;
        } else
            val = filters[next_index][next_sub_index];

    } else {
        next_index += 1;
        val = filters[next_index];
    }

    if (!execute_all)
        val = null;

    if (r.error) {
        alert(r.error);
        return false;
    }

    if (r.length === 0) {
        if (Array.isArray(filters[next_index])) {

            next_sub_index = 0;
            next_index += 1;

            if (next_index + 1 < filters.length) {
                document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = '<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';
                ctUpdateTableJoinLink(control_name, next_index, false, next_sub_index, parent_object_id, formId, false, null);
                return false;
            } else {
                let selectorObject = document.getElementById(control_name + "Selector" + index + '_' + sub_index);

                if (selectorObject) {
                    selectorObject.innerHTML = '';//No items to select';//..<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';
                } else {
                    return false;
                }
            }
        } else {

            let NoItemsText;

            if (typeof wrapper.dataset.addrecordmenualias !== 'undefined' && wrapper.dataset.addrecordmenualias !== '') {
                let js = 'ctTableJoinAddRecordModalForm(\'' + control_name + '\',' + sub_index + ');';
                let addText = TranslateText('COM_CUSTOMTABLES_ADD');
                NoItemsText = addText + '<a href="javascript:' + js + '" className="toolbarIcons"><img src="/components/com_customtables/libraries/customtables/media/images/icons/new.png" alt="' + addText + '" title="' + addText + '"></a>';
            } else
                NoItemsText = TranslateText('COM_CUSTOMTABLES_SELECT_NOTHING')

            document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = NoItemsText;
            return false;
        }
    }

    let result = '';
    let cssClass = 'form-select valid form-control-success';
    let objForm = document.getElementById(formId);
    if (objForm && objForm.dataset.version < 4)
        cssClass = 'inputbox';

    //Add select box
    let current_object_id = control_name + index + (Array.isArray(filters[index]) ? '_' + sub_index : '');

    if (r.length > 0) {

        let updateValueString = (index + 1 === filters.length ? 'true' : 'false');
        let onChangeFunction = 'ctUpdateTableJoinLink(\'' + control_name + '\', ' + next_index + ', false, ' + next_sub_index + ',\'' + current_object_id + '\', \'' + formId + '\', ' + updateValueString + ',null);'
        let onChangeAttribute = ' onChange="' + onChangeFunction + onchange + '"';
        //[' + index + ',' + filters.length + ']
        result += '<select id="' + current_object_id + '"' + onChangeAttribute + ' class="' + cssClass + '">';
        result += '<option value="">- ' + TranslateText('COM_CUSTOMTABLES_SELECT') + '</option>';

        if (typeof wrapper.dataset.addrecordmenualias !== 'undefined' && wrapper.dataset.addrecordmenualias !== '')
            result += '<option value="%addRecord%">- ' + TranslateText('COM_CUSTOMTABLES_ADD') + '</option>';

        for (let i = 0; i < r.length; i++) {
            let optionLabel = decodeHtml(r[i].label);
            result += '<option value="' + r[i].value + '">' + optionLabel + '</option>';
        }

        result += '</select>';

        //Prepare the space for next elements
        result += '<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';
    }

    //Add content to the element
    if (document.getElementById(control_name + "Selector" + index + '_' + (sub_index + 1)))
        document.getElementById(control_name + "Selector" + index + '_' + (sub_index + 1)).innerHTML = result;

    if (forceValue !== null) {
        let obj = document.getElementById(current_object_id);
        obj.value = forceValue;
    }

    if (r.length > 0) {
        if (execute_all && next_index + 1 < filters.length && val != null) {
            ctUpdateTableJoinLink(control_name, next_index, true, next_sub_index, null, formId, false, null);
        }
    }
}

function ctTableJoinAddRecordModalForm(control_name, sub_index) {

    let wrapper = document.getElementById(control_name + "Wrapper");

    let query = ctWebsiteRoot + 'index.php/' + wrapper.dataset.addrecordmenualias;
    if (wrapper.dataset.addrecordmenualias.indexOf('?') === -1)
        query += '?';
    else
        query += '&';

    query += 'view=edititem';

    let parentObjectValue = null;
    let sub_indexObject = document.getElementById(control_name + sub_index);
    if (sub_indexObject) {
        parentObjectValue = sub_indexObject.value;
        query += '&es_' + sub_indexObject.dataset.childtablefield + '=' + parentObjectValue;
    }
    ctEditModal(query, wrapper.dataset.formname + '.' + wrapper.dataset.fieldname)
}

function ctUpdateTableJoinLink(control_name, index, execute_all, sub_index, object_id, formId, updateValue, forceValue) {

    let wrapper = document.getElementById(control_name + "Wrapper");
    let link = location.href.split('administrator/index.php?option=com_customtables');
    let url;

    if (link.length === 2)//to make sure that it will work in the back-end
        url = 'index.php?option=com_customtables&view=records&from=json&key=' + wrapper.dataset.key + '&index=' + index;
    else
        url = 'index.php?option=com_customtables&view=catalog&tmpl=component&from=json&key=' + wrapper.dataset.key + '&index=' + index;

    let filters = [];
    if (wrapper.dataset.valuefilters !== '') {
        let decodedFilterString = Base64.decode(wrapper.dataset.valuefilters).replace(/[^ -~]+/g, "");
        filters = JSON.parse(decodedFilterString);
    }

    if (execute_all) {
        if (Array.isArray(filters[index])) {
            //Self Parent field
            if (filters[index][sub_index] !== '')
                url += '&subfilter=' + filters[index][sub_index];
        } else if (filters[index] !== '')
            url += '&filter=' + filters[index];
    } else {

        let valueObj = document.getElementById(control_name);
        let obj = document.getElementById(object_id);

        if (forceValue !== null) {
            valueObj.value = forceValue;
        } else {
            if (updateValue) {
                if (obj.value === "") {

                    let indexTemp = index;
                    let sub_indexTemp = sub_index;

                    if (sub_indexTemp > 0)
                        sub_indexTemp -= 2;
                    else {
                        //TODO: descend IndexTemp
                    }

                    if (sub_indexTemp >= 0) {
                        let tempCurrent_object_id = control_name + indexTemp + (Array.isArray(filters[indexTemp]) ? '_' + sub_indexTemp : '');
                        let objTemp = document.getElementById(tempCurrent_object_id);

                        if (objTemp === null)
                            valueObj.value = obj.value;
                        else
                            valueObj.value = objTemp.value;
                    } else
                        valueObj.value = obj.value;
                } else
                    valueObj.value = obj.value;
            }

            if (obj.value == "") {
                //Empty everything after
                document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = '';//"Not selected";
                return false;
            } else if (obj.value == "%addRecord%") {
                ctTableJoinAddRecordModalForm(control_name, sub_index);
            }
        }

        if (Array.isArray(filters[index]))
            url += '&subfilter=' + obj.value;
        else
            url += '&filter=' + obj.value;
    }

    if (index >= filters.length)
        return false;

    ctRenderTableJoinSelectBoxLoadRecords(url, control_name, index, execute_all, sub_index, object_id, formId, forceValue)
}

function ctRenderTableJoinSelectBoxLoadRecords(url, control_name, index, execute_all, sub_index, object_id, formId, forceValue) {

    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("GET", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {
            if (http.readyState === 4) {
                let response;

                try {
                    response = JSON.parse(http.response.toString());
                } catch (e) {
                    console.log(http.response.toString());
                    return console.error(e);
                }
                ctRenderTableJoinSelectBox(control_name, response, index, execute_all, sub_index, object_id, formId, forceValue);
            }
        };
        http.send();
    }
}

// --------------------- Inputbox: Records

let ctInputBoxRecords_r = [];
let ctInputBoxRecords_v = [];
let ctInputBoxRecords_p = [];
let ctInputBoxRecords_dynamic_filter = [];
let ctInputBoxRecords_current_value = [];

function ctInputBoxRecords_removeOptions(selectobj) {
    //Old calls replaced
    for (let i = selectobj.options.length - 1; i >= 0; i--) {
        selectobj.remove(i);
    }
}

function ctInputBoxRecords_addItem(control_name, control_name_postfix) {
    //Old calls replaced
    let o = document.getElementById(control_name + control_name_postfix);
    o.selectedIndex = 0;

    if (ctInputBoxRecords_dynamic_filter[control_name] != '') {

        ctInputBoxRecords_current_value[control_name] = "";

        let SQLJoinLink = document.getElementById(control_name + control_name_postfix + 'SQLJoinLink');
        if (SQLJoinLink) {
            SQLJoinLink.selectedIndex = 0;
            ctInputbox_UpdateSQLJoinLink(control_name, control_name_postfix);
        }
    }

    document.getElementById(control_name + '_addButton').style.visibility = "hidden";
    document.getElementById(control_name + '_addBox').style.visibility = "visible";
}

function ctInputBoxRecords_DoAddItem(control_name, control_name_postfix) {
    //Old calls replaced
    let o = document.getElementById(control_name + control_name_postfix);
    if (o.selectedIndex === -1)
        return;

    let r = o.options[o.selectedIndex].value;
    let t = o.options[o.selectedIndex].text;
    let p = 1;

    if (document.getElementById(control_name + control_name_postfix + '_elementsPublished')) {
        let elementsPublished = document.getElementById(control_name + control_name_postfix + '_elementsPublished').innerHTML.split(",");
        let elementsID = document.getElementById(control_name + control_name_postfix + '_elementsID').innerHTML.split(",");

        for (let i = 0; i < elementsPublished.length; i++) {
            if (elementsID[i] === r)
                p = elementsPublished[i];
        }
    }

    for (let x = 0; x < ctInputBoxRecords_r[control_name].length; x++) {
        if (ctInputBoxRecords_r[control_name][x] === r) {
            alert("Item already exists");
            return false;
        }
    }

    ctInputBoxRecords_r[control_name].push(r);
    ctInputBoxRecords_v[control_name].push(t);
    ctInputBoxRecords_p[control_name].push(p);

    o.remove(o.selectedIndex);
    ctInputBoxRecords_showMultibox(control_name, control_name_postfix);
}

function ctInputBoxRecords_cancel(control_name) {
    //Old calls replaced
    document.getElementById(control_name + '_addButton').style.visibility = "visible";
    document.getElementById(control_name + '_addBox').style.visibility = "hidden";
}

function ctInputBoxRecords_deleteItem(control_name, control_name_postfix, index) {
    //Old calls replaced
    ctInputBoxRecords_r[control_name].splice(index, 1);
    ctInputBoxRecords_v[control_name].splice(index, 1);
    ctInputBoxRecords_p[control_name].splice(index, 1);
    ctInputBoxRecords_showMultibox(control_name, control_name_postfix);
}

function ctInputBoxRecords_showMultibox(control_name, control_name_postfix) {
    //Old calls replaced

    let l = document.getElementById(control_name);// + control_name_postfix);
    ctInputBoxRecords_removeOptions(l);

    let opt1 = document.createElement("option");
    opt1.value = '0';
    opt1.innerHTML = "";
    opt1.setAttribute("selected", "selected");
    l.appendChild(opt1);

    let v = '<table style="width:100%;"><tbody>';
    for (let i = 0; i < ctInputBoxRecords_r[control_name].length; i++) {
        v += '<tr><td style="border-bottom:1px dotted grey;">';
        if (ctInputBoxRecords_p[control_name][i] == 0)
            v += ctInputBoxRecords_v[control_name][i];
        else
            v += ctInputBoxRecords_v[control_name][i];

        v += '</td>';

        let deleteImage = 'components/com_customtables/libraries/customtables/media/images/icons/cancel.png';

        v += '<td style="border-bottom:1px dotted grey;min-width:16px;">';
        let onClick = "ctInputBoxRecords_deleteItem('" + control_name + "','" + control_name_postfix + "'," + i + ")";
        v += '<img src="' + deleteImage + '" alt="Delete" title="Delete" style="width:16px;height:16px;cursor: pointer;" onClick="' + onClick + '" />';
        v += '</td>';
        v += '</tr>';

        const opt = document.createElement("option");
        opt.value = ctInputBoxRecords_r[control_name][i];
        opt.innerHTML = ctInputBoxRecords_v[control_name][i];
        opt.style.cssText = "color:red;";
        opt.setAttribute("selected", "selected");

        l.appendChild(opt);
    }
    v += '</tbody></table>';

    document.getElementById(control_name + "_box").innerHTML = v;
}

/* -------------------------- Filtering --------------------------- */

let ctTranslates = [];

function ctInputbox_removeEmptyParents(control_name, control_name_postfix) {
    //Old calls replaced
    let selectObj = document.getElementById(control_name + 'SQLJoinLink');
    let elementsFilter = document.getElementById(control_name + control_name_postfix + '_elementsFilter').innerHTML.split(";");

    for (let o = selectObj.options.length - 1; o >= 0; o--) {
        let c = 0;
        let v = selectObj.options[o].value;

        for (let i = 0; i < control_name + elementsFilter.length; i++) {
            let f = elementsFilter[i];
            if (typeof f != "undefined") {

                let f_list = f.split(",");

                if (f_list.indexOf(v) !== -1)
                    c++;
            }
        }
    }
}

function ctInputbox_UpdateSQLJoinLink(control_name, control_name_postfix) {
    //Old calls replaced
    setTimeout(ctInputbox_UpdateSQLJoinLink_do(control_name, control_name_postfix), 100);
}

function ctInputbox_UpdateSQLJoinLink_do(control_name, control_name_postfix) {
    //Old calls replaced
    let l = document.getElementById(control_name + control_name_postfix);
    let o = document.getElementById(control_name + 'SQLJoinLink');
    let v = '';

    if (o) {
        if (o.selectedIndex === -1)
            return false;

        v = o.options[o.selectedIndex].value;
    }

    let selectedValue = ctInputBoxRecords_current_value[control_name];
    ctInputBoxRecords_removeOptions(l);

    if (control_name_postfix !== '_selector') {
        let opt = document.createElement("option");
        opt.value = '0';
        opt.innerHTML = ctTranslates["COM_CUSTOMTABLES_SELECT"];
        l.appendChild(opt);
    }

    let elements = JSON.parse(document.getElementById(control_name + control_name_postfix + '_elements').innerHTML);
    let elementsID = document.getElementById(control_name + control_name_postfix + '_elementsID').innerHTML.split(",");
    let elementsFilter = document.getElementById(control_name + control_name_postfix + '_elementsFilter').innerHTML.split(";");
    let elementsPublished = document.getElementById(control_name + control_name_postfix + '_elementsPublished').innerHTML.split(",");

    for (let i = 0; i <= elements.length; i++) {
        let f = elementsFilter[i];
        if (typeof f != "undefined" && elements[i] !== "") {

            let eid = elementsID[i];
            let published = elementsPublished[i];
            let f_list = f.split(",");

            if (f_list.indexOf(v) !== -1) {
                let opt = document.createElement("option");
                opt.value = eid;
                if (eid === selectedValue)
                    opt.selected = true;

                if (published === 0)
                    opt.style.cssText = "color:red;";

                opt.innerHTML = elements[i];
                l.appendChild(opt);
            }
        }
    }

    return true;
}

// ------------------------ Google Map coordinates

let gmapdata = [];
let gmapmarker = [];

function ctInputbox_googlemapcoordinates(inputbox_id) {

    let map_obj = document.getElementById(inputbox_id + "_map");

    if (typeof google === 'undefined') {
        map_obj.innerHTML = 'Custom Tables Configuration: Google Map API Key not provided.';
        map_obj.style.display = "block";
        return false;
    }

    let val = document.getElementById(inputbox_id).value;
    let val_list = val.split(",");

    let def_latval = (val_list[0] !== '' ? parseFloat(val_list[0]) : -8);
    let def_longval = (val_list.length > 1 && val_list[1] !== '' ? parseFloat(val_list[1]) : -79);

    let def_zoomval = (val_list.length > 2 && val_list[2] !== '' ? parseFloat(val_list[2]) : 10);
    if (def_zoomval === 0)
        def_zoomval = 10;

    let curpoint = new google.maps.LatLng(def_latval, def_longval);


    if (map_obj.style.display === "block") {
        map_obj.style.display = "none";
        map_obj.innerHTML = "";
        return false;
    }

    gmapdata[inputbox_id] = new google.maps.Map(map_obj, {
        center: curpoint,
        zoom: def_zoomval,
        mapTypeId: 'roadmap'
    });

    gmapmarker[inputbox_id] = new google.maps.Marker({
        map: gmapdata[inputbox_id],
        position: curpoint
    });

    infoWindow = new google.maps.InfoWindow;

    google.maps.event.addListener(gmapdata[inputbox_id], 'click', function (event) {
        document.getElementById(inputbox_id).value = event.latLng.lat().toFixed(6) + "," + event.latLng.lng().toFixed(6);
        gmapmarker[inputbox_id].setPosition(event.latLng);
    });

    google.maps.event.addListener(gmapmarker[inputbox_id], 'click', function () {
        infoWindow.open(gmapdata[inputbox_id], gmapmarker[inputbox_id]);
    });

    map_obj.style.display = "block";

    return false;
}

let ct_signaturePad_fields = [];
let ct_signaturePad = [];
let ct_signaturePad_formats = [];

function ctInputbox_signature(inputbox_id, width, height, format) {

    let canvas = document.getElementById(inputbox_id + '_canvas');

    ct_signaturePad_fields.push(inputbox_id);
    ct_signaturePad[inputbox_id] = new SignaturePad(canvas, {
        backgroundColor: "rgb(255, 255, 255)"
    });

    ct_signaturePad_formats[inputbox_id] = format;

    canvas.width = width;
    canvas.height = height;
    canvas.getContext("2d").scale(1, 1);

    document.getElementById(inputbox_id + '_clear').addEventListener('click', function () {
        ct_signaturePad[inputbox_id].clear();
    });
}

function ctInputbox_signature_apply() {
    for (let i = 0; i < ct_signaturePad_fields.length; i++) {

        let inputbox_id = ct_signaturePad_fields[i];

        if (ct_signaturePad[inputbox_id].isEmpty()) {
            alert(TranslateText('COM_CUSTOMTABLES_JS_SIGNATURE_REQUIRED'));
            return false;
        } else {

            let format = ct_signaturePad_formats[inputbox_id];

            //let dataURL = ct_signaturePad[inputbox_id].toDataURL('image/'+format+'+xml');
            let dataURL = ct_signaturePad[inputbox_id].toDataURL('image/' + format);
            document.getElementById(inputbox_id).setAttribute("value", dataURL);
            return true;
        }
    }
    return true;
}

/*
function download(dataURL, filename) {
  var blob = dataURLToBlob(dataURL);
  var url = window.URL.createObjectURL(blob);

  var a = document.createElement("a");
  a.style = "display: none";
  a.href = url;
  a.download = filename;

  document.body.appendChild(a);
  a.click();

  window.URL.revokeObjectURL(url);
}

// One could simply use Canvas#toBlob method instead, but It's just to show
// that it can be done using result of SignaturePad#toDataURL.
function dataURLToBlob(dataURL) {
  // Code taken from https://github.com/ebidel/filer.js
  var parts = dataURL.split(\';base64,\');
  var contentType = parts[0].split(":")[1];
  var raw = window.atob(parts[1]);
  var rawLength = raw.length;
  var uInt8Array = new Uint8Array(rawLength);

  for (var i = 0; i < rawLength; ++i) {
    uInt8Array[i] = raw.charCodeAt(i);
  }

  return new Blob([uInt8Array], { type: contentType });
}
*/

//---------------------------------

!function (a, b) {
    "function" == typeof define && define.amd ? define([], function () {
        return a.SignaturePad = b()
    }) : "object" == typeof exports ? module.exports = b() : a.SignaturePad = b()
}(this, function () {/*!
 * Signature Pad v1.3.5 | https://github.com/szimek/signature_pad
 * (c) 2015 Szymon Nowak | Released under the MIT license
 */
    const a = function (a) {
        const d = function (a, b, c, d) {
            this.startPoint = a, this.control1 = b, this.control2 = c, this.endPoint = d
        };
        const c = function (a, b, c) {
            this.x = a, this.y = b, this.time = c || (new Date).getTime()
        };
        "use strict";
        const b = function (a, b) {
            const c = b || {};
            this.velocityFilterWeight = c.velocityFilterWeight || .7, this.minWidth = c.minWidth || .5, this.maxWidth = c.maxWidth || 2.5, this.dotSize = c.dotSize || function () {
                return (this.minWidth + this.maxWidth) / 2
            }, this.penColor = c.penColor || "black", this.backgroundColor = c.backgroundColor || "rgba(0,0,0,0)", this.onEnd = c.onEnd, this.onBegin = c.onBegin, this._canvas = a, this._ctx = a.getContext("2d"), this.clear(), this._handleMouseEvents(), this._handleTouchEvents()
        };
        b.prototype.clear = function () {
            const a = this._ctx, b = this._canvas;
            a.fillStyle = this.backgroundColor, a.clearRect(0, 0, b.width, b.height), a.fillRect(0, 0, b.width, b.height), this._reset()
        }, b.prototype.toDataURL = function () {
            const a = this._canvas;
            return a.toDataURL.apply(a, arguments)
        }, b.prototype.fromDataURL = function (a) {
            const b = this, c = new Image, d = window.devicePixelRatio || 1, e = this._canvas.width / d,
                f = this._canvas.height / d;
            this._reset(), c.src = a, c.onload = function () {
                b._ctx.drawImage(c, 0, 0, e, f)
            }, this._isEmpty = !1
        }, b.prototype._strokeUpdate = function (a) {
            const b = this._createPoint(a);
            this._addPoint(b)
        }, b.prototype._strokeBegin = function (a) {
            this._reset(), this._strokeUpdate(a), "function" == typeof this.onBegin && this.onBegin(a)
        }, b.prototype._strokeDraw = function (a) {
            const b = this._ctx, c = "function" == typeof this.dotSize ? this.dotSize() : this.dotSize;
            b.beginPath(), this._drawPoint(a.x, a.y, c), b.closePath(), b.fill()
        }, b.prototype._strokeEnd = function (a) {
            const b = this.points.length > 2, c = this.points[0];
            !b && c && this._strokeDraw(c), "function" == typeof this.onEnd && this.onEnd(a)
        }, b.prototype._handleMouseEvents = function () {
            const b = this;
            this._mouseButtonDown = !1, this._canvas.addEventListener("mousedown", function (a) {
                1 === a.which && (b._mouseButtonDown = !0, b._strokeBegin(a))
            }), this._canvas.addEventListener("mousemove", function (a) {
                b._mouseButtonDown && b._strokeUpdate(a)
            }), a.addEventListener("mouseup", function (a) {
                1 === a.which && b._mouseButtonDown && (b._mouseButtonDown = !1, b._strokeEnd(a))
            })
        }, b.prototype._handleTouchEvents = function () {
            const b = this;
            this._canvas.style.msTouchAction = "none", this._canvas.addEventListener("touchstart", function (a) {
                const c = a.changedTouches[0];
                b._strokeBegin(c)
            }), this._canvas.addEventListener("touchmove", function (a) {
                a.preventDefault();
                const c = a.changedTouches[0];
                b._strokeUpdate(c)
            }), a.addEventListener("touchend", function (a) {
                const c = a.target === b._canvas;
                c && b._strokeEnd(a)
            })
        }, b.prototype.isEmpty = function () {
            return this._isEmpty
        }, b.prototype._reset = function () {
            this.points = [], this._lastVelocity = 0, this._lastWidth = (this.minWidth + this.maxWidth) / 2, this._isEmpty = !0, this._ctx.fillStyle = this.penColor
        }, b.prototype._createPoint = function (a) {
            const b = this._canvas.getBoundingClientRect();
            return new c(a.clientX - b.left, a.clientY - b.top)
        }, b.prototype._addPoint = function (a) {
            let b, c, e, f, g = this.points;
            g.push(a), g.length > 2 && (3 === g.length && g.unshift(g[0]), f = this._calculateCurveControlPoints(g[0], g[1], g[2]), b = f.c2, f = this._calculateCurveControlPoints(g[1], g[2], g[3]), c = f.c1, e = new d(g[1], b, c, g[2]), this._addCurve(e), g.shift())
        }, b.prototype._calculateCurveControlPoints = function (a, b, d) {
            const e = a.x - b.x, f = a.y - b.y, g = b.x - d.x, h = b.y - d.y,
                i = {x: (a.x + b.x) / 2, y: (a.y + b.y) / 2}, j = {x: (b.x + d.x) / 2, y: (b.y + d.y) / 2},
                k = Math.sqrt(e * e + f * f), l = Math.sqrt(g * g + h * h), m = i.x - j.x, n = i.y - j.y,
                o = l / (k + l), p = {x: j.x + m * o, y: j.y + n * o}, q = b.x - p.x, r = b.y - p.y;
            return {c1: new c(i.x + q, i.y + r), c2: new c(j.x + q, j.y + r)}
        }, b.prototype._addCurve = function (a) {
            let b, c, d = a.startPoint, e = a.endPoint;
            b = e.velocityFrom(d), b = this.velocityFilterWeight * b + (1 - this.velocityFilterWeight) * this._lastVelocity, c = this._strokeWidth(b), this._drawCurve(a, this._lastWidth, c), this._lastVelocity = b, this._lastWidth = c
        }, b.prototype._drawPoint = function (a, b, c) {
            const d = this._ctx;
            d.moveTo(a, b), d.arc(a, b, c, 0, 2 * Math.PI, !1), this._isEmpty = !1
        }, b.prototype._drawCurve = function (a, b, c) {
            let d, e, f, g, h, i, j, k, l, m, n, o = this._ctx, p = c - b;
            for (d = Math.floor(a.length()), o.beginPath(), f = 0; d > f; f++) g = f / d, h = g * g, i = h * g, j = 1 - g, k = j * j, l = k * j, m = l * a.startPoint.x, m += 3 * k * g * a.control1.x, m += 3 * j * h * a.control2.x, m += i * a.endPoint.x, n = l * a.startPoint.y, n += 3 * k * g * a.control1.y, n += 3 * j * h * a.control2.y, n += i * a.endPoint.y, e = b + i * p, this._drawPoint(m, n, e);
            o.closePath(), o.fill()
        }, b.prototype._strokeWidth = function (a) {
            return Math.max(this.maxWidth / (a + 1), this.minWidth)
        };
        c.prototype.velocityFrom = function (a) {
            return this.time !== a.time ? this.distanceTo(a) / (this.time - a.time) : 1
        }, c.prototype.distanceTo = function (a) {
            return Math.sqrt(Math.pow(this.x - a.x, 2) + Math.pow(this.y - a.y, 2))
        };
        return d.prototype.length = function () {
            let a, b, c, d, e, f, g, h, i = 10, j = 0;
            for (a = 0; i >= a; a++) b = a / i, c = this._point(b, this.startPoint.x, this.control1.x, this.control2.x, this.endPoint.x), d = this._point(b, this.startPoint.y, this.control1.y, this.control2.y, this.endPoint.y), a > 0 && (g = c - e, h = d - f, j += Math.sqrt(g * g + h * h)), e = c, f = d;
            return j
        }, d.prototype._point = function (a, b, c, d, e) {
            return b * (1 - a) * (1 - a) * (1 - a) + 3 * c * (1 - a) * (1 - a) * a + 3 * d * (1 - a) * a * a + e * a * a * a
        }, b
    }(document);
    return a
});

function activateJoomla3Tabs() {
    jQuery(function ($) {
        const tabs$ = $(".nav-tabs a");

        $(window).on("hashchange", function () {
            const hash = window.location.hash, // get current hash
                menu_item$ = tabs$.filter('[href="' + hash + '"]'); // get the menu element

            menu_item$.tab("show"); // call bootstrap to show the tab
        }).trigger("hashchange");

        const hash = window.location.hash;
        hash && $('ul.nav a[href="' + hash + '"]').tab('show');

        $('.nav-tabs a').click(function (e) {
            $(this).tab('show');
            const scrollMe = $('body').scrollTop() || $('html').scrollTop();
            window.location.hash = this.hash;
            $('html,body').scrollTop(scrollMe);
        });
    });
}

function setUpdateChildTableJoinField(childFieldName, parentFieldName, childFilterFieldName) {
    document.getElementById('comes_' + parentFieldName + '0').addEventListener('change', function () {
        updateChildTableJoinField(childFieldName, parentFieldName, childFilterFieldName);
    });
}

function updateChildTableJoinField(childFieldName, parentFieldName, childFilterFieldName) {
    //This function updates the list of items in Table Join field based on its parent value;
    let parentValue = document.getElementById('comes_' + parentFieldName).value;
    let wrapper = document.getElementById('comes_' + childFieldName + 'Wrapper');
    let key = wrapper.dataset.key;
    let where = childFilterFieldName + '=' + parentValue;
    let url = 'index.php?option=com_customtables&view=catalog&tmpl=component&from=json&key=' + key + '&index=0&where=' + encodeURIComponent(where);//Base64.encode

    fetch(url)

        .then(r => r.json())
        .then(r => {
            ctRenderTableJoinSelectBox('comes_' + childFieldName, r, 0, false, 0, 'comes_' + childFieldName + '0', wrapper.dataset.formname, null);
        })
        .catch(error => console.error("Error", error));
}

function refreshTableJoinField(fieldName, response) {

    let valueObject = document.getElementById('comes_' + fieldName);
    valueObject.value = response['id'];
    if (valueObject.onchange) {
        valueObject.dispatchEvent(new Event('change'));
    }

    let wrapper = document.getElementById('comes_' + fieldName + 'Wrapper');
    if (wrapper === null)
        return;

    //let valueFiltersStr = Base64.decode(wrapper.dataset.valuefilters).replace(/[^\x00-\x7F]/g, "");
    let valueFiltersNamesStr = Base64.decode(wrapper.dataset.valuefiltersnames).replace(/[^\x00-\x7F]/g, "");
    let valueFiltersNames = JSON.parse(valueFiltersNamesStr);
    let NewValueFilters = [];

    for (let i = 0; i < valueFiltersNames.length; i++) {
        if (valueFiltersNames[i] !== null) {
            let value = response['record']['es_' + valueFiltersNames[i]];
            NewValueFilters.push(value);

            let index = i - 1;
            let selectorID = 'comes_' + fieldName + index;
            let selector = document.getElementById(selectorID);
            selector.value = value;
        } else {
            NewValueFilters.push(null);
        }
    }
    let newValueFiltersStr = JSON.stringify(NewValueFilters);
    wrapper.dataset.valuefilters = Base64.encode(newValueFiltersStr);

    let index = NewValueFilters.length - 1;
    ctUpdateTableJoinLink('comes_' + fieldName, index, true, 0, 'comes_' + fieldName + '0', wrapper.dataset.formname, true, response.id);
}

//Virtual Select
async function onCTVirtualSelectServerSearch(searchValue, virtualSelect) {

    let selectorElement = document.getElementById(virtualSelect.dropboxWrapper);
    //let fieldnameObject = document.getElementById(selectorElement.dataset.fieldname);

    let wrapper = document.getElementById(selectorElement.dataset.wrapper);
    let key = wrapper.dataset.key;
    let url = 'index.php?option=com_customtables&view=catalog&tmpl=component&from=json&key=' + key + '&index=0&limit=20&';
    if (searchValue != "")
        url += "&search=" + searchValue;

    let newList = [];

    try {
        const response = await fetch(url);
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new TypeError("Oops, we haven't got JSON!");
        }
        const jsonData = await response.json();

        for (let i = 0; i < jsonData.length; i++) {

            let doc = new DOMParser().parseFromString(jsonData[i].label, 'text/html');
            let label = doc.documentElement.textContent;

            newList.push({value: jsonData[i].value, label: decodeURI(label)});
        }
        virtualSelect.setServerOptions(newList);
    } catch (error) {
        alert(error);
        console.error("Error:", error);
    }
}

