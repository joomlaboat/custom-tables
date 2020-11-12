/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

Joomla.submitbutton = function(task)
{
	if (task == ''){
		return false;
	} else { 
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
			alert(Joomla.JText._('tables, some values are not acceptable.','Some values are unacceptable'));
			return false;
		}
	}
}
