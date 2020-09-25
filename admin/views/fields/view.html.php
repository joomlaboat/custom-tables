<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage views/fields/view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Fields View class
 */
class CustomtablesViewFields extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	var $tableid;
	
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		
		
		// Assign the variables
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		
		if((int)$this->item->id==0)
			$this->tableid=$app->input->getint('tableid',0);
		else
			$this->tableid=$this->item->tableid;
		
		
		$this->script = $this->get('Script');
		$this->state = $this->get('State');
		// get action permissions
		$this->canDo = CustomtablesHelper::getActions('fields',$this->item);
		// get input
		$jinput = JFactory::getApplication()->input;
		$this->ref = JFactory::getApplication()->input->get('ref', 0, 'word');
		$this->refid = JFactory::getApplication()->input->get('refid', 0, 'int');
		$this->referral = '';
		if ($this->refid)
		{
			// return to the item that refered to this item
			$this->referral = '&ref='.(string)$this->ref.'&refid='.(int)$this->refid;
		}
		elseif($this->ref)
		{
			// return to the list view that refered to this item
			$this->referral = '&ref='.(string)$this->ref;
		}

		// Set the toolbar
		$this->addToolBar();
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}


	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId	= $user->id;
		$isNew = $this->item->id == 0;

		JToolbarHelper::title( JText::_($isNew ? 'COM_CUSTOMTABLES_FIELDS_NEW' : 'COM_CUSTOMTABLES_FIELDS_EDIT'), 'pencil-2 article-add');
		// Built the actions for new and existing records.
		if ($this->refid || $this->ref)
		{
			if ($this->canDo->get('core.create') && $isNew)
			{
				// We can create the record.
				JToolBarHelper::save('fields.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canDo->get('core.edit'))
			{
				// We can save the record.
				JToolBarHelper::save('fields.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// Do not creat but cancel.
				JToolBarHelper::cancel('fields.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// We can close it.
				JToolBarHelper::cancel('fields.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			if ($isNew)
			{
				// For new records, check the create permission.
				if ($this->canDo->get('core.create'))
				{
					JToolBarHelper::apply('fields.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('fields.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('fields.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				JToolBarHelper::cancel('fields.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				if ($this->canDo->get('core.edit'))
				{
					// We can save the new record
					JToolBarHelper::apply('fields.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('fields.save', 'JTOOLBAR_SAVE');
					// We can save this record, but check the create permission to see
					// if we can return to make a new one.
					if ($this->canDo->get('core.create'))
					{
						JToolBarHelper::custom('fields.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				if ($this->canDo->get('core.create'))
				{
					JToolBarHelper::custom('fields.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				JToolBarHelper::cancel('fields.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		JToolbarHelper::divider();
		// set help url for this view if found
		$help_url = CustomtablesHelper::getHelpUrl('fields');
		if (CustomtablesHelper::checkString($help_url))
		{
			JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
		}
	}

	/**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 30)
		{
    		// use the helper htmlEscape method instead and shorten the string
			return CustomtablesHelper::htmlEscape($var, $this->_charset, true, 30);
		}
		// use the helper htmlEscape method instead.
		return CustomtablesHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$isNew = ($this->item->id < 1);
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_($isNew ? 'COM_CUSTOMTABLES_FIELDS_NEW' : 'COM_CUSTOMTABLES_FIELDS_EDIT'));
		$this->document->addScript(JURI::root(true) . $this->script, (CustomtablesHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
		$this->document->addScript(JURI::root(true)."/administrator/components/com_customtables/views/fields/submitbutton.js", (CustomtablesHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript'); 
		JText::script('view not acceptable. Error');
	}
	
	protected function getAllTables()
	{

        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id,tablename,tabletitle');
        $query->from('#__customtables_tables');
		$query->order('tabletitle');
		
		$db->setQuery((string)$query);
        $records = $db->loadObjectList();
		
		$items=array();
		foreach($records as $rec)
		{
			$items[]='['.$rec->id.',"'.$rec->tablename.'","'.$rec->tabletitle.'"]';
		}
		
		return '['.implode(',',$items).']';
	}

	
}
