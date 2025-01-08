/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2025. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
function updateParameters() {

	if (type_obj == null)
		return;

	const typename = type_obj.value;

	//find the type
	const typeparams = findTheType(typename);

	if (typeparams != null) {

		typeparams_box_obj.innerHTML = renderParamBox(typeparams, typeparams_id, typeparams_obj.value, []);

		const param_att = typeparams["@attributes"];
		let rawQuotes = false;
		if (typeof (param_att.rawquotes) != "undefined" && param_att.rawquotes === "1")
			rawQuotes = true;

		const param_array = getParamOptions(typeparams.params, 'param');

		if (typeof (param_att.repetitive) !== "undefined" && param_att.repetitive === "1" && param_array.length === 1)
			updateParamString('fieldtype_param_', 1, -1, typeparams_id, null, rawQuotes);//unlimited number of parameters
		else
			updateParamString('fieldtype_param_', 1, param_array.length, typeparams_id, null, rawQuotes);

	} else
		typeparams_box_obj.innerHTML = '<p class="msg_error">Unknown Field Type</p>';
}

function renderInput_Radio(objName, param, value, onchange) {
	const param_att = param["@attributes"];
	let result = '<fieldset><legend class="visually-hidden"></legend><div class="switcher" id="' + objName + '" data-type="radio">';

	const options = param_att.options.split(",");

	for (let o = 0; o < options.length; o++) {
		const opt = options[o].split("|");
		const id = objName + "" + o;

		let c = '';
		if (opt[0] === '')
			c += 'active ';

		let cssClass = '';//c + (opt[0]==value ? 'valid form-control-success': 'valid');

		result += '<input type="radio" id="' + id + '" name="' + objName + '" value="' + opt[0] + '"' + (opt[0] === value ? ' checked="checked"' : '') + onchange + ' class="' + cssClass + '" aria-invalid="false">';
		result += '<label for="' + id + '" id="' + id + '_label" >' + opt[1] + '</label>		<span class="toggle-outside"><span class="toggle-inside"></span></span>';
	}
	return result + '</div></fieldset>';
}
