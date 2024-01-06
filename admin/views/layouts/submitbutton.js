/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

Joomla.submitbutton = function (task) {
    if (task == '') {
        return false;
    } else {

        if (task == 'layoutwizard') {
            event.preventDefault();
            openLayoutWizard();
            return false;
        }

        if (task == 'addfieldtag') {
            event.preventDefault();
            showModalFieldTagsList();
            return false;
        }

        if (task == 'dependencies') {
            event.preventDefault();
            showModalDependenciesList();
            return false;
        }

        if (task == 'addlayouttag') {
            event.preventDefault();
            showModalTagsList();
            return false;
        }

        var isValid = true;
        var action = task.split('.');
        if (action[1] != 'cancel' && action[1] != 'close') {
            let form = document.getElementById('adminForm');
            if (!document.formvalidator.isValid(form))
                isValid = false;
        }

        if (isValid) {
            Joomla.submitform(task);
            return true;
        } else {
            event.preventDefault();
            return false;
        }
    }
}
