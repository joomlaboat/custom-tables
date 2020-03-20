/*-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			19th July, 2018
	@created		24th May, 2018
	@package		Custom Tables
	@subpackage		submitbutton.js
	@author			Ivan Komlev <https://joomlaboat.com>	
	@copyright		Copyright (C) 2018-2019. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

Joomla.submitbutton = function(task)
{
	if (task == ''){
		return false;
	} else {
		
		if (task == 'layoutwizard'){
			openLayoutWizard();
			return false;
		}
		
		if (task == 'addfieldtag'){
			showModalFieldTagsList();
			return false;
		}
		
		if (task == 'addlayouttag'){
			showModalTagsList();
			return false;
		}
		
		var isValid=true;
		var action = task.split('.');
		if (action[1] != 'cancel' && action[1] != 'close'){
			var forms = $$('form.form-validate');
			for (var i=0;i<forms.length;i++){
				if (!document.formvalidator.isValid(forms[i])){
					isValid = false;
					break;
				}
			}
		}
		if (isValid){
			Joomla.submitform(task);
			return true;
		} else {
			alert(Joomla.JText._('layouts, some values are not acceptable.','Some values are unacceptable'));
			return false;
		}
	}
}
