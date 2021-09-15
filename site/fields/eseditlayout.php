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

class JFormFieldESEditLayout extends JFormFieldList
{
	protected $type = 'eseditlayout';

	protected function getOptions()
	{
        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id,layoutname, (SELECT tablename FROM #__customtables_tables WHERE id=tableid) AS tablename');
        $query->from('#__customtables_layouts');
		$query->where('published=1 AND layouttype=2');
		$query->order('tablename,layoutname');

        $db->setQuery((string)$query);
        $messages = $db->loadObjectList();
        $options = array();

		$options[] = JHtml::_('select.option', '', '- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ));

        if ($messages)
        {
            foreach($messages as $message)
            {
                $options[] = JHtml::_('select.option', $message->layoutname, $message->tablename.': '.$message->layoutname);

            }
        }
        $options = array_merge(parent::getOptions(), $options);
        return $options;

	}
}
