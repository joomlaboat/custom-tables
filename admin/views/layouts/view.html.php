<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
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
 * Layouts View class
 */
class CustomtablesViewLayouts extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	public function display($tpl = null)
	{
		// Assign the variables
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->script = $this->get('Script');
		$this->state = $this->get('State');
		// get action permissions
		$this->canDo = CustomtablesHelper::getActions('layouts',$this->item);
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

		JToolbarHelper::title( JText::_($isNew ? 'COM_CUSTOMTABLES_LAYOUTS_NEW' : 'COM_CUSTOMTABLES_LAYOUTS_EDIT'), 'pencil-2 article-add');
		// Built the actions for new and existing records.
		if ($this->refid || $this->ref)
		{
			if ($this->canDo->get('core.create') && $isNew)
			{
				// We can create the record.
				JToolBarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canDo->get('core.edit'))
			{
				// We can save the record.
				JToolBarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// Do not creat but cancel.
				JToolBarHelper::cancel('layouts.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// We can close it.
				JToolBarHelper::cancel('layouts.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			if ($isNew)
			{
				// For new records, check the create permission.
				if ($this->canDo->get('core.create'))
				{
					JToolBarHelper::apply('layouts.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('layouts.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};

				JToolbarHelper::custom('layoutwizard', 'wizzardbutton', 'wizzardbutton', 'COM_CUSTOMTABLES_BUTTON_LAYOUTAUTOCREATE', false);//Layout Wizard
				JToolbarHelper::custom('addfieldtag', 'fieldtagbutton', 'fieldtagbutton', 'COM_CUSTOMTABLES_BUTTON_ADDFIELDTAG', false);
				JToolbarHelper::custom('addlayouttag', 'layouttagbutton', 'layouttagutton', 'COM_CUSTOMTABLES_BUTTON_ADDLAYOUTTAG', false);

				JToolBarHelper::cancel('layouts.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				if ($this->canDo->get('core.edit'))
				{
					// We can save the new record
					JToolBarHelper::apply('layouts.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('layouts.save', 'JTOOLBAR_SAVE');
					// We can save this record, but check the create permission to see
					// if we can return to make a new one.
					if ($this->canDo->get('core.create'))
					{
						JToolBarHelper::custom('layouts.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				if ($this->canDo->get('core.create'))
				{
					JToolBarHelper::custom('layouts.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				
				JToolbarHelper::custom('layoutwizard', 'wizzardbutton', 'wizzardbutton', 'COM_CUSTOMTABLES_BUTTON_LAYOUTAUTOCREATE', false);//Layout Wizard
				JToolbarHelper::custom('addfieldtag', 'fieldtagbutton', 'fieldtagbutton', 'COM_CUSTOMTABLES_BUTTON_ADDFIELDTAG', false);
				JToolbarHelper::custom('addlayouttag', 'layouttagbutton', 'layouttagutton', 'COM_CUSTOMTABLES_BUTTON_ADDLAYOUTTAG', false);
				JToolbarHelper::custom('dependencies', 'dependencies', 'dependencies', 'COM_CUSTOMTABLES_BUTTON_DEPENDENCIES', false);
				
				JToolBarHelper::cancel('layouts.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		JToolbarHelper::divider();
		// set help url for this view if found
		$help_url = CustomtablesHelper::getHelpUrl('layouts');
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
		$this->document->setTitle(JText::_($isNew ? 'COM_CUSTOMTABLES_LAYOUTS_NEW' : 'COM_CUSTOMTABLES_LAYOUTS_EDIT'));
		$this->document->addScript(JURI::root(true) . $this->script, (CustomtablesHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
		$this->document->addScript(JURI::root(true)."/administrator/components/com_customtables/views/layouts/submitbutton.js", (CustomtablesHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript'); 
		JText::script('view not acceptable. Error');
	}
	
	protected function getMenuItems()
	{
		if(!isset($this->row) or !is_array($this->row) or count($this->row)==0)
			return '';
		
		$result='';
		
		$wheretosearch=array();
		$whattolookfor=array();

		switch($this->row->layouttype)
		{
			case 1:
				$wheretosearch[]='escataloglayout';
				$whattolookfor[]=$this->row->layoutname;
			break;

			case 2:
				$wheretosearch[]='eseditlayout';
				$whattolookfor[]=$this->row->layoutname;
				$wheretosearch[]='editlayout';
				$whattolookfor[]='layout:'.$this->row->layoutname;
			break;
		
			case 4:
				$wheretosearch[]='esdetailslayout';
				$whattolookfor[]=$this->row->layoutname;
			break;

			case 5:
				$wheretosearch[]='escataloglayout';
				$whattolookfor[]=$this->row->layoutname;
			break;
		
			case 6:
				$wheretosearch[]='esitemlayout';
				$whattolookfor[]=$this->row->layoutname;
			break;

			case 7:
				$wheretosearch[]='onrecordaddsendemaillayout';
				$whattolookfor[]=$this->row->layoutname;
			break;
		}

		$where=array();
		$i=0;
		foreach($wheretosearch as $w)
		{
			$where[]='INSTR(params,\'"'.$w.'":"'.$whattolookfor[$i].'"\')';
			$i++;
		}

		if(count($where)>0)
		{

			$db = JFactory::getDBO();
			$query='SELECT id,title FROM #__menu WHERE '.implode(' OR ',$where);

			$db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());
			$recs = $db->loadAssocList( );
			
			if(count($recs)>0)
			{
				$result='<hr/><p>List of Menu Items that use this layout:</p><ul>';
				foreach($recs as $r)
				{
					$link='/administrator/index.php?option=com_menus&view=item&layout=edit&id='.$r['id'];
					$result.='<li><a href="'.$link.'" target="_blank">'.$r['title'].'</a></li>';	
				}
				$result.='</ul>';
			}
		}
		
		return $result;
	}
	
	
	protected function getLayouts()
	{
        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id,layoutname,tableid,layouttype');
        $query->from('#__customtables_layouts');
		$query->order('layoutname');
		$query->where('published=1');
		$db->setQuery((string)$query);
        return $db->loadObjectList();
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