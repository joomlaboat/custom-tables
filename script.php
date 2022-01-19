<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage script.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.installer.installer');
jimport('joomla.installer.helper');

use CustomTables\CT;
use CustomTables\IntegrityChecks;

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
	 * method to update the component
	 *
	 * @return void
	 */
	function update($parent)
	{

	}

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @return void
	 */
	function preflight($type, $parent)
	{
		// get application
		$app = JFactory::getApplication();
		// is redundant ...hmmm
		if ($type == 'uninstall')
		{
			return true;
		}
		// the default for both install and update
		$jversion = new JVersion();
		if (!$jversion->isCompatible('3.6.0'))
		{
			$app->enqueueMessage('Please upgrade to at least Joomla! 3.6.0 before continuing!', 'error');
			return false;
		}
		// do any updates needed
		if ($type == 'update')
		{
		}
		// do any install needed
		if ($type == 'install')
		{
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
		$app = JFactory::getApplication();
		// set the default component settings
		if ($type == 'install')
		{
			// Install the global extenstion assets permission.
			$db = JFactory::getDbo();
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
			$db->setQuery($query);
			$allDone = $db->execute();

			// Install the global extenstion params.
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
			$db->setQuery($query);
			$allDone = $db->execute();

			echo '<a target="_blank" href="https://joomlaboat.com" title="Custom Tables">
				<img src="'.JURI::root(true).'/components/com_customtables/libraries/media/images/controlpanel/customtables.jpg"/>
				</a>';
		}
		// do any updates needed
		if ($type == 'update')
		{
			echo '<a target="_blank" href="https://joomlaboat.com" title="Custom Tables">
				<img src="'.JURI::root(true).'/components/com_customtables/libraries/media/images/controlpanel/customtables.jpg"/>
				</a>
				<h3>Upgrade was Successful!</h3>';
		}

		if(!file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'ct_images'))
				mkdir(JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'ct_images');
				
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
		$loader_file = $path.'loader.php';
		if(file_exists($loader_file))
		{
			//Do not run in on uninstall
			require_once($loader_file);
			CTLoader(true);
			$ct = new CT;
		
			$result = IntegrityChecks::check($ct,true,false);
			if(count($result)>0)
				echo '<ol><li>'.implode('</li><li>',$result).'</li></ol>';
		}
			
		//if(!$ct->Env->advancedtagprocessor)
			//$this->hideCategorySubMenu();
	}
	/*
	function hideCategorySubMenu()
	{
		$db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
            //$db->quoteName('published') . ' = 0',
			$db->quoteName('client_id') . ' = 99' //imposible client id to hide the menu item
        );

        $conditions = array(
            $db->quoteName('alias') . ' = ' . $db->quote('com-customtables-submenu-listofcategories'), 
            $db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_customtables&view=listofcategories'),
        );
	
        $query->update($db->quoteName('#__menu'))->set($fields)->where($conditions);

        $db->setQuery($query);   
        $db->execute();     
	}
	
	function deleteCategorySubMenu()
	{
		$db = JFactory::getDbo();
        
        $conditions = array(
            $db->quoteName('alias') . ' = ' . $db->quote('com-customtables-submenu-listofcategories'), 
            $db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_customtables&view=listofcategories'),
        );
	
        $query= 'DELETE FROM #__menu WHERE '.implode(' AND ',$conditions);

        $db->setQuery($query);   
        $db->execute();     
	}
	*/
}
