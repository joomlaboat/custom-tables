/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/js/layouteditor.js
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2024. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

//const parts = location.href.split("/administrator/");
//const websiteroot = parts[0] + "/administrator/";
//const websiteSiteLibraries = parts[0] + "/components/com_customtables/libraries/customtables/";

let field_types = [];
let field_type_loaded = false;
let type_obj = null;
let typeparams_id;
let typeparams_obj;
let typeparams_box_id;
let typeparams_box_obj;
let temp_imagesizeparams = [];
let temp_imagesizelist_string = '';
let temp_imagesize_box_id = null;
let temp_imagesize_parambox_id = null;
let temp_imagesize_updateparent = null;
let all_tables = [];
let proversion = false;
let SQLJoinTableID = null;

let CustomTablesCSSClasses = [];
let CustomTablesCMS = null;

document.addEventListener('DOMContentLoaded', function () {

    if (window.Joomla instanceof Object) {
        CustomTablesCMS = 'Joomla';
        CustomTablesCSSClasses.push({'btn': 'btn'})
        CustomTablesCSSClasses.push({'btn-small': 'btn-small'})
        CustomTablesCSSClasses.push({'btn-success': 'btn-success'})
        CustomTablesCSSClasses.push({'icon-delete': 'icon-delete'})
        CustomTablesCSSClasses.push({'form-select': 'form-select'})
        CustomTablesCSSClasses.push({'form-control': 'form-control'})
    } else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
        CustomTablesCMS = 'WordPress';
        CustomTablesCSSClasses.push({'btn': 'button'})
        CustomTablesCSSClasses.push({'btn-small': null})
        CustomTablesCSSClasses.push({'btn-success': 'button-primary'})
        CustomTablesCSSClasses.push({'icon-delete': 'dashicons dashicons-dismiss'})
        CustomTablesCSSClasses.push({'form-select': null})
    }
});

function getCSSClassValueByKey(key) {
    const foundObject = CustomTablesCSSClasses.find(obj => Object.keys(obj).includes(key));
    return foundObject ? Object.values(foundObject)[0] : null;
}

function convertClassString(classString) {
    const classes = classString.split(' ');
    const newClasses = [];

    for (const cls of classes) {
        const value = getCSSClassValueByKey(cls);
        if (value !== null) {
            newClasses.push(value);
        }
    }

    return newClasses.join(' ');
}

function typeChanged() {
    if (typeparams_obj != null)
        typeparams_obj.value = "";

    updateParameters();
}

function renderInput_Article(id, param, value, onchange) {
    const options = getParamOptions(param, 'option');

    let selectOptions = [];


    for (let o = 0; o < options.length; o++) {
        const opt = options[o]["@attributes"];

        if (window.Joomla instanceof Object || (typeof (opt.wordpress) !== "undefined" && opt.wordpress === "true"))
            selectOptions.push([opt.value, opt.label]);
    }

    if (window.Joomla instanceof Object) {
        for (let i = 0; i < custom_fields.length; i++) {
            if (custom_fields[i][0] === 'com_content.article')
                selectOptions.push([custom_fields[i][2], custom_fields[i][1]]);
        }
    }

    // Sort the array by the first column
    selectOptions.sort(function (a, b) {
        return a[1].localeCompare(b[1]); // Sort by first column, assumed to be strings
    });

    let result = '<select id="' + id + '" class="' + convertClassString('form-select') + '" data-type="list" ' + onchange + '>';

    for (let i = 0; i < selectOptions.length; i++) {
        if (selectOptions[i][0] === value)
            result += '<option value="' + selectOptions[i][0] + '" selected="selected">' + selectOptions[i][1] + '</option>';
        else
            result += '<option value="' + selectOptions[i][0] + '" >' + selectOptions[i][1] + '</option>'
    }

    result += '</select>';

    return result;
}

function renderInputBox(id, param, vlu, attributes, fieldTypeParametersList) {
    const param_att = param["@attributes"];
    let result = '';
    if (param_att.type != null) {
        if (param_att.type === "number") {
            if (vlu === '') {
                if (typeof (param_att.default) != "undefined")
                    vlu = param_att.default;
            }

            let extra = "";
            if (param.min !== null)
                extra += ' min="' + param_att.min + '"';

            if (param_att.max !== null)
                extra += ' max="' + param_att.max + '"';

            if (param_att.min !== null)
                extra += ' step="' + param_att.step + '"';

            return '<input data-type="' + param_att.type + '" class="' + convertClassString('form-control') + '" type="number" id="' + id + '" value="' + vlu + '" ' + extra + ' ' + attributes + '>';
        } else if (param_att.type === "list") {

            if (vlu === '') {
                if (typeof (param_att.default) !== "undefined")
                    vlu = param_att.default;
            }

            return renderInput_List(id, param, vlu, attributes);

        } else if (param_att.type === "user") {

            if (vlu === '') {
                if (typeof (param_att.default) !== "undefined")
                    vlu = param_att.default;
            }

            return renderInput_User(id, param, vlu, attributes);

        } else if (param_att.type === "article") {

            if (vlu === '') {
                if (typeof (param_att.default) !== "undefined")
                    vlu = param_att.default;
            }

            return renderInput_Article(id, param, vlu, attributes);

        } else if (param_att.type === "language") {
            return renderInput_Language(id, param, vlu, attributes);
        } else if (param_att.type === "table") {

            let fieldChild = null;
            if (typeof (param_att.fieldchild) != "undefined" && param_att.fieldchild !== "")
                fieldChild = param_att.fieldchild;

            result = renderInput_Table(id, param, vlu, attributes, fieldChild);

            for (let o = 0; o < all_tables.length; o++) {
                let option = all_tables[o];
                if (option[1] === vlu) {
                    SQLJoinTableID = option[0];
                    break;
                }
            }
            return result;

        } else if (param_att.type === "field") {

            if (vlu.indexOf(':') !== -1) {
                //if the default is a layout name then show input box but not select box
                return '<input data-type="field" type="text" class="' + convertClassString('form-control') + '" id="' + id + '" value="' + vlu + '" ' + attributes + '>';
            } else {
                if (SQLJoinTableID === null || (typeof (param_att.currenttable) != "undefined" && param_att.currenttable === "1")) {

                    //Get Current Table ID
                    let currentTableId;
                    let obj;

                    if (typeof Joomla !== 'undefined') {
                        obj = document.getElementById('jform_tableid');
                    } else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
                        obj = document.getElementById('table');
                    }

                    if (!obj)
                        return 'Table selector not found.';

                    currentTableId = obj.value;

                    return renderInput_Field(id, param, vlu, attributes, currentTableId);
                } else
                    return renderInput_Field(id, param, vlu, attributes, SQLJoinTableID);
            }
        } else if (param_att.type === "layout") {
            return renderInput_Layout(id, param, vlu, attributes);
        } else if (param_att.type === "multiselect") {
            return renderInput_Multiselect(id, param, vlu, attributes);
        } else if (param_att.type === "imagesizelist") {

            vlu = vlu.replaceAll('****quote****', '');
            vlu = vlu.replaceAll('****apos****', "");

            return renderInput_ImageSizeList(id, param, vlu, attributes);
        } else if (param_att.type === "imagesizeselector") {

            vlu = vlu.replaceAll('****quote****', '');
            vlu = vlu.replaceAll('****apos****', "");

            return renderInput_ImageSizeSelector(id, param, vlu, attributes, fieldTypeParametersList);
        } else if (param_att.type === "folder") {

            vlu = vlu.replaceAll('****quote****', '');
            vlu = vlu.replaceAll('****apos****', "");

            return renderInput_Folder(id, vlu, attributes);
        } else if (param_att.type === "radio") {
            return renderInput_Radio(id, param, vlu, attributes);
        } else if (param_att.type === "array") {
            if (vlu === '')
                vlu = '[]';
        } else if (param_att.type === "fieldlayout") {
            if (vlu === '') {
                if (typeof (param_att.default) != "undefined")
                    vlu = param_att.default;
            }

            vlu = vlu.replaceAll('****quote****', '&quot;');
            vlu = vlu.replaceAll('****apos****', "&apos;");
        } else {
            if (vlu === '') {
                if (typeof (param_att.default) != "undefined")
                    vlu = param_att.default;
            }

            vlu = vlu.replaceAll('****quote****', '&quot;');
            vlu = vlu.replaceAll('****apos****', "&apos;");
        }
    }
    return '<input data-type="' + param_att.type + '" class="' + convertClassString('form-control') + '" type="text" id="' + id + '" value="' + vlu + '" ' + attributes + '>';
}

function updateTypeParams(type_id, typeparams_id_, typeparams_box_id_) {
    type_obj = document.getElementById(type_id);
    typeparams_id = typeparams_id_;
    typeparams_obj = document.getElementById(typeparams_id);
    typeparams_box_id = typeparams_box_id_;
    typeparams_box_obj = document.getElementById(typeparams_box_id_);

    if (!field_type_loaded) {
        loadTypes(typeparams_box_obj, type_id, typeparams_id, typeparams_box_id_);
    } else {
        updateParameters();
    }
}

function getParamOptions(param, optionObjectName) {
    let options = [];

    if (typeof (param) !== "undefined" && typeof (param[optionObjectName]) !== "undefined") {
        if (param[optionObjectName].constructor.name !== "Array")
            options.push(param[optionObjectName]);
        else
            options = param[optionObjectName];
    }
    return options;
}

function renderParamList(typeparams, typeparams_box, paramValueString, fieldTypeParametersList) {
    let result = '';
    const att = typeparams["@attributes"];
    const param_array = getParamOptions(typeparams.params, 'param');
    let vlu = '';
    const values = parseQuote(paramValueString, ',', true);

    if (typeof (att.repetitive) !== "undefined" && att.repetitive === "1" && param_array.length === 1) {

        for (let i = 0; i < values.length; i++) {
            vlu = '';
            if (values.length > i)
                vlu = values[i];

            result += inputBoxPreRender(proversion, param_array[0], -1, i, vlu, att, typeparams_box, fieldTypeParametersList);
        }
        result += inputBoxPreRender(proversion, param_array[0], -1, values.length, '', att, typeparams_box, fieldTypeParametersList);

    } else {
        for (let i2 = 0; i2 < param_array.length; i2++) {
            vlu = '';
            if (values.length > i2)
                vlu = values[i2];

            const param = param_array[i2];

            result += inputBoxPreRender(proversion, param, param_array.length, i2, vlu, att, typeparams_box, fieldTypeParametersList);
        }
    }

    return result;
}

function renderParamBox(typeparams, typeparams_box, paramValueString, fieldTypeParametersList) {

    const att = typeparams["@attributes"];
    let result = '<h4>' + att.label + '</h4><p>' + att.description;

    if (typeof (att.helplink) !== "undefined")
        result += ' <a href="' + att.helplink + '" target="_blank">Read more</a>';

    result += '</p><div class="form-horizontal typeparams_box_area">';

    const param_array = getParamOptions(typeparams.params, 'param');

    if (param_array.length > 0) {
        result += '<hr/>';

        let tag_pair = [];

        if (typeof (att.name) !== "undefined")
            tag_pair = parseQuote(att.name, [':', '='], false);
        else if (typeof (att.ct_name) !== "undefined")
            tag_pair = parseQuote(att.ct_name, [':', '='], false);

        result += '<div id="modalParamTagName" style="display:none">' + tag_pair[0] + '</div>';
        result += '<div id="modalParamList">' + renderParamList(typeparams, typeparams_box, paramValueString, fieldTypeParametersList) + '</div>';
    }

    if (!proversion && typeof (att.proversion) !== "undefined" && att.proversion === "1") {
        result += '<div class="fieldtype_disable_box"></div></div>';
        result = '<p style="color:red;">This Field Type available in PRO version only.</p>' + result;

    } else {
        result += '</div>';
    }

    return result;
}

function inputBoxPreRender(proVersion, param, param_count, i, vlu, att, typeparams_box, fieldTypeParametersList) {
    let repetitive = false;
    if (typeof (att.repetitive) !== "undefined" && att.repetitive === "1")
        repetitive = true;

    let result = '';
    const param_att = param["@attributes"];

    if (proVersion || typeof (param_att.proversion) === "undefined" || param_att.proversion === "0") {

        result += '<div class="control-group"><div class="control-label">';

        let label = "";
        if (typeof (param_att) !== "undefined" && typeof (param_att.label) !== "undefined") {
            if (param_count === -1)
                label = param_att.label + " (" + (i + 1) + ")";
            else
                label = param_att.label;
        }

        let description = "";
        if (typeof (param_att) !== "undefined" && typeof (param_att.description) !== "undefined")
            description = param_att.description;

        result += '<label id="fieldtype_param_' + i + '-lbl" for="fieldtype_param_' + i + '" class="hasPopover" data-content="' + description + '"';
        result += ' data-original-title="' + label + '"';
        result += ' title="' + description + '"';
        result += ' >';
        result += label;

        result += '</label>';
        result += '</div><div class="controls">';

        let rawQuotes = "false";
        if (typeof (att.rawquotes) != "undefined" && att.rawquotes === "1")
            rawQuotes = "true";

        let startChar = '';

        if (typeof (att.startchar) !== "undefined")
            startChar = att.startchar;

        let refresh = 'false';
        if (repetitive)
            refresh = 'true';

        const attributes = 'onchange="updateParamString(\'fieldtype_param_\',1,' + param_count + ',\'' + typeparams_box + '\',event,' + rawQuotes + ',' + refresh + ',\'' + startChar + '\');"';

        result += renderInputBox('fieldtype_param_' + i, param, vlu, attributes, fieldTypeParametersList);
        result += '</div></div>';
    }
    return result;
}

function getInputType(obj) {
    if (obj && typeof (obj) == "object" && obj.childNodes && typeof (obj.childNodes) !== "undefined") {
        for (let i = 0; i < obj.childNodes.length; i++) {
            if (obj.childNodes[i].tagName === "INPUT") {
                return obj.childNodes[i].type;
            }
            if (obj.childNodes[i].tagName === "SELECT") {
                return 'select';
            }
        }
    }
    return '';
}

function updateParamString(inputBoxId, countList, countParams, objectId, e, rawQuotes, refresh, startChar = '') {

    let endChar = '';
    if (startChar === '{')
        endChar = '}';

    //objectId is the element id where value will be set
    if (e != null)
        e.preventDefault();

    let count = 0;
    let list = [];

    for (let r = 0; r < countList; r++) {
        let params = [];

        for (let i = 0; i < countParams || countParams === -1; i++) { // -1 "unlimited" number of parameters
            let objectName = inputBoxId;

            if (r > 0)
                objectName += r + 'x';

            objectName += i;
            let obj = document.getElementById(objectName);

            if (obj) {
                let t = getInputType(obj);
                let v = "";
                let inputBoxType = obj.dataset['type'];

                if (t === "radio")
                    v = getRadioValue(objectName);
                else if (t === "multiselect")
                    v = getSelectValues(select).merge(",");
                else {
                    if (inputBoxType === 'array') {
                        if (obj.value === '')
                            v = '[]';
                        else
                            v = obj.value;
                    } else {
                        v = obj.value;
                    }
                }

                if (inputBoxType !== "array" && isNaN(v) && v !== 'true' && v !== 'false') {
                    if (v.indexOf('"') !== -1)
                        v = v.replaceAll('"', '****quote****');

                    //if (v.indexOf("'") !== -1)
                    //v = v.replaceAll("'", '****apos****');

                    v = '"' + v + '"';
                }
                params.push(v);
                if (v !== "" && v != '[]')
                    count = i + 1; //to include all previous parameters even if they are empty
            } else
                break;
        }

        let tmp_params = "";
        let newParams = [];

        if (count > 0) {
            for (let i2 = 0; i2 < count; i2++) {

                let v = params[i2];
                if (v === '')
                    v = '"' + v + '"';

                newParams.push(v);
            }
            tmp_params = newParams.join(",");
        }
        list.push(tmp_params);
    }

    let tmp_list = list.join(";");
    let typeparams_obj = document.getElementById(objectId);

    if (typeparams_obj) {
        typeparams_obj.value = tmp_list;  // why is it here?

        let paramValueStringTemp = tmp_list.replaceAll('<', '&lt;');
        paramValueStringTemp = paramValueStringTemp.replaceAll('>', '&gt;');

        typeparams_obj.innerHTML = paramValueStringTemp;
    }

    if (refresh) {

        const modalParamTagNameObject = document.getElementById("modalParamTagName");
        if (modalParamTagNameObject) {
            let typename = modalParamTagNameObject.innerHTML;

            let typeparams;
            if (startChar === '{')
                typeparams = findTagObjectByName(startChar, endChar, typename);
            else
                typeparams = findTheType(typename);

            if (typeparams != null) {
                let modalParamListObject = document.getElementById("modalParamList");
                if (modalParamListObject) {

                    let fieldTypeParametersList = [];//TODO: do something
                    modalParamListObject.innerHTML = renderParamList(typeparams, objectId, tmp_list, fieldTypeParametersList);
                } else
                    alert("modalParamList not found.");
            }
        }
    }
    return false;
}

function getSelectValues(select) {
    const result = [];
    const options = select && select.options;
    let opt;

    let i = 0, iLen = options.length;
    for (; i < iLen; i++) {
        opt = options[i];

        if (opt.selected) {
            result.push(opt.value || opt.text);
        }
    }
    return result;
}

function getRadioValue(objectName) {
    const radios = document.getElementsByName(objectName);
    let v = "";
    const length = radios.length;

    for (let i = 0; i < length; i++) {
        const id = radios[i].getAttribute('id');
        const label_obj = document.getElementById(id + "_label");
        let label_class = label_obj.getAttribute('class');
        label_class = "btn";//label_class.replace(" active","");

        if (radios[i].checked) {
            v = radios[i].value;
        }
    }
    return v;
}

function renderInput_Multiselect(id, param, values, onchange) {
    const options = getParamOptions(param, 'option');
    const values_array = values.split(",");
    let result = "";

    result += '<select id="' + id + '" class="' + convertClassString('form-select') + '" data-type="multiselect" ' + onchange + ' multiple="multiple">';

    for (let o = 0; o < options.length; o++) {
        const opt = options[o]["@attributes"];

        if (proversion || typeof (opt.proversion) === "undefined" || opt.proversion === "0") {
            if (values !== '' && values_array.indexOf(opt.value) !== -1)
                result += '<option value="' + opt.value + '" selected="selected">' + opt.label + '</option>';
            else
                result += '<option value="' + opt.value + '" >' + opt.label + '</option>';
        }
    }
    return result + '</select>';
}

function renderInput_ImageSizeList(id, param, value, attributes) {

    let result = "";
    temp_imagesizeparams = getParamOptions(param, 'sizeparam');
    temp_imagesizelist_string = value;
    temp_imagesize_parambox_id = id;
    temp_imagesize_box_id = 'temp_imagesize_box';

    result += '<div id="' + temp_imagesize_box_id + '">';
    result += BuildImageSizeTable();
    result += '</div>';

    temp_imagesize_updateparent = attributes.replace('onchange="updateParamString(', '');
    temp_imagesize_updateparent = temp_imagesize_updateparent.replace(');"', '');
    temp_imagesize_updateparent = temp_imagesize_updateparent.replace(/[']/g, "");
    temp_imagesize_updateparent = temp_imagesize_updateparent.split(",");

    result += '<input type="text" id="' + id + '" class="' + convertClassString('form-control') + '" data-type="imagesizelist" value="' + value + '" style="display:none;width:100%;" ' + attributes + '>';// '+onchange+'>';
    return result;
}

function renderInput_ImageSizeSelector(id, param, value, attributes, fieldTypeParametersList) {

    let imageSizes = parseQuote(fieldTypeParametersList[0], ';', true);

    temp_imagesizeparams = getParamOptions(param, 'sizeparam');

    let result = '<select id="' + id + '" class="' + convertClassString('form-select') + '" data-type="imagesizeselector" ' + attributes + '>';

    if ("_thumb" || value === "_thumbnail")
        value = '';

    let pairs = [
        ['link:', '- Link to Thumbnail (100x100)'],
        ['', '- IMG Tag: Thumbnail (100x100)'],
        ['link:_original', '- Link to Original Image'],
        ['_original', '- IMG Tag: Original Image'],
    ];

    for (let i = 0; i < imageSizes.length; i++) {
        let pair = parseQuote(imageSizes[i], ',', true);
        pairs.push(['link:' + pair[0], 'Link to image size: ' + pair[0]]);
        pairs.push([pair[0], 'IMG tag: ' + pair[0]]);
    }

    pairs.push(['_count', '- Count (Number of images)']);

    for (let i = 0; i < pairs.length; i++) {
        if (pairs[i][0] === value)
            result += '<option value="' + pairs[i][0] + '" selected="selected">' + pairs[i][1] + '</option>';
        else
            result += '<option value="' + pairs[i][0] + '">' + pairs[i][1] + '</option>';
    }

    result += '</select>';
    return result;
}

function BuildImageSizeTable() {
    const value = temp_imagesizelist_string;//document.getElementById(temp_imagesize_parambox_id).value;
    const value_array = value.split(";");
    let result = '';
    let i;
    let param = null;
    let param_att = null;
    const blank_value = [];
    let count_list = value_array.length;
    if (value === "")
        count_list = 0;

    if (count_list > 0) {
        result += '<table><thead><tr>';
        for (i = 0; i < temp_imagesizeparams.length; i++) {
            blank_value.push("");

            param = temp_imagesizeparams[i];
            param_att = param["@attributes"];

            result += '<th>';//<div class="control-group"><div class="control-label">';
            result += '<label id="size_param_' + i + '-lbl" class="hasPopover" title="" data-content="' + param_att.description + '"';
            result += ' data-original-title="' + param_att.label + '" >' + param_att.label + '</label>';
            //result+='</div>';
            result += '</th>';
        }
        result += '<th></th>';

        result += '</tr></thead>';
        result += '<tbody>';


        const count_params = temp_imagesizeparams.length;

        for (let r = 0; r < count_list; r++) {
            const values = value_array[r].split(',');

            result += '<tr>';
            for (i = 0; i < count_params; i++) {
                param = temp_imagesizeparams[i];
                param_att = param["@attributes"];
                let vlu = "";

                if (values.length > i)
                    vlu = values[i];

                let id = 'size_param_';

                if (r > 0)
                    id += r + 'x';

                id += i;
                let attributes = 'onchange="updateParamString_ImageSizes(\'size_param_\',' + count_list + ',' + count_params + ',\'' + temp_imagesize_parambox_id + '\',event,false);"';

                if (typeof (param_att.style) != "undefined")
                    attributes += 'style="' + param_att.style + '"';

                let fieldTypeParametersList = [1, 2, 3, 4, 5];//TODO: do something
                result += '<td style="padding-right:5px;">' + renderInputBox(id, param, vlu, attributes, fieldTypeParametersList) + '</td>';

            }
            result += '<td><div class="btn-wrapper" id="toolbar-delete"><button onclick="deleteImageSize(' + r + ');" type="button" class="' + convertClassString('btn btn-small') + '"><span class="' + convertClassString('icon-delete') + '"></span></button></div></td>';
            result += '</tr>';
        }
        result += '</tbody>';
        result += '</table>';
    }

    result += '<button onclick=\'addImageSize("' + blank_value.join(",") + '")\' class="' + convertClassString('btn btn-small btn-success') + '" type="button" style="margin-top:5px;">';
    result += '<span class="icon-new icon-white"></span><span style="margin-left:10px;">Add Image Size</span></button>';//<hr/>

    return result;
}

//Used in onchange event
function updateParamString_ImageSizes(sizeParam, countList, countParams, tempImageSizeParamBoxId, e) {

    updateParamString(sizeParam, countList, countParams, tempImageSizeParamBoxId, e, false);

    const obj = document.getElementById(temp_imagesize_parambox_id);
    temp_imagesizelist_string = obj.value;

    const sizeParam_ = temp_imagesize_updateparent[0];
    const countList_ = temp_imagesize_updateparent[1];
    const countParams_ = temp_imagesize_updateparent[2];
    const tempImageSizeParamBoxId_ = temp_imagesize_updateparent[3];

    updateParamString(sizeParam_, countList_, countParams_, tempImageSizeParamBoxId_, null, false);

}

function deleteImageSize(index) {
    const obj = document.getElementById(temp_imagesize_parambox_id);
    const value = obj.value;
    const value_array = value.split(";");
    value_array.splice(index, 1);

    temp_imagesizelist_string = value_array.join(';');
    obj.value = temp_imagesizelist_string;

    document.getElementById(temp_imagesize_box_id).innerHTML = BuildImageSizeTable();

    const sizeparam_ = temp_imagesize_updateparent[0];
    const countlist_ = temp_imagesize_updateparent[1];
    const countparams_ = temp_imagesize_updateparent[2];
    const tempimagesizeparamboxid_ = temp_imagesize_updateparent[3];

    updateParamString(sizeparam_, countlist_, countparams_, tempimagesizeparamboxid_, null, false);
}

function addImageSize(vlu) {
    const obj = document.getElementById(temp_imagesize_parambox_id);
    const value = obj.value;
    if (value === '')
        temp_imagesizelist_string = vlu + ',';
    else
        temp_imagesizelist_string = value + ';';
    obj.value = temp_imagesizelist_string;

    const obj2 = document.getElementById(temp_imagesize_box_id);
    obj2.innerHTML = BuildImageSizeTable();

    const sizeparam_ = temp_imagesize_updateparent[0];
    const countlist_ = temp_imagesize_updateparent[1];
    const countparams_ = temp_imagesize_updateparent[2];
    const tempimagesizeparamboxid_ = temp_imagesize_updateparent[3];

    updateParamString(sizeparam_, countlist_, countparams_, tempimagesizeparamboxid_, null, false);
}

function renderInput_List(id, param, value, onchange) {
    const options = getParamOptions(param, 'option');
    let result = "";

    result += '<select id="' + id + '" class="' + convertClassString('form-select') + '" data-type="list" ' + onchange + '>';

    for (let o = 0; o < options.length; o++) {
        const opt = options[o]["@attributes"];

        if (window.Joomla instanceof Object || (typeof (opt.wordpress) !== "undefined" && opt.wordpress === "true")) {
            if (opt.value === value)
                result += '<option value="' + opt.value + '" selected="selected">' + opt.label + '</option>';
            else
                result += '<option value="' + opt.value + '" >' + opt.label + '</option>';
        }
    }

    result += '</select>';

    return result;
}

function renderInput_User(id, param, value, onchange) {
    const options = getParamOptions(param, 'option');

    let selectOptions = [];


    for (let o = 0; o < options.length; o++) {
        const opt = options[o]["@attributes"];

        if (window.Joomla instanceof Object || (typeof (opt.wordpress) !== "undefined" && opt.wordpress === "true"))
            selectOptions.push([opt.value, opt.label]);
    }

    if (window.Joomla instanceof Object) {
        for (let i = 0; i < custom_fields.length; i++) {
            if (custom_fields[i][0] === 'com_users.user')
                selectOptions.push([custom_fields[i][2], custom_fields[i][1]]);
        }
    }

    // Sort the array by the first column
    selectOptions.sort(function (a, b) {
        return a[1].localeCompare(b[1]); // Sort by first column, assumed to be strings
    });

    let result = '<select id="' + id + '" class="' + convertClassString('form-select') + '" data-type="list" ' + onchange + '>';

    for (let i = 0; i < selectOptions.length; i++) {
        if (selectOptions[i][0] === value)
            result += '<option value="' + selectOptions[i][0] + '" selected="selected">' + selectOptions[i][1] + '</option>';
        else
            result += '<option value="' + selectOptions[i][0] + '" >' + selectOptions[i][1] + '</option>'
    }

    result += '</select>';

    return result;
}


function renderInput_Language(id, param, value, onchange) {
    let result = '<select id="' + id + '" class="' + convertClassString('form-select') + '" ' + onchange + '>';

    if (value === "")
        result += '<option value="" selected="selected">- Default Language</option>';
    else
        result += '<option value="" >- Default Language</option>';

    for (let o = 0; o < languages.length; o++) {
        if (languages[o][0] === value)
            result += '<option value="' + languages[o][0] + '" selected="selected">' + languages[o][1] + '</option>';
        else
            result += '<option value="' + languages[o][0] + '" >' + languages[o][1] + '</option>';
    }

    return result + '</select>';
}

//Used in onchange event
function updateFieldSelectOptions(tableSelectElementId, fieldChildListStr, selectedIndex) {
    let list = fieldChildListStr.split(",");
    for (let i = 0; i < list.length; i++)
        updateFieldSelectOptionsDo(tableSelectElementId, list[i], selectedIndex);
}

function updateFieldSelectOptionsDo(tableSelectElementId, fieldchild, selectedIndex) {

    let selectObject = document.getElementById(fieldchild);

    while (selectObject.options.length > 0) {
        selectObject.remove(0);
    }

    if (selectedIndex !== 0) {
        let tableSelectElement = document.getElementById(tableSelectElementId);
        let dataset = tableSelectElement.options[selectedIndex].dataset;//.tableid;
        let tableid = dataset['tableid'];

        let fieldsStr = document.getElementById('fieldsData' + tableid).innerHTML
        let fields = JSON.parse(fieldsStr);

        let option = document.createElement("option");
        option.value = '';
        option.text = '- Select Field';
        selectObject.add(option);

        for (let i = 0; i < fields.length; i++) {
            let option = document.createElement("option");
            option.value = fields[i][1];
            option.text = fields[i][1];
            selectObject.add(option);
        }

        option = document.createElement("option");
        option.value = '_id';
        option.text = '- ID';
        selectObject.add(option);

        option = document.createElement("option");
        option.value = '_published';
        option.text = '- Published';
        selectObject.add(option);
    }
}

function renderInput_Table(id, param, value, onchange, fieldchild) {

    let result = "";

    if (fieldchild !== null) {
        onchange = onchange.replace('onchange="', 'onchange="updateFieldSelectOptions(\'' + id + '\',\'' + fieldchild + '\',this.selectedIndex);');
    }

    result += '<select id="' + id + '" class="' + convertClassString('form-select') + '" data-type="table" ' + onchange + '>';

    if (value === "")
        result += '<option value="" selected="selected">- Select Table</option>';
    else
        result += '<option value="" >- Select Table</option>';

    for (let o = 0; o < all_tables.length; o++) {
        const option = all_tables[o];

        if (option[1] === value)
            result += '<option value="' + option[1] + '" data-tableid="' + option[0] + '" selected="selected">' + option[1] + '</option>';
        else
            result += '<option value="' + option[1] + '" data-tableid="' + option[0] + '" >' + option[1] + '</option>';
    }

    result += '</select>';

    return result;
}

function renderInput_Field_do(id, value, onchange, SQLJoinTableID) {

    let result = "";

    result += '<select id="' + id + '" class="' + convertClassString('form-select') + '" data-type="field" ' + onchange + '>';

    if (SQLJoinTableID !== null && SQLJoinTableID !== "") {

        let fieldsStr = document.getElementById('fieldsData' + SQLJoinTableID).innerHTML
        let fields = JSON.parse(fieldsStr);

        if (value === "")
            result += '<option value="" selected="selected">- Select Field</option>';
        else
            result += '<option value="" >- Select Field</option>';

        for (let i = 0; i < fields.length; i++) {

            if (fields[i][1] === value)
                result += '<option value="' + fields[i][1] + '" selected="selected">' + fields[i][1] + '</option>';
            else
                result += '<option value="' + fields[i][1] + '">' + fields[i][1] + '</option>';
        }

        if (value === "_id")
            result += '<option value="_id" selected="selected">- ID</option>';
        else
            result += '<option value="_id" >- ID</option>';

        if (value === "_published")
            result += '<option value="_published" selected="selected">- Published</option>';
        else
            result += '<option value="_published">- Published</option>';
    } else {
        alert("Table not selected.");
    }

    result += '</select>';
    return result;
}

//Used in onchange event
function renderInput_Field_readSelectBoxes(id) {
    let value = '';
    let i = 0;
    let allHaveSelections = true;
    const indexes = [];
    const values = [];
    let onchangefound = false;
    let onchange_function = "";

    while (1) {

        const obj = document.getElementById(id + '_' + i);
        if (!obj)
            break;

        const v = obj.value;

        if (v !== "") {
            if (indexes.indexOf(obj.selectedIndex) === -1) {
                if (value !== '')
                    value += ',';

                value += v;
            }

            if (!onchangefound) {
                const o = String(obj.onchange);
                const oParts = o.split(";");
                if (oParts.length === 3) {
                    onchange_function = oParts[1];
                    onchangefound = true;
                }
            }
        } else
            allHaveSelections = false;

        values.push(v);
        indexes.push(obj.selectedIndex);
        i++;
    }

    document.getElementById(id).value = value;

    if (allHaveSelections) {
        //add new select box

        const selectBoxes = document.getElementById(id + '_selectboxes');

        let tableObjectId = '';
        if (window.Joomla instanceof Object) {
            tableObjectId = 'jform_tableid';
        } else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
            tableObjectId = 'table';
        } else {
            console.log('Error: renderInput_Layout not supported in this type of CMS');
            return;
        }

        let tableObject = document.getElementById(tableObjectId);
        if (!tableObject)
            return;

        let tableId = tableObject.value;

        const o2 = 'onchange=\'renderInput_Field_readSelectBoxes("' + id + '");' + onchange_function + '\';';
        selectBoxes.innerHTML = selectBoxes.innerHTML + renderInput_Field_do(id + "_" + i, '', o2, tableId);

        //update values
        for (let n = 0; n < i; n++) {
            const obj2 = document.getElementById(id + '_' + n);
            if (!obj2)
                break;

            obj2.selectedIndex = indexes[n];
            obj2.value = values[n];
        }
    }
}

function renderInput_Field_readTextBoxes(id) {
    let value = '';
    let i = 0;
    let allHaveSelections = true;
    const indexes = [];
    const values = [];
    let onchangefound = false;
    let onchange_function = "";

    while (1) {
        const obj = document.getElementById(id + '_' + i);
        if (!obj)
            break;

        const v = obj.value;

        if (v !== "") {
            if (indexes.indexOf(obj.selectedIndex) === -1) {
                if (value !== '')
                    value += ',';

                value += v;
            }

            if (!onchangefound) {
                const o = String(obj.onchange);
                const oParts = o.split(";");
                if (oParts.length === 3) {
                    onchange_function = oParts[1];
                    onchangefound = true;
                }
            }
        } else
            allHaveSelections = false;

        values.push(v);
        indexes.push(obj.selectedIndex);
        i++;
    }

    document.getElementById(id).value = value;

    if (allHaveSelections) {
        //add new select box
        const selectBoxes = document.getElementById(id + '_selectboxes');

        const o2 = 'onchange=\'renderInput_Field_readTextBoxes("' + id + '");' + onchange_function + '\';';
        selectBoxes.innerHTML = selectBoxes.innerHTML + '<input type="text" class="' + convertClassString('form-control') + '" id="' + id + "_" + i + '" value="" ' + o2 + '>';

        //update values
        for (let n = 0; n < i; n++) {
            const obj2 = document.getElementById(id + '_' + n);
            if (!obj2)
                break;

            obj2.selectedIndex = indexes[n];
            obj2.value = values[n];
        }
    }
}

function renderInput_Field(id, param, value, onchange, SQLJoinTableID) {

    const param_att = param["@attributes"];
    let repetitive = 0;
    if (typeof (param_att.repetitive) != "undefined" && param_att.repetitive === "1")
        repetitive = 1;

    if (repetitive === 1) {
        let result = "";
        const onchange_parts = onchange.split('"');
        if (onchange_parts.length < 3)
            return 'Something wrong with onchange attribute';

        let onchange_function = onchange_parts[1];//onChange function
        onchange_function = onchange_function.replace(/'/g, '"');

        const o = 'onchange=\'renderInput_Field_readSelectBoxes("' + id + '");' + onchange_function + '\'';
        let i = 0;
        if (value !== '') {
            const fields = value.split(",");

            for (i = 0; i < fields.length; i++)
                result += renderInput_Field_do(id + "_" + i, fields[i], o, SQLJoinTableID);
        }

        result += renderInput_Field_do(id + "_" + i, '', o, SQLJoinTableID);

        return '<input type="hidden" data-type="field" class="' + convertClassString('form-control') + '" id="' + id + '" ' + onchange + ' value=\'' + value + '\' /><div id="' + id + '_selectboxes">' + result + '</div>';
    } else
        return renderInput_Field_do(id, value, onchange, SQLJoinTableID);
}

function renderInput_Layout_checktype(layouttype_str, t) {
    //this function accepts one or more layout types. example: catalogpage,simplecatalog
    //if at least one type matches layout type number return true
    if (layouttype_str === '')
        return true; //any type

    const types = ['', 'simplecatalog', 'editform', 'record', 'details', 'catalogpage', 'catalogitem', 'email', 'xml', 'csv', 'json'];
    const parts = layouttype_str.split(",");

    for (let i = 0; i < parts.length; i++) {
        if (types.indexOf(parts[i]) === t)
            return true;
    }

    return false;
}

function renderInput_Layout(id, param, value, onchange) {
    let param_att = param["@attributes"];
    let currentLayout;
    let currentTable;

    if (window.Joomla instanceof Object) {
        currentLayout = document.getElementById('jform_layoutname').value;
        currentTable = parseInt(document.getElementById('jform_tableid').value);
    } else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
        currentLayout = document.getElementById('layoutname').value;
        currentTable = parseInt(document.getElementById('table').value);
    } else {
        return 'renderInput_Layout not supported in this type of CMS';
    }

    let layout_table = "";
    if (param_att.table != null)
        layout_table = param_att.table;

    let layout_type = "";
    if (param_att.layouttype != null)
        layout_type = param_att.layouttype;

    let selectedLayoutName = '';
    let selectedLayoutID = '';
    onchange = onchange.replace('onchange="', 'onchange="renderInput_LayoutLinkUpdate(\'' + id + '\', this);');
    let result = '<select id="' + id + '" class="' + convertClassString('ct_improved_selectbox form-select') + '" data-type="layout" ' + onchange + '>';

    result += '<option value="" ' + (value === "" ? 'selected="selected"' : '') + '>- Select Layout</option>';

    for (let o = 0; o < wizardLayouts.length; o++) {
        let ok = true;
        let option = wizardLayouts[o];

        if (layout_table === "current") {

            if (parseInt(option.tableid) !== currentTable)
                ok = false;
        }

        if (ok && option.layoutname !== currentLayout) {   //table checked not checking layout type
            if (renderInput_Layout_checktype(layout_type, parseInt(option.layouttype)))
                result += '<option value="' + option.layoutname + '"' + (option.layoutname === value ? 'selected="selected"' : '') + ' data-layoutid="' + option.id + '">' + option.layoutname + '</option>';
        }

        if (option.layoutname === value) {
            selectedLayoutName = option.layoutname;
            selectedLayoutID = option.id;
        }
    }

    result += '</select>';
    result += '<div id="' + id + '_layoutLink" style="margin-left:20px;display: inline-block;">';
    if (selectedLayoutName !== '')
        result += '<a href="index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' + selectedLayoutID + '" target="_blank">' + selectedLayoutName + '</a>';

    result += '</div>';
    return result;
}

function renderInput_LayoutLinkUpdate(id, t) {
    let dataSet = t.options[t.selectedIndex].dataset;
    document.getElementById(id + '_layoutLink').innerHTML = '<a href="index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=' + dataSet['layoutid'] + '" target="_blank">' + t.options[t.selectedIndex].value + '</a>';
}

function renderInput_Folder(id, value, onchange) {

    const folders = document.getElementById("ct_fieldtypeeditor_box").innerHTML.split(',');
    let result = '<select id="' + id + '" class="' + convertClassString('form-select') + '" style="width:90%" ' + onchange + '>';

    result += '<option value="" ' + (value === "" ? ' selected="selected"' : '') + '>- Images Root</option>';
    for (let i = 0; i < folders.length; i++)
        result += '<option value="' + folders[i] + '"' + (value === folders[i] ? ' selected="selected"' : '') + '>' + folders[i] + '</option>';

    return result + '</select>';
}

function findTheType(typename) {

    for (let i = 0; i < field_types.length; i++) {
        const n = field_types[i]["@attributes"].ct_name;

        if (n === typename)
            return field_types[i];//["@attributes"];
    }
    return null;
}

//Used in onchange event
function loadTypes_silent() {

    let url = '';
    if (window.Joomla instanceof Object) {
        let parts = location.href.split("/administrator/");
        url = parts[0] + '/index.php?option=com_customtables&view=xml&xmlfile=fieldtypes&Itemid=-1';
    } else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
        let parts = location.href.split("wp-admin/admin.php?");
        url = parts[0] + 'wp-admin/admin.php?page=customtables-api-xml&xmlfile=fieldtypes';
    } else {
        alert('loadTags: CMS Not Supported.');
        return;
    }

    const params = "";
    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("GET", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {

            if (http.readyState === 4) {
                const res = http.response;
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(res, "text/xml");

                if (xmlDoc.getElementsByTagName('parsererror').length) {
                    processMessageBox_obj.innerHTML = '<p class="msg_error">Error: ' + (new XMLSerializer()).serializeToString(xmlDoc) + '</p>';
                    return;
                }

                const s = xmlToJson(xmlDoc);
                field_types = s.fieldtypes.type;

                field_type_loaded = true;
                //processMessageBox_obj.innerHTML="";
            }
        };
        http.send(params);
    } else {
        //error
        //processMessageBox_obj.innerHTML='<p class="msg_error">Cannot connect to the server</p>';
    }
}

function loadTypes(typeparams_box_obj, jform_type, jform_typeparams, typeparams_box) {

    typeparams_box_obj.innerHTML = 'Loading...';

    let url = '';
    if (window.Joomla instanceof Object) {
        let parts = location.href.split("/administrator/");
        url = parts[0] + '/index.php?option=com_customtables&view=xml&xmlfile=fieldtypes&Itemid=-1';
    } else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
        let parts = location.href.split("wp-admin/admin.php?");
        url = parts[0] + 'wp-admin/admin.php?page=customtables-api-xml&xmlfile=fieldtypes';
    } else {
        typeparams_box_obj.innerHTML = 'CMS Not Supported. #A8';
        return;
    }

    const params = "";
    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("GET", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {

            if (http.readyState === 4) {
                let res = http.response;
                let parser = new DOMParser();
                let xmlDoc = parser.parseFromString(res, "application/xml");

                if (xmlDoc.getElementsByTagName('parsererror').length) {
                    typeparams_box_obj.innerHTML = '<p class="msg_error">URL: ' + url + '<br/>Error: ' + (new XMLSerializer()).serializeToString(xmlDoc) + '</p>';
                    return;
                }

                let json_object = xmlToJson(xmlDoc);

                field_types = json_object.fieldtypes.type;
                field_type_loaded = true;
                updateTypeParams(jform_type, jform_typeparams, typeparams_box);
            }
        };
        http.send(params);
    } else {
        //error
        typeparams_box_obj.innerHTML = '<p class="msg_error">Cannot connect to the server</p>';
    }
}

// Changes XML to JSON
function xmlToJson(xml) {

    // Create the return object
    let obj = {};

    if (xml.nodeType === 1) { // element
        // do attributes
        if (xml.attributes.length > 0) {
            obj["@attributes"] = {};
            for (let j = 0; j < xml.attributes.length; j++) {
                const attribute = xml.attributes.item(j);
                obj["@attributes"][attribute.nodeName] = attribute.nodeValue;
            }
        }
    } else if (xml.nodeType === 3) { // text
        obj = xml.nodeValue;
    }

    // do children
    if (xml.hasChildNodes()) {
        for (let i = 0; i < xml.childNodes.length; i++) {
            const item = xml.childNodes.item(i);
            const nodeName = item.nodeName;
            if (typeof (obj[nodeName]) == "undefined") {
                obj[nodeName] = xmlToJson(item);
            } else {
                if (typeof (obj[nodeName].push) == "undefined") {
                    const old = obj[nodeName];
                    obj[nodeName] = [];
                    obj[nodeName].push(old);
                }
                obj[nodeName].push(xmlToJson(item));
            }
        }
    }
    return obj;
}

function parseQuote(str, separator, cleanQuotes) {

    const arr = [];
    let quote = false;  // true means we're inside a quoted field
    let c = 0;

    // iterate over each character, keep track of current field index (i)
    for (let i = 0; c < str.length; c++) {
        const cc = str[c];
        //    var nc = str[c+1];  // current character, next character
        arr[i] = arr[i] || '';           // create a new array value (start with empty string) if necessary

        // If it's just one quotation mark, begin/end quoted field
        if (cc === '"') {
            quote = !quote;

            if (!cleanQuotes)
                arr[i] += cc;

            continue;
        }

        // If it's a comma, and we're not in a quoted field, move on to the next field
        if (Array.isArray(separator)) {
            let s;
            let found = false;
            for (s = 0; s < separator.length; s++) {
                if (cc === separator[s] && !quote) {
                    found = true;
                    break;
                }
            }

            if (cc === separator[s] && !quote) {
                ++i;
                continue;
            }
        } else {
            if (cc === separator && !quote) {
                ++i;
                continue;
            }
        }

        // Otherwise, append the current character to the current field
        arr[i] += cc;
    }

    return arr;
}

String.prototype.replaceAll = function (search, replacement) {
    const target = this;
    return target.split(search).join(replacement);
};
