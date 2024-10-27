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
use Joomla\Database\DatabaseInterface;

/**
 * Script File of Customtables Component
 *
 * @since 1.0.0
 */
class com_customtablesInstallerScript
{
    /**
     * method to uninstall the component
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function uninstall($parent)
    {
        // Display the original uninstall message
        echo '<h2>Did something go wrong? Are you disappointed?</h2>
    <p>Please let me know at <a href="mailto:support@joomlaboat.com">support@joomlaboat.com</a>.
    <br />We at JoomlaBoat.com are committed to building extensions that performs proficiently! You can help us, really!
    <br />Send me your thoughts on improvements that is needed, trust me, I will be very grateful!
    <br />Visit us at <a href="https://joomlaboat.com" target="_blank">https://joomlaboat.com</a> today!</p>';
    }

    /**
     * method to run before an installation/update/uninstall method
     *
     * @return true
     *
     * @throws Exception
     * @since 1.0.0
     */
    public function preflight($type, $parent)
    {
        if ($type == 'uninstall') {
            $version_object = new Version;
            $version = (int)$version_object->getShortVersion();

            if ($version < 4)
                $db = Factory::getDbo();
            else
                $db = Factory::getContainer()->get(DatabaseInterface::class);

            $prefix = $db->getPrefix();

            // Get list of CustomTables tables
            $tables = $db->setQuery("SHOW TABLES LIKE '{$prefix}customtables_%'")->loadColumn();

            if (count($tables) > 0) {
                echo '<div class="alert alert-info" style="margin-bottom: 20px;">
                <h4>To delete all CustomTables database tables, run these SQL queries:</h4>
                <pre style="background:#f5f5f5; padding:10px; margin-top:10px; overflow:auto;">';

                foreach ($tables as $table) {
                    echo 'DROP TABLE IF EXISTS ' . $db->quoteName($table) . ";\n";
                }

                echo '</pre>
                <p><strong>Note:</strong> Save these queries before closing this window.</p>
            </div>';
            }
        }

        // Rest of your existing code...
        if ($type !== 'uninstall') {
            $app = Factory::getApplication();
            $VersionObject = new Version();
            if (!$VersionObject->isCompatible('3.6.0')) {
                $app->enqueueMessage('Please upgrade to at least Joomla! 3.6.0 before continuing!', 'error');
            }

            //Temporary change component_id of custom back-end menu items
            $version_object = new Version;
            $version = (int)$version_object->getShortVersion();

            if ($version < 4)
                $db = Factory::getDbo();
            else
                $db = Factory::getContainer()->get(DatabaseInterface::class);

            $db->setQuery('UPDATE #__menu SET component_id=0 WHERE client_id=1 AND (
            INSTR(link,"index.php?option=com_customtables&view=listofrecords&Itemid=") OR
            INSTR(link,"index.php?option=com_customtables&view=adminmenu&category=")
        )');
            $db->execute();
        }

        return true;
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
        // Your existing postflight code
        if ($type == 'uninstall') {
            return;
        }

        if (!file_exists(JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'ct_images'))
            mkdir(JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'ct_images');

        $loader_file = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR
            . 'customtables' . DIRECTORY_SEPARATOR . 'loader.php';

        if (file_exists($loader_file)) {
            //Do not run on uninstall
            require_once($loader_file);
            CustomTablesLoader(true);
            $ct = new CT;

            $result = IntegrityChecks::check($ct, true, false);
            if (count($result) > 0)
                echo '<ol><li>' . implode('</li><li>', $result) . '</li></ol>';

        } else {
            echo '<h3>CT Loader not installed!</h3>';
            echo '<p>Path: ' . $loader_file . '</p>';
            return;
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
                'params' => '{"authorName":"Ivan Komlev","authorEmail":"support@joomlaboat.com"}'
            ];
            $whereClauseUpdate = new MySQLWhereClause();
            $whereClauseUpdate->addCondition('element', 'com_customtables');
            database::update('#__extensions', $data, $whereClauseUpdate);

            echo '<a target="_blank" href="https://joomlaboat.com" title="Custom Tables">'
                . '<img src="' . Uri::root() . 'components/com_customtables/libraries/customtables/media/images/controlpanel/customtables.jpg" alt="Custom Tables"/>'
                . '</a>';
        }

        // do any updates needed
        if ($type == 'update') {
            echo '<a target="_blank" href="https://joomlaboat.com" title="Custom Tables">'
                . '<img src="' . Uri::root() . 'components/com_customtables/libraries/customtables/media/images/controlpanel/customtables.jpg" alt="Custom Tables" />'
                . '</a>'
                . '<h3>Upgrade was Successful!</h3>';
        }
    }

    /**
     * method to update the component
     *
     * @return void
     *
     * @since 1.0.0
     */
    function update($parent)
    {
        // Update logic here if needed
    }
}
