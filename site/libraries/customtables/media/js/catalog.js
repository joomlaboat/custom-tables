let es_LinkLoading = false;

function ctCreateUser(msg, listing_id, toolbarboxid, ModuleId) {
    if (confirm(msg)) {
        document.getElementById(toolbarboxid).innerHTML = '';

        let returnto = btoa(window.location.href);

        //ctWebsiteRoot is the global variable same like ctItemId
        let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;

        if (ModuleId !== 0)
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids', 'option', 'view'], ['task=createuser', 'option=com_customtables', 'view=catalog', 'listing_id=' + listing_id, 'returnto=' + returnto, 'ModuleId=' + ModuleId], link);
        else
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], ['task=createuser', 'listing_id=' + listing_id, 'returnto=' + returnto], link);

        window.location.href = link;
    }
}

function ctResetPassword(msg, listing_id, toolbarboxid, ModuleId) {
    if (confirm(msg)) {
        document.getElementById(toolbarboxid).innerHTML = '';
        let returnto = btoa(window.location.href);

        let link = '';

        if (ModuleId !== 0)
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids', 'option', 'view'], ['task=resetpassword', 'option=com_customtables', 'view=catalog', 'listing_id=' + listing_id, 'returnto=' + returnto, 'ModuleId=' + ModuleId], '');
        else
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], ['task=resetpassword', 'listing_id=' + listing_id, 'returnto=' + returnto], '');

        window.location.href = link;

    }
}

function esPrepareLink(deleteParams, addParams, custom_link) {

    let link = custom_link !== '' ? custom_link : window.location.href;

    const pair = link.split('#');
    link = pair[0];

    for (let i = 0; i < deleteParams.length; i++)
        link = removeURLParameter(link, deleteParams[i]);

    for (let a = 0; a < addParams.length; a++) {

        if (link.indexOf("?") === -1)
            link += "?"; else link += "&";

        link += addParams[a];
    }

    return link;
}

function esEditObject(objid, toolbarboxid, Itemid, tmpl, returnto) {
    if (es_LinkLoading)
        return;

    es_LinkLoading = true;
    document.getElementById(toolbarboxid).innerHTML = '';

    let return_to = btoa(window.location.href);
    let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=edititem&listing_id=' + objid + '&Itemid=' + Itemid + '&returnto=' + return_to;

    if (tmpl !== '')
        link += '&tmpl=' + tmpl;

    link += '&returnto=' + returnto;

    window.location.href = link;
}

function runTheTask(task, tableid, recordid, url, responses, last) {

    let params = "";
    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {

        http.open("GET", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {
            if (http.readyState === 4) {
                let res = http.response.replace(/(\r\n|\n|\r)/gm, "");

                if (responses.indexOf(res) !== -1) {

                    let element_tableid_tr = "ctTable_" + tableid + '_' + recordid;
                    let index = findRowIndexById("ctTable_" + tableid, element_tableid_tr);

                    if (task === 'delete')
                        document.getElementById("ctTable_" + tableid).deleteRow(index);
                    else
                        ctCatalogUpdate(tableid, recordid, index);

                    es_LinkLoading = false;

                    if (last) {
                        let toolbarboxid = 'esToolBar_' + task + '_box_' + tableid;
                        document.getElementById(toolbarboxid).style.visibility = 'visible';
                    }

                } else
                    alert(res);
            }
        }
        http.send(params);
    }
}

function ctRefreshRecord(tableid, recordid, toolbarboxid, ModuleId) {
    if (es_LinkLoading)
        return;

    es_LinkLoading = true;

    document.getElementById(toolbarboxid).innerHTML = '';

    let element_tableid_tr = "ctTable_" + tableid + '_' + recordid;

    let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;

    let tr_object = document.getElementById(element_tableid_tr);
    if (tr_object) {
        let url = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], ['task=refresh', 'listing_id=' + recordid, 'clean=1', 'tmpl=component'], link);
        runTheTask('refresh', tableid, recordid, url, ['refreshed'], false);
    } else {
        let returnto = btoa(window.location.href);

        if (ModuleId !== 0)
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids', 'option', 'view'], ['task=refresh', 'option=com_customtables', 'view=catalog', 'listing_id=' + recordid, 'returnto=' + returnto, 'ModuleId=' + ModuleId], link);
        else
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], ['task=refresh', 'listing_id=' + recordid, 'returnto=' + returnto], link);

        window.location.href = link;
    }


}

function ctOrderChanged(objectValue) {
    const current_url = esPrepareLink(['returnto', 'task', 'orderby'], [], '');
    let returnto = btoa(current_url);

    let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;

    link = esPrepareLink(['task'], ['task=setorderby', 'orderby=' + objectValue, 'returnto=' + returnto], link);
    window.location.href = link;
}

function ctLimitChanged(object) {
    const current_url = esPrepareLink(['returnto', 'task', 'limit'], [], '');
    let returnto = btoa(current_url);

    let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;
    link = esPrepareLink(['task'], ['task=setlimit', 'limit=' + object.value, 'returnto=' + returnto], link);
    window.location.href = link;
}

function ctPublishRecord(tableid, recordid, toolbarboxid, publish, ModuleId) {
    if (es_LinkLoading)
        return;

    es_LinkLoading = true;

    document.getElementById(toolbarboxid).innerHTML = '';

    let task = publish === 1 ? 'task=publish' : 'task=unpublish';

    let element_tableid_tr = "ctTable_" + tableid + '_' + recordid;
    let tr_object = document.getElementById(element_tableid_tr);

    let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;

    if (tr_object) {
        let url = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], [task, 'listing_id=' + recordid, 'clean=1', 'tmpl=component'], link);
        runTheTask((publish === 0 ? 'unpublish' : 'publish'), tableid, recordid, url, ['published', 'unpublished'], false);
    } else {
        let returnto = Base64.encode(window.location.href);

        if (ModuleId !== 0)
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids', 'option', 'view'], [task, 'option=com_customtables', 'view=catalog', 'listing_id=' + recordid, 'returnto=' + returnto, 'ModuleId=' + ModuleId], link);
        else
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], [task, 'listing_id=' + recordid, 'returnto=' + returnto], link);

        window.location.href = link;
    }
}

function findRowIndexById(tableid, rowid) {

    let rows = document.getElementById(tableid).rows;
    for (let i = 0; i < rows.length; i++) {
        if (rows.item(i).id === rowid)
            return i;
    }
    return -1;
}

function ctDeleteRecord(msg, tableid, recordId, toolbarBoxId, ModuleId) {
    if (es_LinkLoading)
        return;

    es_LinkLoading = true;

    if (confirm(msg)) {

        let element_tableid_tr = "ctTable_" + tableid + '_' + recordId;
        let tr_object = document.getElementById(element_tableid_tr);

        let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;
        let returnto = btoa(window.location.href);

        if (ModuleId !== 0)
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids', 'option', 'view', 'ModuleId', 'tableid'], ['tableid=' + tableid, 'task=delete', 'option=com_customtables', 'view=catalog', 'listing_id=' + recordId, 'returnto=' + returnto, 'ModuleId=' + ModuleId], link);
        else
            link = esPrepareLink(['task', "listing_id", 'returnto', 'ids', 'tableid'], ['tableid=' + tableid, 'task=delete', 'listing_id=' + recordId, 'returnto=' + returnto], link);


        if (tr_object) {

            runTheTask('delete', tableid, recordId, url, ['deleted'], false);
        } else {
            window.location.href = link;
        }
    } else
        es_LinkLoading = false;
}

function es_SearchBoxKeyPress(e) {
    if (e.keyCode === 13)//enter key pressed
        ctSearchBoxDo();
}

function ctSearchBoxDo() {
    if (es_LinkLoading)
        return;

    es_LinkLoading = true;
    let w = [];
    let allSearchElements = document.querySelectorAll('[ctSearchBoxField]');

    for (let i = 0; i < allSearchElements.length; i++) {
        let n = allSearchElements[i].getAttribute('ctSearchBoxField').split(":");
        let elementId = n[0];
        let obj = document.getElementById(elementId);

        if (obj) {
            let o = obj.value;

            if (o !== "" && (o !== "0" || obj.dataset.type === 'int' || obj.dataset.type === 'float' || obj.dataset.type === 'checkbox')) {
                if (n[2] === "") {
                    if (o.indexOf("-to-") !== -1) {
                        if (o !== "-to-")
                            w.push(n[1] + "_r_=" + o);
                    } else
                        w.push(n[1] + "=" + o);
                } else
                    w.push(n[1] + "=" + n[2] + "." + o);//Custom Tables Structure
            }
        } else {
            alert('Element "' + elementId + '" not found.');
        }
    }

    let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;
    link = esPrepareLink(['where', 'task', "listing_id", 'returnto'], ["where=" + Base64.encode(w.join(" and "))], link);
    window.location.href = link;
}

function esCheckboxAllclicked(tableid) {
    const checkboxobj = document.getElementById("esCheckboxAll" + tableid);
    const elements = document.getElementsByName("esCheckbox" + tableid);

    const ids = [];
    for (let i = 0; i < elements.length; i++) {
        const d = parseInt(elements[i].value);

        if (ids.indexOf(d) === -1) {
            ids.push(d);
            const obj = document.getElementById(elements[i].id);
            obj.checked = checkboxobj.checked;
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

            if (selectedIds.indexOf(d) === -1)
                selectedIds.push(d);
        }
    }
    return selectedIds;
}

function ctToolBarDO(task, tableid) {
    if (es_LinkLoading)
        return;

    es_LinkLoading = true;
    const elements = getListOfSelectedRecords(tableid);

    if (elements.length === 0) {
        alert("Please select records first.");
        es_LinkLoading = false;
        return;
    }

    if (task === 'delete') {
        if (!confirm('Do you want to delete ' + elements.length + ' records?')) {
            es_LinkLoading = false;
            return;
        }
    }

    const toolbarboxid = 'esToolBar_' + task + '_box_' + tableid;
    document.getElementById(toolbarboxid).style.visibility = 'hidden';

    let element_tableid = "ctTable_" + tableid;
    let tr_object = document.getElementById(element_tableid);

    let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;

    if (tr_object) {

        for (let i = 0; i < elements.length; i++) {
            let listing_id = elements[i];
            let url = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], ['task=' + task, 'listing_id=' + listing_id, 'clean=1', 'tmpl=component'], link);
            let accept_responses = [];
            if (task === 'refresh')
                accept_responses = ['refreshed'];
            else if (task === 'publish' || task === 'unpublish')
                accept_responses = ['published', 'unpublished'];
            else if (task === 'delete')
                accept_responses = ['published', 'deleted'];

            let last = i === elements.length - 1;
            runTheTask(task, tableid, listing_id, url, accept_responses, last);
        }


    } else {
        let returnto = btoa(window.location.href);
        link = esPrepareLink(['task', "listing_id", 'returnto', 'ids'], ['task=' + task, 'ids=' + elements.toString(), 'returnto=' + returnto], link);
        window.location.href = link;
    }

}

//https://stackoverflow.com/a/1634841
function removeURLParameter(url, parameter) {
    //prefer to use l.search if you have a location/link object
    const urlparts = url.split('?');
    if (urlparts.length >= 2) {
        const prefix = encodeURIComponent(parameter) + '=';
        const pars = urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (let i = pars.length; i-- > 0;) {
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                pars.splice(i, 1);
            }
        }

        url = urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
        return url;
    } else {
        return url;
    }
}

function ct_UpdateSingleValue(WebsiteRoot, Itemid, fieldname_, record_id, postfix, ModuleId) {
    const fieldname = fieldname_.split('_')[0];
    const url = ctWebsiteRoot + 'index.php?option=com_customtables&amp;view=edititem&amp;Itemid=' + Itemid;
    let params = "";
    const obj_checkbox_off = document.getElementById("comes_" + record_id + "_" + fieldname_ + "_off");
    if (obj_checkbox_off) {
        //A bit confusing. But this is needed to save Unchecked values
        //It's because unchecked checkbox has value NULL
        params = "comes_" + fieldname_ + "_off=" + obj_checkbox_off.value; // if this set 1 then the checkbox value will be 0

        if (parseInt(obj_checkbox_off.value) === 1)
            params += "&comes_" + fieldname_ + "=0";
        else
            params += "&comes_" + fieldname_ + "=1";
    } else {
        let objectName = "comes_" + record_id + "_" + fieldname_;
        params += "&comes_" + fieldname_ + "=" + document.getElementById(objectName).value;
    }

    params += "&task=save";
    params += "&Itemid=" + Itemid;
    if (ModuleId !== 0)
        params += "&ModuleId=" + ModuleId;

    params += "&listing_id=" + record_id;

    const obj = document.getElementById("com_" + record_id + "_" + fieldname + postfix + "_div");
    if (obj)
        obj.className = "ct_loader";

    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("POST", url + "&clean=1", true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {
            if (http.readyState === 4) {
                let res = http.response;
                res = res.replace(/<[^>]*>?/gm, '').trim();

                if (res.indexOf("saved") !== -1) {
                    obj.className = "ct_checkmark ct_checkmark_hidden";//+css_class;
                } else {
                    obj.className = "ct_checkmark_err ";

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

function ctCatalogUpdate(tableid, recordsId, row_index) {

    let element_tableid = "ctTable_" + tableid;

    let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;

    let url = esPrepareLink(['task', "listing_id", 'returnto', 'ids', 'clean', 'component', 'frmt'], ['listing_id=' + recordsId, 'number=' + row_index], link);

    let params = "";
    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("GET", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {

            if (http.readyState === 4) {
                let res = http.response;

                let rows = document.getElementById(element_tableid).rows;
                rows[row_index].innerHTML = res;
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
        if (obj == null)
            return null;
    }
}

function ctCatalogOnDrop(event) {
    event.preventDefault();
    const importFromId = event
        .dataTransfer
        .getData('text');

    let to_parts = getContainerElementIDTable(event.target);
    if (to_parts == null)
        return false;

    let to_id = to_parts.join("_");

    if (importFromId === to_id)
        return false;

    if (confirm("Do you want to copy field content to target record?") === true) {

        let from_parts = importFromId.split('_');

        let from = from_parts[2] + '_' + from_parts[3];
        let to = to_parts[2] + '_' + to_parts[3];

        let element_tableid_tr = "ctTable_" + to_parts[1] + '_' + to_parts[2];
        let index = findRowIndexById("ctTable_" + to_parts[1], element_tableid_tr);

        let link = ctWebsiteRoot + 'index.php?option=com_customtables&view=catalog&Itemid=' + ctItemId;

        let url = esPrepareLink(['task', "listing_id", 'returnto', 'ids', 'clean', 'component', 'frmt'], ['task=copycontent', 'from=' + from, 'to=' + to, 'clean=1', 'tmpl=component', 'frmt=json'], link);

        fetch(url)
            .then(r => r.json())
            .then(r => {
                if (r.error) {
                    alert(r.error);
                    return false;
                } else
                    ctCatalogUpdate(to_parts[1], to_parts[2], index);

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

function ctEditModal(url) {

    let new_url = url + '&modal=1&time=' + Date.now();

    let params = "";
    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("GET", new_url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {
            if (http.readyState === 4) {
                let res = http.response;

                //let content_html = '<div style="overflow-y: scroll;overflow-x: hidden;height: 100%;width:100%;">' + res + '</div>';
                ctShowPopUp(res, true);
            }
        }
        http.send(params);
    }
}
