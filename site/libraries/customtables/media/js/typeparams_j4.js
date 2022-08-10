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
