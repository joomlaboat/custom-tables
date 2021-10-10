<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldCTOption extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 *
	 */
	protected $type = 'ctoption';

	protected function getOptions()//$name, $value, &$node, $control_name)
	{
		$jinput = JFactory::getApplication()->input;

		$currentoptionid=0;
		if ($jinput->get('id'))
			$currentoptionid = $jinput->getInt('id');

        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id,title');
        $query->from('#__customtables_options');
		$query->order('title');
		$query->where('id!='.(int)$currentoptionid);

		$db->setQuery((string)$query);
        $records = $db->loadObjectList();

        $options = array();
        if ($records)
        {
			$options[] = JHtml::_('select.option', '', JText::_('COM_CUSTOMTABLES_FIELDS_SELECT_LABEL'));
            foreach($records as $rec)
                $options[] = JHtml::_('select.option', $rec->id, $rec->title);
        }
        return $options;
	}
}
