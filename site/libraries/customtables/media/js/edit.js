let ctItemId = 0;

function setTask(event, task, returnLink, submitForm, formName) {

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

            ctInputbox_signature_apply();

            const tasks_with_validation = ['saveandcontinue', 'save', 'saveandprint', 'saveascopy'];
            let element_tableid = "ctTable_" + objForm.dataset.tableid;
            let table_object = document.getElementById(element_tableid);

            if (table_object && task !== 'saveascopy') {

                let hideModelOnSave = true;
                if (task === 'saveandcontinue')
                    hideModelOnSave = false;

                if (tasks_with_validation.includes(task)) {
                    if (checkRequiredFields())
                        submitModalForm(objForm.action, objForm.elements, objForm.dataset.tableid, objForm.dataset.recordid, hideModelOnSave)
                } else
                    submitModalForm(objForm.action, objForm.elements, objForm.dataset.tableid, objForm.dataset.recordid, hideModelOnSave)

                return false;
            } else {
                if (tasks_with_validation.includes(task)) {
                    if (checkRequiredFields())
                        objForm.submit();
                } else
                    objForm.submit();
            }

        } else
            alert("Form not found.");
    }
}

function submitModalForm(url, elements, tableid, recordid, hideModelOnSave) {

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

        http.open("POST", url + "&clean=1", true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {
            if (http.readyState === 4) {

                let res = http.response.toString().trim().replace(/<[^>]*>?/gm, '');

                if (res.indexOf("saved") !== -1) {

                    let element_tableid_tr = "ctTable_" + tableid + '_' + recordid;
                    let index = findRowIndexById("ctTable_" + tableid, element_tableid_tr);
                    ctCatalogUpdate(tableid, recordid, index);

                    if (hideModelOnSave)
                        ctHidePopUp();
                } else {
                    if (res.indexOf('<div class="alert-message">Nothing to save</div>') !== -1)
                        alert('Nothing to save. Check Edit From layout.');
                    else if (res.indexOf('view-login') !== -1)
                        alert('Session expired. Please login again.');
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
        //alert('Validation rule "' + valuerules + '" has an error: ' + error);
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
            let isEmailValid = (lastAtPos < lastDotPos && lastAtPos > 0 && value.indexOf('@@') == -1 && lastDotPos > 2 && (value.length - lastDotPos) > 2);
            if (!isEmailValid) {
                alert('The ' + label + ' "' + value + '" is not a valid Email.');
                return false;
            }
        } else if (filter === 'url') {
            if (!isValidURL(value)) {
                alert('The ' + label + ' "' + value + '" is not a valid URL.');
                return false;
            }
        } else if (filter === 'https') {
            if (value.indexOf("https") !== 0) {
                alert('The ' + label + ' "' + value + '" must be secure - must start with "https://".');
                return false;
            }
        } else if (filter === 'domain' && filter_parts.length > 1) {
            let domains = filter_parts[1].split(",");
            let hostname = "";

            try {
                hostname = (new URL(value)).hostname;
            } catch (err) {
                alert('The ' + label + ' "' + value + '" is not a valid URL link.');
                return false;
            }

            let found = false;
            for (let f = 0; f < domains.length; f++) {

                if (domains[f] === hostname) {
                    found = true;
                    break;
                }
            }

            if (!found) {
                alert('The ' + label + ' domain "' + hostname + '" must match to "' + filter_parts[1] + '".');
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
        if (sanitizers[i] == 'trim')
            value = value.trim();
    }

    obj.value = value;
}

function checkRequiredFields() {
    if (!checkFilters())
        return false;

    let requiredFields = document.getElementsByClassName("required");
    let label = "One field";

    for (let i = 0; i < requiredFields.length; i++) {
        if (typeof requiredFields[i].id != "undefined") {
            if (requiredFields[i].id.indexOf("sqljoin_table_comes_") != -1) {
                if (!CheckSQLJoinRadioSelections(requiredFields[i].id))
                    return false;
            }
            if (requiredFields[i].id.indexOf("ct_uploadfile_box_") != -1) {
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

                    alert(label + " required.");
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

                if (requiredFields[i].type === "text") {
                    let obj = document.getElementById(objName);
                    if (obj.value === '') {
                        alert(label + " required.");
                        return false;
                    }
                } else if (requiredFields[i].type === "select-one") {
                    let obj = document.getElementById(objName);
                    //let v = obj.value;

                    if (obj.value === null || obj.value === '') {
                        alert(label + " not selected.");
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
                        alert(label + " not selected.");
                        return false;
                    }
                }
            }
        }
    }

    return true;
}

function SetUsetInvalidClass(id, isValid) {
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
        SetUsetInvalidClass(id, false);
        return false;
    }

    SetUsetInvalidClass(id, true);
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
        alert(label + " not selected.");
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

function ctRenderTableJoinSelectBox(control_name, r, index, execute_all, sub_index, parent_object_id, formId) {

    let wrapper = document.getElementById(control_name + "Wrapper");
    let filters = [];
    if (wrapper.dataset.valuefilters != '')
        filters = JSON.parse(atob(wrapper.dataset.valuefilters));

    //let attributes = atob(wrapper.dataset.attributes);
    let onchange = atob(wrapper.dataset.onchange);

    let next_index = index;
    let next_sub_index = sub_index;
    let val;

    if (Array.isArray(filters[index])) {
        //Self Parent field
        next_sub_index += 1;
        if (next_sub_index == filters[index].length) {
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
            //alert("next_index:" + next_index);
            next_index += 1;

            if (next_index + 1 < filters.length) {
                document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = '<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';
                ctUpdateTableJoinLink(control_name, next_index, false, next_sub_index, parent_object_id, formId, false);//
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
            document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = "No items to select";
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

        let updateValueString = (index + 1 == filters.length ? 'true' : 'false');

        let onChangeFunction = 'ctUpdateTableJoinLink(\'' + control_name + '\', ' + next_index + ', false, ' + next_sub_index + ',\'' + current_object_id + '\', \'' + formId + '\', ' + updateValueString + ');'
        let onChangeAttribute = ' onChange="' + onChangeFunction + onchange + '"';
        //[' + index + ',' + filters.length + ']
        result += '<select id="' + current_object_id + '"' + onChangeAttribute + ' class="' + cssClass + '">';
        result += '<option value="">- ' + Joomla.JText._('COM_CUSTOMTABLES_SELECT') + '</option>';

        for (let i = 0; i < r.length; i++) {
            let optionLabel = decodeHtml(r[i].label);
            result += '<option value="' + r[i].id + '">' + optionLabel + '</option>';
        }

        result += '</select>';

        //Prepare the space for next elements
        result += '<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';
    }

    //Add content to the element
    document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = result;

    if (r.length > 0) {
        if (execute_all && next_index + 1 < filters.length && val != null) {
            ctUpdateTableJoinLink(control_name, next_index, true, next_sub_index, null, formId, false);//
        }
    }
}

function ctUpdateTableJoinLink(control_name, index, execute_all, sub_index, object_id, formId, updateValue) {

    //alert("index:" + index);
    let wrapper = document.getElementById(control_name + "Wrapper");
    //let onchange = atob(wrapper.dataset.onchange);

    let link = location.href.split('administrator/index.php?option=com_customtables');
    let url;

    if (link.length === 2)//to make sure that it will work in the back-end
        url = 'index.php?option=com_customtables&view=records&from=json&key=' + wrapper.dataset.key + '&index=' + index;
    else
        url = 'index.php?option=com_customtables&view=catalog&tmpl=component&from=json&key=' + wrapper.dataset.key + '&index=' + index;

    let filters = [];
    if (wrapper.dataset.valuefilters !== '')
        filters = JSON.parse(atob(wrapper.dataset.valuefilters));

    if (execute_all) {
        if (Array.isArray(filters[index])) {
            //Self Parent field
            if (filters[index][sub_index] !== '')
                url += '&subfilter=' + filters[index][sub_index];
        } else if (filters[index] !== '')
            url += '&filter=' + filters[index];
    } else {
        let obj = document.getElementById(object_id);

        if (updateValue) {
            let valueObj = document.getElementById(control_name);

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
        }

        if (Array.isArray(filters[index]))
            url += '&subfilter=' + obj.value;
        else
            url += '&filter=' + obj.value;
    }

    if (index >= filters.length)
        return false;

    fetch(url)
        .then(r => r.json())
        .then(r => {
            ctRenderTableJoinSelectBox(control_name, r, index, execute_all, sub_index, object_id, formId);//, attributes);
        })
        .catch(error => console.error("Error", error));
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
    let val = document.getElementById(inputbox_id).value;
    let val_list = val.split(",");

    let def_latval = (val_list[0] !== '' ? parseFloat(val_list[0]) : -8);
    let def_longval = (val_list.length > 1 && val_list[1] !== '' ? parseFloat(val_list[1]) : -79);

    let def_zoomval = (val_list.length > 2 && val_list[2] !== '' ? parseFloat(val_list[2]) : 10);
    if (def_zoomval === 0)
        def_zoomval = 10;

    let curpoint = new google.maps.LatLng(def_latval, def_longval);

    let map_obj = document.getElementById(inputbox_id + "_map");

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

    /*
    document.getElementById(inputbox_id + '_save').addEventListener("click", function (event) {
        if (ct_signaturePad[inputbox_id].isEmpty()) {
            "Please provide a signature first.";
        } else {
            //let dataURL = ct_signaturePad[inputbox_id].toDataURL('image/'+format+'+xml');
            let dataURL = ct_signaturePad[inputbox_id].toDataURL('image/'+format);
            document.getElementById(inputbox_id).setAttribute("value", dataURL);
        }
    });
    */
}

function ctInputbox_signature_apply() {
    for (let i = 0; i < ct_signaturePad_fields.length; i++) {

        let inputbox_id = ct_signaturePad_fields[i];

        if (ct_signaturePad[inputbox_id].isEmpty()) {
            alert("Please provide a signature first.");
        } else {

            let format = ct_signaturePad_formats[inputbox_id];

            //let dataURL = ct_signaturePad[inputbox_id].toDataURL('image/'+format+'+xml');
            let dataURL = ct_signaturePad[inputbox_id].toDataURL('image/' + format);
            document.getElementById(inputbox_id).setAttribute("value", dataURL);
        }
    }
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