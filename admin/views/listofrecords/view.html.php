<?php
/*

	@version		1.6.1
	@build			1st July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		view.html.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Customtables View class for the Listoffields
 */
class CustomtablesViewListofrecords extends JViewLegacy
{
	/**
	 * Listoffields view display method
	 * @return void
	 */

	var $tableid;
	var $tablename;
	var $tabletitle;
	var $languages;
	var $tablefields;
	var $langpostfix;

	function display($tpl = null)
	{
		
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
		$LangMisc	= new ESLanguages;
		$this->languages=$LangMisc->getLanguageList();
		
		$this->langpostfix=$LangMisc->getLangPostfix();
		$app = JFactory::getApplication();
		$this->tablename="";
		$this->tabletitle="";
		$this->tablefields=array();

		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listofrecords');
		}

		// Assign data to the view
		$jinput=$app->input;
		$this->tableid=$jinput->getInt('tableid',0);
		
		$this->state = $this->get('State');
		$state_tableid=(int)$this->state->get('filter.tableid');
		
		if($state_tableid!=0)// and $tableid==0)
		{
			$this->tableid=$state_tableid;
			$jinput->set('tableid',$this->tableid);
			$this->tableid=$jinput->getInt('tableid',0);
		}
		elseif($state_tableid==0 and $this->tableid!=0)
		{
			$this->state->set('filter.tableid',$this->tableid);
		}
		
		
		if($this->tableid!=0)
		{
			require_once(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
			$table=ESTables::getTableRowByID($this->tableid);
			if(!is_object($table) and $table==0)
			{
				JFactory::getApplication()->enqueueMessage('Table not found', 'error');
				$this->tableid=0;
			}
			else
			{
				require_once(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fields.php');
				$this->tablename=$table->tablename;
				$this->tabletitle=$table->tabletitle;
				$this->tablefields=ESFields::getFields($this->tableid);
			}
		}
		
	
		$jinput->set('tablename',$this->tablename);
		

		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		
		//$this->user = JFactory::getUser();
		//$this->listOrder = $this->escape($this->state->get('list.ordering'));
		//$this->listDirn = $this->escape($this->state->get('list.direction'));
		//$this->saveOrder = $this->listOrder == 'ordering';
		// get global action permissions
		$this->canDo = CustomtablesHelper::getActions('fields');
		$this->canEdit = $this->canDo->get('core.edit');
		$this->canState = $this->canDo->get('core.edit.state');
		$this->canCreate = $this->canDo->get('core.create');
		$this->canDelete = $this->canDo->get('core.delete');
		$this->canBatch = $this->canDo->get('core.batch');

		

		

		if ($this->getLayout()!== 'modal')
		{
			//$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
			// load the batch html
			//if ($this->canCreate && $this->canEdit && $this->canState)
			//{
				//$this->batchDisplay = JHtmlBatch_::render();
			//}
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			//throw new Exception(implode("\n", $errors), 500);
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
		$app = JFactory::getApplication();

		if($this->tableid!=0)
		{
			require_once(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');

			JToolBarHelper::title('Table "'.$this->tabletitle.'" - '.JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');
		}
		else
			JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');


		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listofrecords&tableid='.$this->tableid);
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/records');

		if ($this->canCreate)
		{
			JToolBarHelper::addNew('records.add');
		}

		// Only load if there are items
		if (CustomtablesHelper::checkArray($this->items))
		{
			if ($this->canEdit)
			{
				JToolBarHelper::editList('records.edit');
			}

			if ($this->canState)
			{
				JToolBarHelper::publishList('listofrecords.publish');
				JToolBarHelper::unpublishList('listofrecords.unpublish');
				//JToolBarHelper::archiveList('listofrecords.archive');

				//if ($this->canDo->get('core.admin'))
				//{
					//JToolBarHelper::checkin('listofrecords.checkin');
				//}
			}

			// Add a batch button
			/*
			if ($this->canBatch && $this->canCreate && $this->canEdit && $this->canState)
			{
				// Get the toolbar object instance
				$bar = JToolBar::getInstance('toolbar');
				// set the batch button name
				$title = JText::_('JTOOLBAR_BATCH');
				// Instantiate a new JLayoutFile instance and render the batch button
				$layout = new JLayoutFile('joomla.toolbar.batch');
				// add the button to the page
				$dhtml = $layout->render(array('title' => $title));
				$bar->appendButton('Custom', $dhtml, 'batch');
			}
*/
			if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete))
			{
				JToolbarHelper::deleteList('', 'listofrecords.delete', 'JTOOLBAR_EMPTY_TRASH');
			}
			elseif ($this->canState && $this->canDelete)
			{
				JToolbarHelper::trash('listofrecords.trash');
			}
		}

		// set help url for this view if found
		$help_url = CustomtablesHelper::getHelpUrl('listofrecords');
		if (CustomtablesHelper::checkString($help_url))
		{
				JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
		}

		// add the options comp button
		if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
		{
			JToolBarHelper::preferences('com_customtables');
		}

		if ($this->canState)
		{
			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
			// only load if batch allowed
			if ($this->canBatch)
			{
				JHtmlBatch_::addListSelection(
					JText::_('COM_CUSTOMTABLES_KEEP_ORIGINAL_STATE'),
					'batch[published]',
					JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
				);
			}
		}

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);


		// Set Tableid Selection
		$this->tableidOptions = $this->getTheTableidSelections();

		if ($this->tableidOptions)
		{
			// Tableid Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_CUSTOMTABLES_RECORDS_TABLE_LABEL').' -',
				'filter_tableid',
				JHtml::_('select.options', $this->tableidOptions, 'value', 'text', $this->state->get('filter.tableid'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Tableid Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_CUSTOMTABLES_RECORDS_TABLE_LABEL').' -',
					'batch[tableid]',
					JHtml::_('select.options', $this->tableidOptions, 'value', 'text')
				);
			}
		}
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_('COM_CUSTOMTABLES_LISTOFRECORDS'));
//		$this->document->addStyleSheet(JURI::root(true)."/administrator/components/com_customtables/assets/css/listofrecords.css", (CustomtablesHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
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
		if(strlen($var) > 50)
		{
			// use the helper htmlEscape method instead and shorten the string
			return CustomtablesHelper::htmlEscape($var, $this->_charset, true);
		}
		// use the helper htmlEscape method instead.
		return CustomtablesHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
/*
	protected function getTheTypeSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('type'));
		$query->from($db->quoteName('#__customtables_fields'));
		$query->order($db->quoteName('type') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = $this->getModel();
			$results = array_unique($results);
			$_filter = array();
			foreach ($results as $type)
			{
				// Translate the type selection
				$text = $model->selectionTranslation($type,'type');
				// Now add the type and its text to the options array
				$_filter[] = JHtml::_('select.option', $type, JText::_($text));
			}
			return $_filter;
		}
		return false;
	}
*/
	protected function getTheTableidSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName(array('id','tabletitle')));
		$query->from('#__customtables_tables');
		$query->order($db->quoteName('tabletitle') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadObjectList();

		if ($results)
		{
			//$results = array_unique($results);
			$_filter = array();
			foreach ($results as $result)
			{
				// Now add the tableid and its text to the options array
				$_filter[] = JHtml::_('select.option', $result->id, $result->tabletitle);
			}
			return $_filter;
		}
		return false;
	}
	
	
	protected function processRecord($row,$detailsLayout)
	{
		$jinput=JFactory::getApplication()->input;

		$paramsArray=array();
		$paramsArray['establename']=$this->tablename;
		$paramsArray['listingid']=(int)$row['id'];
		$paramsArray['custom_where']='';
		
		if($paramsArray==null)
				return '';

		$_params= new JRegistry;
		$_params->loadArray($paramsArray);

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'details.php');

		$config=array();
		$model = JModelLegacy::getInstance('Details', 'CustomTablesModel', $config);
		$model->load($_params,$paramsArray['listingid'],true,$paramsArray['custom_where']);

		$model->LayoutProc->layout=$detailsLayout;

		if(count($row)>0)
		{
			$model->LayoutProc->toolbar_array=array();
			$result=$model->LayoutProc->fillLayout($row);
			return $result;
		}
		else
			return 'CustomTables Record Not Found';
	}
	

}
