var field_types = [];
var field_type_loaded = false;

var parts = location.href.split("/administrator/");
var websiteroot = parts[0] + "/administrator/";
var websiteSiteLibraries = parts[0] + "/components/com_customtables/libraries/customtables/";

var type_obj = null;
var typeparams_id;
var typeparams_obj;
var typeparams_box_id;
var typeparams_box_obj;

var temp_imagesizeparams = [];
var temp_imagesizelist_string = '';
var temp_imagesize_box_id = null;
var temp_imagesize_parambox_id = null;
var temp_imagesize_updateparent = null;

var all_tables = [];
var all_fields = [];

var proversion = false;

function typeChanged() {
    if (typeparams_obj != null)
        typeparams_obj.value = "";

    updateParameters();
}

function updateParameters() {

    if (type_obj == null)
        return;

    var typename = type_obj.value;

    //find the type
    var typeparams = findTheType(typename);

    if (typeparams != null) {

        typeparams_box_obj.innerHTML = renderParamBox(typeparams, typeparams_id, typeparams_obj.value);

        var param_att = typeparams["@attributes"];
        var rawquotes = false;
        if (typeof (param_att.rawquotes) != "undefined" && param_att.rawquotes == "1")
            rawquotes = true;

        var param_array = getParamOptions(typeparams.params, 'param');


        if (typeof (param_att.repeatative) !== "undefined" && param_att.repeatative === "1" && param_array.length == 1)
            updateParamString('fieldtype_param_', 1, -1, typeparams_id, null, rawquotes);//unlimited number of parameters
        else
            updateParamString('fieldtype_param_', 1, param_array.length, typeparams_id, null, rawquotes);

    } else
        typeparams_box_obj.innerHTML = '<p class="msg_error">Unknown Field Type</p>';
}

function updateTypeParams(type_id, typeparams_id_, typeparams_box_id_)//type selection
{
    //type_obj_id=type_id;

    //current_params_count=0;
    type_obj = document.getElementById(type_id);

    typeparams_id = typeparams_id_;
    typeparams_obj = document.getElementById(typeparams_id);

    //typeparams_id=typeparams_id;
    typeparams_box_id = typeparams_box_id_;
    typeparams_box_obj = document.getElementById(typeparams_box_id_);

    if (!field_type_loaded) {
        loadTypes(typeparams_box_obj, type_id, typeparams_id, typeparams_box_id_);
    } else {
        updateParameters();
    }

}


function getParamOptions(param, optionobjectname) {
    var options = [];

    if (typeof (param) !== "undefined" && typeof (param[optionobjectname]) !== "undefined") {
        if (param[optionobjectname].constructor.name != "Array")
            options.push(param[optionobjectname]);
        else
            options = param[optionobjectname];
    }

    return options;
}


function renderParamList(typeparams, typeparams_box, paramvaluestring) {
    var result = '';
    var att = typeparams["@attributes"];
    var param_array = getParamOptions(typeparams.params, 'param');

    var vlu = '';
    var values = parseQuote(paramvaluestring, ',', true);

    if (typeof (att.repeatative) !== "undefined" && att.repeatative === "1" && param_array.length == 1) {
        for (var i = 0; i < values.length; i++) {
            vlu = '';
            if (values.length > i)
                vlu = values[i];

            result += inputBoxPreRender(proversion, param_array[0], -1, i, vlu, att, typeparams_box);
        }


        result += inputBoxPreRender(proversion, param_array[0], -1, values.length, '', att, typeparams_box);

    } else {
        for (var i2 = 0; i2 < param_array.length; i2++) {
            vlu = '';
            if (values.length > i2)
                vlu = values[i2];

            var param = param_array[i2];

            result += inputBoxPreRender(proversion, param, param_array.length, i2, vlu, att, typeparams_box);
        }
    }

    return result;
}

function renderParamBox(typeparams, typeparams_box, paramvaluestring) {
    //current_params_count=0;
    var att = typeparams["@attributes"];

    var result = '<h4>' + att.label + '</h4>';
    result += '<p>' + att.description;


    if (typeof (att.helplink) !== "undefined")
        result += ' <a href="' + att.helplink + '" target="_blank">Read more</a>';

    result += '</p>';


    result += '<div class="form-horizontal typeparams_box_area">';


    var param_array = getParamOptions(typeparams.params, 'param');


    if (param_array.length > 0) {
        result += '<hr/>';

        var tag_pair = [];

        if (typeof (att.name) !== "undefined")
            tag_pair = parseQuote(att.name, [':', '='], false);
        else if (typeof (att.ct_name) !== "undefined")
            tag_pair = parseQuote(att.ct_name, [':', '='], false);

        result += '<div id="modalParamTagName" style="display:none">' + tag_pair[0] + '</div>';
        result += '<div id="modalParamList">' + renderParamList(typeparams, typeparams_box, paramvaluestring) + '</div>';
    }

    if (!proversion && typeof (att.proversion) !== "undefined" && att.proversion === "1") {
        result += '<div id="" class="fieldtype_disable_box"></div>';
        result += '</div>';
        result = '<p style="color:red;">This Field Type available in PRO version only.</p>' + result;

    } else {
        result += '</div>';
    }


    return result;

}

function inputBoxPreRender(proversion, param, param_count, i, vlu, att, typeparams_box) {
    var repeatative = false;
    if (typeof (att.repeatative) !== "undefined" && att.repeatative === "1")
        repeatative = true;

    var result = '';

    var param_att = param["@attributes"];

    if (proversion || typeof (param_att.proversion) === "undefined" || param_att.proversion === "0") {

        result += '<div class="control-group"><div class="control-label">';
        //required'+param.label+'

        var label = "";
        if (typeof (param_att) !== "undefined" && typeof (param_att.label) !== "undefined") {
            if (param_count == -1)
                label = param_att.label + " (" + (i + 1) + ")";
            else
                label = param_att.label;
        }

        var description = "";
        if (typeof (param_att) !== "undefined" && typeof (param_att.description) !== "undefined")
            description = param_att.description;

        result += '<label id="fieldtype_param_' + i + '-lbl" for="fieldtype_param_' + i + '" class="hasPopover" title="" data-content="' + description + '"';
        result += ' data-original-title="' + label + '"';
        result += ' >';
        result += label;

        result += '</label>';
        result += '</div><div class="controls">';

        //var vlu='';
        //if(values.length>i)
        //vlu=values[i];

        var rawquotes = "false";
        if (typeof (att.rawquotes) != "undefined" && att.rawquotes == "1")
            rawquotes = "true";

        var startchar = "[";

        if (typeof (att.startchar) !== "undefined")
            startchar = att.startchar;

        var refresh = 'false';
        if (repeatative)
            refresh = 'true';

        var attributes = 'onchange="updateParamString(\'fieldtype_param_\',1,' + param_count + ',\'' + typeparams_box + '\',event,' + rawquotes + ',' + refresh + ',\'' + startchar + '\');"';

        result += renderInputBox('fieldtype_param_' + i, param, vlu, attributes);

        result += '</div></div>';
    }
    return result;
}

function getInputType(obj) {
    if (obj && typeof (obj) == "object" && obj.childNodes && typeof (obj.childNodes) !== "undefined") {
        for (i = 0; i < obj.childNodes.length; i++) {
            if (obj.childNodes[i].tagName == "INPUT") {
                return obj.childNodes[i].type;
            }
            if (obj.childNodes[i].tagName == "SELECT") {
                return 'select';
            }
        }
    }
    return '';
}

function updateParamString(inputboxid, countlist, countparams, objectid, e, rawquotes, refresh, startchar) {

    if (typeof (startchar) == "undefined")
        startchar = "{";

    let endchar = '}';
    if (startchar == '[')
        endchar = ']';

    //objectid is the element id where value will be set
    if (e != null)
        e.preventDefault();

    let count = 0;
    let list = [];

    for (let r = 0; r < countlist; r++) {
        let params = [];

        for (let i = 0; i < countparams || countparams == -1; i++) { // -1 "unlimited" number of parameters
            let objectname = inputboxid;

            if (r > 0)
                objectname += r + 'x';

            objectname += i;
            let obj = document.getElementById(objectname);

            if (obj) {
                let t = getInputType(obj);
                let v = "";

                if (t === "radio")
                    v = getRadioValue(objectname);
                else if (t === "multiselect")
                    v = getSelectValues(select).merge(",");
                else
                    v = obj.value;

                if (isNaN(v) && v != 'true' && v != 'false') {
                    if (v.indexOf('"') != -1)
                        v = v.replaceAll('"', '****quote****');

                    if (v.indexOf("'") != -1)
                        v = v.replaceAll("'", '****apos****');

                    v = '"' + v + '"';
                }
                params.push(v);
                if (v != "")
                    count = i + 1; //to include all previous parameters even if they are empty
            } else
                break;
        }

        let tmp_params = "";
        let newparams = [];

        if (count > 0) {
            for (let i2 = 0; i2 < count; i2++) {

                let v = params[i2];
                if (v == '')
                    v = '"' + v + '"';

                newparams.push(v);
            }
            tmp_params = newparams.join(",");
        }
        list.push(tmp_params);
    }

    let tmp_list = list.join(";");
    let typeparams_obj = document.getElementById(objectid);

    if (typeparams_obj) {
        typeparams_obj.value = tmp_list;  // why is it here?
        typeparams_obj.innerHTML = tmp_list;
    }

    if (refresh) {
        let mptn = document.getElementById("modalParamTagName");
        if (mptn) {
            let typename = mptn.innerHTML;
            let typeparams = findTagObjectByName(startchar, endchar, typename);
            if (typeparams != null) {
                let obj2 = document.getElementById("modalParamList");
                if (obj2)
                    obj2.innerHTML = renderParamList(typeparams, "current_tagparameter", tmp_list);
            }
        }
    }
    return false;
}

function getSelectValues(select) {
    var result = [];
    var options = select && select.options;
    var opt;

    for (var i = 0, iLen = options.length; i < iLen; i++) {
        opt = options[i];

        if (opt.selected) {
            result.push(opt.value || opt.text);
        }
    }
    return result;
}

function getRadioValue(objectname) {
    var radios = document.getElementsByName(objectname);
    var v = "";
    var length = radios.length;

    for (var i = 0; i < length; i++) {
        var id = radios[i].getAttribute('id');
        var label_obj = document.getElementById(id + "_label");
        var label_class = label_obj.getAttribute('class');
        label_class = "btn";//label_class.replace(" active","");

        if (radios[i].checked) {
            v = radios[i].value;
        }
    }

    return v;

}

function renderInput_Radio(objname, param, value, onchange) {
    var param_att = param["@attributes"];

    var result = '<fieldset>';
    result += '<legend class="visually-hidden">Label3</legend>';
    result += '<div class="switcher" id="' + objname + '">';

    var options = param_att.options.split(",");

    for (var o = 0; o < options.length; o++) {
        var opt = options[o].split("|");
        var id = objname + "" + o;

        var c = '';
        if (opt[0] == '')
            c += 'active ';

        let cssclass = '';//c + (opt[0]==value ? 'valid form-control-success': 'valid');

        result += '<input type="radio" id="' + id + '" name="' + objname + '" value="' + opt[0] + '"' + (opt[0] == value ? ' checked="checked"' : '') + onchange + ' class="' + cssclass + '" aria-invalid="false">';
        result += '<label for="' + id + '" id="' + id + '_label" >' + opt[1] + '</label>		<span class="toggle-outside"><span class="toggle-inside"></span></span>';
    }
    result += '</div></fieldset>';

    return result;
}

function renderInput_Multiselect(id, param, values, onchange) {
    var options = getParamOptions(param, 'option');

    var values_array = values.split(",");

    var result = "";

    result += '<select id="' + id + '" ' + onchange + ' multiple="multiple">';

    for (var o = 0; o < options.length; o++) {
        var opt = options[o]["@attributes"];

        if (proversion || typeof (opt.proversion) === "undefined" || opt.proversion === "0") {
            if (values != '' && values_array.indexOf(opt.value) !== -1)
                result += '<option value="' + opt.value + '" selected="selected">' + opt.label + '</option>';
            else
                result += '<option value="' + opt.value + '" >' + opt.label + '</option>';
        }

    }

    result += '</select>';

    return result;
}

function renderInput_ImageSizeList(id, param, value, attributes) {

    var result = "";
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


    result += '<input type="text" id="' + id + '" value="' + value + '" style="display:none;width:100%;" ' + attributes + '>';// '+onchange+'>';


    return result;
}

function BuildImageSizeTable() {
    var value = temp_imagesizelist_string;//document.getElementById(temp_imagesize_parambox_id).value;
    var value_array = value.split(";");

    var result = '';


    var i;
    var param = null;
    var param_att = null;

    var blank_value = [];

    var count_list = value_array.length;
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


        var count_params = temp_imagesizeparams.length;

        for (var r = 0; r < count_list; r++) {
            var values = value_array[r].split(',');

            result += '<tr>';
            for (i = 0; i < count_params; i++) {
                param = temp_imagesizeparams[i];
                param_att = param["@attributes"];

                var vlu = "";

                if (values.length > i)
                    vlu = values[i];

                var id = 'size_param_';

                if (r > 0)
                    id += r + 'x';

                id += i;


                var attributes = 'onchange="updateParamString_ImageSizes(\'size_param_\',' + count_list + ',' + count_params + ',\'' + temp_imagesize_parambox_id + '\',event,false);"';

                if (typeof (param_att.style) != "undefined")
                    attributes += 'style="' + param_att.style + '"';

                result += '<td style="padding-right:5px;">' + renderInputBox(id, param, vlu, attributes) + '</td>';

            }
            result += '<td><div class="btn-wrapper" id="toolbar-delete"><button onclick="deleteImageSize(' + r + ');" type="button" class="btn btn-small"><span class="icon-delete"></span></button></div></td>';
            result += '</tr>';

        }

        result += '</tbody>';
        result += '</table>';

    }

    result += '<button onclick=\'addImageSize("' + blank_value.join(",") + '")\' class="btn btn-small btn-success" type="button" style="margin-top:5px;">';
    result += '<span class="icon-new icon-white"></span><span style="margin-left:10px;">Add Image Size</span></button>';//<hr/>

    return result;
}

function updateParamString_ImageSizes(sizeparam, countlist, countparams, tempimagesizeparamboxid, e) {

    updateParamString(sizeparam, countlist, countparams, tempimagesizeparamboxid, e, false);

    var obj = document.getElementById(temp_imagesize_parambox_id);
    temp_imagesizelist_string = obj.value;

    var sizeparam_ = temp_imagesize_updateparent[0];
    var countlist_ = temp_imagesize_updateparent[1];
    var countparams_ = temp_imagesize_updateparent[2];
    var tempimagesizeparamboxid_ = temp_imagesize_updateparent[3];

    updateParamString(sizeparam_, countlist_, countparams_, tempimagesizeparamboxid_, null, false);

}

function deleteImageSize(index) {
    var obj = document.getElementById(temp_imagesize_parambox_id);
    var value = obj.value;
    var value_array = value.split(";");
    value_array.splice(index, 1);

    temp_imagesizelist_string = value_array.join(';');
    obj.value = temp_imagesizelist_string;

    var obj2 = document.getElementById(temp_imagesize_box_id);
    obj2.innerHTML = BuildImageSizeTable();

    var sizeparam_ = temp_imagesize_updateparent[0];
    var countlist_ = temp_imagesize_updateparent[1];
    var countparams_ = temp_imagesize_updateparent[2];
    var tempimagesizeparamboxid_ = temp_imagesize_updateparent[3];


    updateParamString(sizeparam_, countlist_, countparams_, tempimagesizeparamboxid_, null, false);
}

function addImageSize(vlu) {
    var obj = document.getElementById(temp_imagesize_parambox_id);
    var value = obj.value;
    if (value == '')
        temp_imagesizelist_string = value + ',';
    else
        temp_imagesizelist_string = value + ';';
    obj.value = temp_imagesizelist_string;

    var obj2 = document.getElementById(temp_imagesize_box_id);
    obj2.innerHTML = BuildImageSizeTable();

    var sizeparam_ = temp_imagesize_updateparent[0];
    var countlist_ = temp_imagesize_updateparent[1];
    var countparams_ = temp_imagesize_updateparent[2];
    var tempimagesizeparamboxid_ = temp_imagesize_updateparent[3];


    updateParamString(sizeparam_, countlist_, countparams_, tempimagesizeparamboxid_, null, false);
}


function renderInput_List(id, param, value, onchange) {
    var options = getParamOptions(param, 'option');
    var result = "";

    result += '<select id="' + id + '" ' + onchange + '>';

    for (var o = 0; o < options.length; o++) {
        var opt = options[o]["@attributes"];

        if (opt.value == value)
            result += '<option value="' + opt.value + '" selected="selected">' + opt.label + '</option>';
        else
            result += '<option value="' + opt.value + '" >' + opt.label + '</option>';
    }

    result += '</select>';

    return result;
}

function renderInput_Language(id, param, value, onchange) {
    var result = "";

    result += '<select id="' + id + '" ' + onchange + '>';


    if (value == "")
        result += '<option value="" selected="selected">- Default Language</option>';
    else
        result += '<option value="" >- Default Language</option>';

    for (var o = 0; o < languages.length; o++) {
        if (languages[o][0] == value)
            result += '<option value="' + languages[o][0] + '" selected="selected">' + languages[o][1] + '</option>';
        else
            result += '<option value="' + languages[o][0] + '" >' + languages[o][1] + '</option>';
    }

    result += '</select>';

    return result;
}

function renderInput_Table(id, param, value, onchange) {
    var obj = document.getElementById('jform_tableid');
    var currentTable = obj.value;

    var result = "";


    result += '<select id="' + id + '" ' + onchange + '>';


    if (value == "")
        result += '<option value="" selected="selected">- Select Table</option>';
    else
        result += '<option value="" >- Select Table</option>';

    for (var o = 0; o < all_tables.length; o++) {
        var option = all_tables[o];

        //if(option[0]!=currentTable)
        //{
        if (option[1] == value)
            result += '<option value="' + option[1] + '" selected="selected">' + option[2] + '</option>';
        else
            result += '<option value="' + option[1] + '" >' + option[2] + '</option>';
        //}
    }

    result += '</select>';

    return result;
}

function renderInput_Field_do(id, value, onchange) {
    var result = "";

    result += '<select id="' + id + '" ' + onchange + '>';

    if (value == "")
        result += '<option value="" selected="selected">- Select Field</option>';
    else
        result += '<option value="" >- Select Field</option>';

    var l = wizardFields.length;
    for (var index = 0; index < l; index++) {
        var field = wizardFields[index];
        if (field.fieldname == value)
            result += '<option value="' + field.fieldname + '" selected="selected">' + field.fieldtitle + '</option>';
        else
            result += '<option value="' + field.fieldname + '">' + field.fieldtitle + '</option>';
    }

    result += '</select>';
    return result;
}

function renderInput_Field_readSelectBoxes(id) {
    var value = '';
    var i = 0;
    var allHaveSelections = true;
    var indexes = [];
    var values = [];
    var onchangefound = false;
    var onchange_function = "";

    while (1 == 1) {
        var obj = document.getElementById(id + '_' + i);
        if (!obj)
            break;

        var v = obj.value;

        if (v != "") {
            if (indexes.indexOf(obj.selectedIndex) == -1) {
                if (value != '')
                    value += ',';

                value += v;
            }

            if (!onchangefound) {
                var o = String(obj.onchange);
                var oParts = o.split(";");
                if (oParts.length == 3) {
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
        //add ne select box
        var selectboxes = document.getElementById(id + '_selectboxes');

        var o2 = 'onchange=\'renderInput_Field_readSelectBoxes("' + id + '");' + onchange_function + '\';';
        var result = selectboxes.innerHTML + renderInput_Field_do(id + "_" + i, '', o2);

        selectboxes.innerHTML = result;

        //update values
        for (var n = 0; n < i; n++) {
            var obj2 = document.getElementById(id + '_' + n);
            if (!obj2)
                break;

            obj2.selectedIndex = indexes[n];
            obj2.value = values[n];
        }
    }
}

function renderInput_Field(id, param, value, onchange) {

    var param_att = param["@attributes"];
    var repeatative = 0;
    if (typeof (param_att.repeatative) != "undefined" && param_att.repeatative == "1")
        repeatative = 1;

    if (repeatative == 1) {
        var result = "";
        var onchange_parts = onchange.split('"');
        if (onchange_parts.length < 3)
            return 'Something wrong with onchange attribute';

        var onchange_function = onchange_parts[1];//onCahnage function
        onchange_function = onchange_function.replace(/'/g, '"');


        var o = 'onchange=\'renderInput_Field_readSelectBoxes("' + id + '");' + onchange_function + '\'';
        var i = 0;
        if (value != '') {
            var fields = value.split(",");

            for (i = 0; i < fields.length; i++)
                result += renderInput_Field_do(id + "_" + i, fields[i], o);
        }

        result += renderInput_Field_do(id + "_" + i, '', o);

        return '<input type="hidden" id="' + id + '" ' + onchange + ' value=\'' + value + '\' /><div id="' + id + '_selectboxes">' + result + '</div>';
    } else
        return renderInput_Field_do(id, value, onchange);
}

function renderInput_Layout_checktype(layouttype_str, t) {
    //this function accepts one or more layout tpes. example: catalogpage,simplecatalog
    //if at least one type matches layout type number return true
    if (layouttype_str == '')
        return true; //any type

    var types = ['', 'simplecatalog', 'editform', 'record', 'details', 'catalogpage', 'catalogitem', 'email', 'xml', 'csv', 'json'];

    var parts = layouttype_str.split(",");

    for (var i = 0; i < parts.length; i++) {
        if (types.indexOf(parts[i]) == t)
            return true;
    }

    return false;
}

function renderInput_Layout(id, param, value, onchange) {
    let param_att = param["@attributes"];

    let currentLayout = document.getElementById('jform_layoutname').value;
    let currentTable = document.getElementById('jform_tableid').value;

    let layout_table = "";
    if (param_att.table != null)
        layout_table = param_att.table;

    let layout_type = "";
    if (param_att.layouttype != null)
        layout_type = param_att.layouttype;

    let result = '<select id="' + id + '" ' + onchange + ' class="ct_improved_selectbox">';

    result += '<option value="" ' + (value == "" ? 'selected="selected"' : '') + '>- Select Layout</option>';

    for (let o = 0; o < wizardLayouts.length; o++) {
        let ok = true;
        let option = wizardLayouts[o];

        if (layout_table == "current") {
            if (option.tableid != currentTable)
                ok = false;
        }

        if (ok && option.layoutname != currentLayout) {   //table checked not checking layout type
            if (renderInput_Layout_checktype(layout_type, parseInt(option.layouttype)))
                result += '<option value="' + option.layoutname + '"' + (option.layoutname == value ? 'selected="selected"' : '') + '>' + option.layoutname + '</option>';
        }
    }
    return result += '</select>';
}

function renderInput_Folder(id, value, onchange) {
    //Here we will take existing "folderlist" element (generated by Joomla!) and we replace id, value etc. to make a new one.
    var typebaseobject = document.getElementById("ct_fieldtypeeditor_box");
    var result = typebaseobject.innerHTML;
    /*
            result=result.replace('name="ct_fieldtypeeditor"','name="ct_fieldtypeeditor" '+onchange);
            result=result.replace(/ct_fieldtypeeditor/g, id);
            result=result.replace(' style="display: none;"','');

            result=result.replace('value="'+value+'">','value="'+value+'" selected="selected">');

            var p1=result.indexOf('<select');
            var p2=result.indexOf('</select>');
            */
    var p1 = result.indexOf('<option');
    var p2 = result.indexOf('</select>');

    var datalist = result.substring(p1, p2 - 1);

    datalist = datalist.replace(/option value=\"/g, 'option value="/images/');
    datalist = datalist.replace(/">/g, '">/images/');

    var new_result = '<input list="' + id + '_list" id="' + id + '" style="width:90%" value="' + value + '" ' + onchange + '>';
    new_result += '<datalist id="' + id + '_list">';
    new_result += datalist;
    new_result += '</datalist>';

    //result=result.substring(p1,p2+9);
    return new_result;
}

function renderInputBox(id, param, vlu, attributes) {
    var param_att = param["@attributes"];

    var result = '';
    if (param_att.type != null) {
        if (param_att.type === "number") {
            if (vlu == '') {
                if (typeof (param_att.default) != "undefined")
                    vlu = param_att.default;
            }

            var extra = "";
            if (param.min != null)
                extra += ' min="' + param_att.min + '"';

            if (param_att.max != null)
                extra += ' max="' + param_att.max + '"';

            if (param_att.min != null)
                extra += ' step="' + param_att.step + '"';

            result += '<input type="number" id="' + id + '" value="' + vlu + '" ' + extra + ' ' + attributes + '>';
        } else if (param_att.type === "list") {
            result = renderInput_List(id, param, vlu, attributes);
        } else if (param_att.type === "language") {
            result = renderInput_Language(id, param, vlu, attributes);
        } else if (param_att.type === "table") {
            result = renderInput_Table(id, param, vlu, attributes);
        } else if (param_att.type === "field") {
            result = renderInput_Field(id, param, vlu, attributes);
        } else if (param_att.type === "layout") {
            result = renderInput_Layout(id, param, vlu, attributes);
        } else if (param_att.type === "multiselect") {
            result = renderInput_Multiselect(id, param, vlu, attributes);
        } else if (param_att.type === "imagesizelist") {
            result = renderInput_ImageSizeList(id, param, vlu, attributes);
        } else if (param_att.type === "folder") {
            vlu = vlu.replaceAll('****quote****', '&quot;');
            vlu = vlu.replaceAll('****apos****', "&apos;");

            result = renderInput_Folder(id, vlu, attributes);
        } else if (param_att.type === "radio") {
            result = renderInput_Radio(id, param, vlu, attributes);
        } else {
            if (vlu == '') {
                if (typeof (param_att.default) != "undefined")
                    vlu = param_att.default;
            }

            vlu = vlu.replaceAll('****quote****', '&quot;');
            vlu = vlu.replaceAll('****apos****', "&apos;");

            result += '<input type="text" id="' + id + '" value="' + vlu + '" ' + attributes + '>';
        }
    } else {


        result += '<input type="text" id="' + id + '" value="' + vlu + '" ' + attributes + '>';
    }

    return result;
}


function findTheType(typename) {

    for (var i = 0; i < field_types.length; i++) {

        var n = field_types[i]["@attributes"].ct_name;

        if (n == typename) {

            return field_types[i];//["@attributes"];
        }
    }
    return null;
}


function loadTypes_silent(processMessageBox) {
    //var processMessageBox_obj=document.getElementById(processMessageBox);
    //processMessageBox_obj.innerHTML='Loading Fields Types...';

    const url = websiteSiteLibraries + "media/xml/fieldtypes.xml";

    const params = "";

    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("GET", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {

            if (http.readyState == 4) {
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

    const url = websiteSiteLibraries + "media/xml/fieldtypes.xml";
    const params = "";

    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("GET", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {

            if (http.readyState == 4) {
                let res = http.response;
                ;

                let parser = new DOMParser();

                let xmlDoc = parser.parseFromString(res, "application/xml");


                if (xmlDoc.getElementsByTagName('parsererror').length) {
                    typeparams_box_obj.innerHTML = '<p class="msg_error">Error: ' + (new XMLSerializer()).serializeToString(xmlDoc) + '</p>';
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
    var obj = {};

    if (xml.nodeType == 1) { // element
        // do attributes
        if (xml.attributes.length > 0) {
            obj["@attributes"] = {};
            for (var j = 0; j < xml.attributes.length; j++) {
                var attribute = xml.attributes.item(j);
                obj["@attributes"][attribute.nodeName] = attribute.nodeValue;
            }
        }
    } else if (xml.nodeType == 3) { // text
        obj = xml.nodeValue;
    }

    // do children
    if (xml.hasChildNodes()) {
        for (var i = 0; i < xml.childNodes.length; i++) {
            var item = xml.childNodes.item(i);
            var nodeName = item.nodeName;
            if (typeof (obj[nodeName]) == "undefined") {
                obj[nodeName] = xmlToJson(item);
            } else {
                if (typeof (obj[nodeName].push) == "undefined") {
                    var old = obj[nodeName];
                    obj[nodeName] = [];
                    obj[nodeName].push(old);
                }
                obj[nodeName].push(xmlToJson(item));
            }
        }
    }
    return obj;
}

function parseQuote(str, separator, cleanquotes) {

    var arr = [];
    var quote = false;  // true means we're inside a quoted field
    var c = 0;

    // iterate over each character, keep track of current field index (i)
    for (var i = 0; c < str.length; c++) {
        var cc = str[c];
        //    var nc = str[c+1];  // current character, next character
        arr[i] = arr[i] || '';           // create a new array value (start with empty string) if necessary

        // If it's just one quotation mark, begin/end quoted field
        if (cc == '"') {
            quote = !quote;

            if (!cleanquotes)
                arr[i] += cc;

            continue;
        }

        // If it's a comma, and we're not in a quoted field, move on to the next field
        if (Array.isArray(separator)) {
            var found = false
            for (var s = 0; s < separator.length; s++) {
                if (cc == separator[s] && !quote) {
                    found = true;
                    break;
                }
            }

            if (cc == separator[s] && !quote) {
                ++i;
                continue;
            }
        } else {
            if (cc == separator && !quote) {
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
    var target = this;
    return target.split(search).join(replacement);
};
