/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

Joomla.submitbutton = function (task) {

	if (task == '') {
		return false;
	} else {
		const action = task.split('.');
		if (action[1] != 'cancel' && action[1] != 'close') {
			let form = document.getElementById('adminForm');
			if (!document.formvalidator.isValid(form))
				return false;
		}
		Joomla.submitform(task);
		return true;
	}
}

function disableProField(object_name) {
	let object = document.getElementById(object_name);
	object.value = "Available in Pro version";
	object.disabled = true;
}
