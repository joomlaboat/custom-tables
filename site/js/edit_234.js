function setTask(event, task, returnlink, submitForm) {

	event.preventDefault();

    if (returnlink != "") {
        let obj = document.getElementById('returnto');
        if (obj)
            obj.value = returnlink;
    }

    let obj2 = document.getElementById('task');
    if (obj2)
        obj2.value = task;
    else
        alert("Task Element not found.");

    if (submitForm) {
        let objForm = document.getElementById('eseditForm');
        if (objForm) {
			
			const tasks_with_validation = ['saveandcontinue', 'save', 'saveandprint', 'saveascopy'];
			
			if(tasks_with_validation.includes(task))
			{
				if (checkRequiredFields())
					objForm.submit();
			}
			else
                objForm.submit();

        } else
            alert("Form not found.");
    }
}

function recaptchaCallback() {
    let obj1 = document.getElementById("customtables_submitbutton");
    if (typeof obj1 != "undefined")
        obj1.removeAttribute('disabled');

    let obj2 = document.getElementById("customtables_submitbuttonasnew");
    if (typeof obj2 != "undefined")
        obj2.removeAttribute('disabled');
}

function checkFilters() {
	
    let passed = true;
    let inputs = document.getElementsByTagName('input');

    for (let i = 0; i < inputs.length; i++) {
        let t = inputs[i].type.toLowerCase();

        if (t == 'text' && inputs[i].value != "") {
            let n = inputs[i].name.toString();
            let d = inputs[i].dataset;
            let label = "";

            if (d.label)
                label = d.label;

            if (d.sanitizers)
                doSanitanization(inputs[i], d.sanitizers);

            if (d.filters)
			{
                passed = doFilters(inputs[i], label, d.filters);
				if(!passed)
					return false;
			}
				
			if (d.valuerule)
			{
				let caption="";
				if(d.valuerulecaption)
					caption=d.valuerulecaption;
					
				passed = doValuerules(inputs[i], label, d.valuerule,caption);
				if(!passed)
					return false;
			}
        }
    }
    return passed;
}

//https://stackoverflow.com/questions/5717093/check-if-a-javascript-string-is-a-url
function isValidURL(str) {
    let regex = /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/;
    if (!regex.test(str)) {
        return false;
    } else {
        return true;
    }
}

function doValuerules(obj, label, valuerules,caption)
{
	let ct_fielname = obj.name.replaceAll('comes_', '');
	let valuerules_and_arguments=doValuerules_ParseValues(valuerules,ct_fielname);
	
	if(valuerules_and_arguments == null)
		return true;
	
	let result = false;
	
	let rules_str = "return " + valuerules_and_arguments.new_valuerules;
	
	try {
		let rules = new Function(rules_str); // this |x| refers global |x|
		result = rules(valuerules_and_arguments.new_args);
	} catch (error) {
		alert('Validation rule "' + valuerules +'" has an error: ' + error);
	}

	if(result)
		return true;
		
	if(caption == '')
		caption = 'Invalid value for "' + label + '"';
		
	alert(caption);
	
	return false;
}

function doValuerules_ParseValues(valuerules,ct_fielname)
{
	//let matches=valuerules.match(/(?<=\[)[^\][]*(?=])/g);  Doesn't work on Safari
	let matches=valuerules.match(/\[(.*?)\]/g); // return example: ["[subject]","[date]"]
	
	if(matches == null)
	{
		valuerules = '[' + ct_fielname + ']' + valuerules;
		matches=valuerules.match(/\[(.*?)\]/g); // return example: ["[subject]","[date]"]
		
		if(matches == null)
			return null;
	}
		
	let args=[];
	
	for(let i=0;i<matches.length;i++)
	{
		let fieldname = matches[i].replace("[","").replace("]","");
		let objID = "comes_" + fieldname;
		let obj = document.getElementById(objID);
		
		if(obj){
			valuerules = valuerules.replaceAll("[" + fieldname + "]", 'arguments[0][' + i +']');
			args[i] = obj.value;
		}
	}
	return {new_valuerules : valuerules, new_args : args} ;
}

function doFilters(obj, label, filters_string)
{
    let filters = filters_string.split(",");
    let value = obj.value;

    for (let i = 0; i < filters.length; i++) {
        let filter_parts = filters[i].split(':');
        let filter = filter_parts[0];

        if (filter == 'url') {
            if (!isValidURL(value)) {
                alert('The ' + label + ' "' + value + '" is not a valid URL.');
                return false;
            }
        } else if (filter == 'https') {
            if (value.indexOf("https") != 0) {
                alert('The ' + label + ' "' + value + '" must be secure - must start with "https://".');
                return false;
            }
        } else if (filter == 'domain' && filter_parts.length > 1) {
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

                if (domains[f] == hostname) {
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

function checkRequiredFields()
{
    if (!checkFilters())
        return false;
	
    let requiredFields = document.getElementsByClassName("required");

    for (let i = 0; i < requiredFields.length; i++)
	{
		if (typeof requiredFields[i].id != "undefined") {
            if (requiredFields[i].id.indexOf("sqljoin_table_comes_") != -1) {
                if (!CheckSQLJoinRadioSelections(requiredFields[i].id))
                    return false;

            }
            if (requiredFields[i].id.indexOf("ct_ubloadfile_box_") != -1) {
                if (!CheckImageUploader(requiredFields[i].id))
                    return false;
            }
        }

        if (typeof requiredFields[i].name != "undefined") {
            let n = requiredFields[i].name.toString();

            if (n.indexOf("comes_") != -1) {
				
				let objname = n.replace('_selector', '');

                let label = "One field";

                let d = requiredFields[i].dataset;
                if (d.label)
                    label = d.label;

                if (requiredFields[i].type == "text") {
                    let obj = document.getElementById(objname);
                    if (obj.value == '') {
                        alert(label + " required.");
                        return false;
                    }
                } else if (requiredFields[i].type == "select-one") {
                    let obj = document.getElementById(objname);
					let v = obj.value;
					
                    if (obj.value === null || obj.value == '') {
                        alert(label + " not selected.");
                        return false;
                    }
                } else if (requiredFields[i].type == "select-multiple") {
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

function SetUsetInvalidClass(id, isOk) {
    let frameObj = document.getElementById(id);

    let c = frameObj.className;
    if (c.indexOf("invalid") == -1) {
        if (!isOk) {
            if (c == "")
                c = "invalid";
            else
                c = c + " invalid";
        }
    } else {
        if (isOk)
            c = c.replace("invalid", "");
    }

    frameObj.className = c;
}

function CheckImageUploader(id) {
    let objid = id.replace("ct_ubloadfile_box_", "comes_");
    let obj = document.getElementById(objid);
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
        let lblobj = document.getElementById(obj_name + "-lbl");
        let label = lblobj.innerHTML;
        alert(label + " not selected.");
        return false;
    }

    return true;
}

function clearListingID() {

    let obj = document.getElementById("listing_id");
    obj.value = "";

    let frm = document.getElementById("eseditForm");
    frm.submit();
}

function recaptchaCallback()
{
	let buttons=['save','saveandclose','saveandprint','saveandcopy','delete'];
	for(let i=0;i<buttons.length;i++){
		let button = 'customtables_button_' + buttons[i];
		let obj = document.getElementById(button);

		if(obj)
			obj.disabled=false;
	}
}

function ctRenderTableJoinSelectBox(control_name, r, index, execute_all,sub_index,parent_object_id)
{
	let wrapper = document.getElementById(control_name + "Wrapper");
	let filters = [];
	if(wrapper.dataset.valuefilters != '')
		filters = JSON.parse(atob(wrapper.dataset.valuefilters));
	
	let next_index = index;
	let next_sub_index = sub_index;
	let val = ''
	
	
		if(Array.isArray(filters[index]))
		{
			//Self Parent field
			next_sub_index += 1;
			if(next_sub_index == filters[index].length)
			{
				// Max sub index reached
				next_sub_index = 0;
				next_index += 1;
			
				if(Array.isArray(filters[next_index]))
					val = filters[next_index][next_sub_index];
				else
					val = filters[next_index];
			}
			else
				val = filters[next_index][next_sub_index];

			selfparent = true;
		}
		else
		{
			next_index += 1;
			val = filters[next_index];
		}
		
	if(!execute_all)
		val = null;
		
	if(r.error)
	{
		alert(r.error);
	}
	else
	{
		if(r.length == 0)
		{
			if(Array.isArray(filters[next_index]))
			{
				document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = "No element to select. Select another parent element.";
				
				ctUpdateTableJoinLink(control_name,next_index + 1,false,0,parent_object_id);
			}
			else
			{
				document.getElementById(control_name + "Selector" + index).innerHTML = "No element to select. Select another parent element.";
			}
			return '';
		}
		
		let result = ''
		
		if(next_index + 1 < filters.length)
		{
			//Add select box
			
			let current_object_id = control_name + index;
		
			if(Array.isArray(filters[index]))
				current_object_id += '_' + sub_index;
			
			let onChangeAttribute = ' onChange="ctUpdateTableJoinLink(\'' + control_name + '\', ' + next_index + ', false, ' + next_sub_index + ',\'' + current_object_id + '\')"';
			result += '<select id="' + current_object_id + '"' + onChangeAttribute + '>';	
		}
		else
			result += '<select id="' + control_name + '" name="' + control_name + '">';	
		
		result += '<option value="">- Select</option>';
		
		for(let i = 0;i < r.length; i++)
			result += '<option value="' + r[i].id + '"' + (r[i].id == val ? ' selected="selected"' : '') + '>' + r[i].label + '</option>';

		result += '</select>';
		
		//Prepare the space for next elements
		//alert(next_index)
		if(Array.isArray(filters[next_index]))
			result += '<div id="' + control_name + 'Selector' + next_index + '_' + next_sub_index + '"></div>';

		result += '<div id="' + control_name + 'Selector' + next_index + '"></div>';

		//Add content to the element
		if((index > 0 || sub_index >0 ) && Array.isArray(filters[index]))
		{
			//alert(control_name + "Selector" + index + '_' + sub_index);
			document.getElementById(control_name + "Selector" + index + '_' + sub_index).innerHTML = result;
		}
		else
			document.getElementById(control_name + "Selector" + index).innerHTML = result;
		
		if(execute_all && next_index + 1 < filters.length)
			ctUpdateTableJoinLink(control_name,next_index,true,next_sub_index,null);
	}
}

function ctUpdateTableJoinLink(control_name,index,execute_all,sub_index,object_id)
{
	let wrapper = document.getElementById(control_name + "Wrapper");
	let url = 'index.php?option=com_customtables&view=catalog&tmpl=component&from=json&key=' + wrapper.dataset.key + '&index=' + index;

	let filters = [];
	if(wrapper.dataset.valuefilters != '')
		filters = JSON.parse(atob(wrapper.dataset.valuefilters));
	
	if(Array.isArray(filters[index]))
		url += '&subindex=' + sub_index;
	
	if(execute_all)
	{
		if(Array.isArray(filters[index]))
		{
			//Self Parent field
			if(filters[index][sub_index] !='')
				url += '&subfilter=' + filters[index][sub_index];
		}
		else
		{
			if(filters[index] !='')
				url += '&filter=' + filters[index];	
		}
	}
	else
	{
		let obj = document.getElementById(object_id);
		if(Array.isArray(filters[index]))
			url += '&subfilter=' + obj.value;
		else
			url += '&filter=' + obj.value;
	}
		
	fetch(url)
		.then(r => r.json())
		.then(r => {ctRenderTableJoinSelectBox(control_name, r, index, execute_all, sub_index,object_id);})
		.catch(error => console.error("Error", error));
}