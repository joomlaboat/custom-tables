<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage script.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

jimport('joomla.installer.installer');
jimport('joomla.installer.helper');

use CustomTables\CT;
use CustomTables\database;
use CustomTables\IntegrityChecks;
use Joomla\CMS\Factory;

/**
 * Script File of Customtables Component
 */
class com_customtablesInstallerScript
{
    /**
     * method to uninstall the component
     *
     * @return void
     */
    function uninstall($parent)
    {
        // little notice as after service, in case of bad experience with component.
        echo '<h2>Did something go wrong? Are you disappointed?</h2>
		<p>Please let me know at <a href="mailto:support@joomlaboat.com">support@joomlaboat.com</a>.
		<br />We at JoomlaBoat.com are committed to building extensions that performs proficiently! You can help us, really!
		<br />Send me your thoughts on improvements that is needed, trust me, I will be very grateful!
		<br />Visit us at <a href="https://joomlaboat.com" target="_blank">https://joomlaboat.com</a> today!</p>';
    }

    /**
     * method to run before an install/update/uninstall method
     *
     * @return void
     */
    function preflight($type, $parent)
    {
        // get application
        $app = Factory::getApplication();
        // is redundant ...mmm
        if ($type == 'uninstall') {
            return true;
        }
        // the default for both install and update
        $jVersion = new JVersion();
        if (!$jVersion->isCompatible('3.6.0')) {
            $app->enqueueMessage('Please upgrade to at least Joomla! 3.6.0 before continuing!', 'error');
            return false;
        }
        // do any updates needed
        if ($type == 'update') {
        }
        // do any install needed
        if ($type == 'install') {
        }
    }

    /**
     * method to run after an install/update/uninstall method
     *
     * @return void
     */
    function postflight($type, $parent)
    {
        // get application
        $app = Factory::getApplication();
        // set the default component settings
        if ($type == 'install') {
            // Install the global extension assets permission.
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            // Field to update.
            $fields = array(
                $db->quoteName('rules') . ' = ' . $db->quote('{"site.catalog.access":{"1":1}}'),
            );
            // Condition.
            $conditions = array(
                $db->quoteName('name') . ' = ' . $db->quote('com_customtables')
            );
            $query->update($db->quoteName('#__assets'))->set($fields)->where($conditions);
            database::setQuery($query);

            // Install the global extension params.
            $query = $db->getQuery(true);
            // Field to update.
            $fields = array(
                $db->quoteName('params') . ' = ' . $db->quote('{"autorName":"Ivan Komlev","autorEmail":"support@joomlaboat.com"}'),
            );
            // Condition.
            $conditions = array(
                $db->quoteName('element') . ' = ' . $db->quote('com_customtables')
            );
            $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
            database::setQuery($query);

            echo '<a target="_blank" href="https://joomlaboat.com" title="Custom Tables">
				<img src="' . JURI::root(false) . 'components/com_customtables/libraries/customtables/media/images/controlpanel/customtables.jpg"/>
				</a>';
        }
        // do any updates needed
        if ($type == 'update') {
            echo '<a target="_blank" href="https://joomlaboat.com" title="Custom Tables">
				<img src="' . JURI::root(false) . 'components/com_customtables/libraries/customtables/media/images/controlpanel/customtables.jpg"/>
				</a>
				<h3>Upgrade was Successful!</h3>';
        }

        /*
        $file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
            . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . '_es2ct.php';

        if (file_exists($file)) {
            echo 'Updating Extrasearch Tables';
            require_once($file);
            updateESTables();
        }
        */

        if (!file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'ct_images'))
            mkdir(JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'ct_images');

        $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
        $loader_file = $path . 'loader.php';
        if (file_exists($loader_file)) {
            //Do not run in on uninstall
            require_once($loader_file);
            CTLoader(true);
            $ct = new CT;

            $result = IntegrityChecks::check($ct, true, false);
            if (count($result) > 0)
                echo '<ol><li>' . implode('</li><li>', $result) . '</li></ol>';
        }
    }

    /**
     * method to update the component
     *
     * @return void
     */
    function update($parent)
    {

    }
}
