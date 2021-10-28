<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Layouts;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldCTLayoutType extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	public
	 * @var		string
	 *  
	 */
	public $type = 'ctlayouttype';
	
	public function getOptions()//$name, $value, &$node, $control_name)
	{
		$ct = new CT;
		
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('layouttype'));
		$query->from($db->quoteName('#__customtables_layouts'));
		$query->order($db->quoteName('layouttype') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		$options = array();
		if ($results)
		{
			$Layouts = new Layouts($ct);
			$translations = $Layouts->layoutTypeTranslation();
			$results = array_unique($results);
			
			foreach ($results as $layouttype)
			{
				// Translate the layouttype selection
				if((int)$layouttype!=0)
				{
					$text = $translations[$layouttype];
					// Now add the layouttype and its text to the options array
					$options[] = JHtml::_('select.option', $layouttype, JText::_($text));
				}
			}
		}
		return $options;
	}
}
