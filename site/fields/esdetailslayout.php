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

class JFormFieldESDetailsLayout extends JFormFieldList
{
	protected $type = 'esdetailslayout';

	protected function getOptions()
	{
		$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
		require_once($path.'loader.php');
		CTLoader();
		
        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id,layoutname, (SELECT tablename FROM #__customtables_tables WHERE id=tableid) AS tablename');
        $query->from('#__customtables_layouts');
		$query->where('published=1 AND (layouttype=4 OR layouttype=8 OR layouttype=9 OR layouttype=10)');
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
