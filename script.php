<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage script.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

jimport('joomla.installer.installer');
jimport('joomla.installer.helper');

use CustomTables\CT;
use CustomTables\database;
use CustomTables\IntegrityChecks;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

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
        $VersionObject = new Version();
        if (!$VersionObject->isCompatible('3.6.0')) {
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
     * @throws Exception
     * @since 3.2.3
     */
    function postflight($type, $parent)
    {
        if ($type == 'uninstall') {
            return true; //No need to do anything
        }

        if (!file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'ct_images'))
            mkdir(JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'ct_images');

        $loader_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR
            . 'customtables' . DIRECTORY_SEPARATOR . 'loader.php';

        if (file_exists($loader_file)) {
            //Do not run on uninstall
            require_once($loader_file);
            CustomTablesLoader(true, false, null, 'com_customtables', true);
            $ct = new CT;

            $result = IntegrityChecks::check($ct, true, false);
            if (count($result) > 0)
                echo '<ol><li>' . implode('</li><li>', $result) . '</li></ol>';
        } else {
            echo '<h3>CT Loader not installed!</h3>';
            echo '<p>Path: ' . $loader_file . '</p>';
            return false;
        }

        // set the default component settings
        if ($type == 'install') {
            // Install the global extension assets permission.

            $data = [
                'rules' => '{"site.catalog.access":{"1":1}}'
            ];
            $whereClauseUpdate = new MySQLWhereClause();
            $whereClauseUpdate->addCondition('name', 'com_customtables');
            database::update('#__assets', $data, $whereClauseUpdate);

            $data = [
                'params' => '{"autorName":"Ivan Komlev","autorEmail":"support@joomlaboat.com"}'
            ];
            $whereClauseUpdate = new MySQLWhereClause();
            $whereClauseUpdate->addCondition('element', 'com_customtables');
            database::update('#__extensions', $data, $whereClauseUpdate);

            echo '<a target="_blank" href="https://joomlaboat.com" title="Custom Tables">'
                . '<img src="' . Uri::root(false) . 'components/com_customtables/libraries/customtables/media/images/controlpanel/customtables.jpg"/>'
                . '</a>';
        }
        // do any updates needed
        if ($type == 'update') {
            echo '<a target="_blank" href="https://joomlaboat.com" title="Custom Tables">'
                . '<img src="' . Uri::root(false) . 'components/com_customtables/libraries/customtables/media/images/controlpanel/customtables.jpg"/>'
                . '</a>'
                . '<h3>Upgrade was Successful!</h3>';
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
